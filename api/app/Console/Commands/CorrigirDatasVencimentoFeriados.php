<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;

class CorrigirDatasVencimentoFeriados extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'corrigir:datas-vencimento-feriados {slot : de 0 a 9 para particionar}';

    protected $description = 'Corrige automaticamente datas de vencimento real de parcelas que caem em feriados (particionado em 10 slots)';

    public function handle()
    {
        $slot = (int) $this->argument('slot');
        if ($slot < 0 || $slot > 9) {
            $this->error('Slot inválido. Use 0..9.');
            Log::warning("[corrigir:datas-vencimento-feriados] Slot INVÁLIDO recebido: {$slot}");
            return self::FAILURE;
        }

        $inicio = microtime(true);
        $dataExec = now()->toDateTimeString();
        $this->info("Iniciando correção de datas de vencimento (slot {$slot}) às {$dataExec}");
        Log::info("[corrigir:datas-vencimento-feriados] Início | slot={$slot} | ts={$dataExec}");

        // 1. Buscar todos os feriados agrupados por company_id
        $feriadosPorEmpresa = Feriado::select('company_id', 'data_feriado')
            ->get()
            ->groupBy('company_id')
            ->map(function ($feriados) {
                return $feriados->pluck('data_feriado')
                    ->map(function ($date) {
                        return Carbon::parse($date)->format('Y-m-d');
                    })
                    ->toArray();
            });

        if ($feriadosPorEmpresa->isEmpty()) {
            $this->info("Nenhum feriado encontrado. Nada a processar.");
            Log::info("[corrigir:datas-vencimento-feriados] Nenhum feriado encontrado");
            return self::SUCCESS;
        }

        // 2. Buscar parcelas cujo venc_real bate com algum feriado
        $datasFeriados = $feriadosPorEmpresa->flatten()->unique()->values()->all();

        if (empty($datasFeriados)) {
            $this->info("Nenhuma data de feriado para processar.");
            return self::SUCCESS;
        }

        $query = Parcela::query()
            ->whereNull('dt_baixa')
            ->whereIn('venc_real', $datasFeriados)
            ->whereRaw('MOD(emprestimo_id, 10) = ?', [$slot])
            ->with(['emprestimo.company'])
            ->orderBy('emprestimo_id')
            ->orderBy('parcela');

        $total = (clone $query)->count();
        $this->info("Total de parcelas com venc_real em feriados no slot {$slot}: {$total}");
        Log::info("[corrigir:datas-vencimento-feriados] Slot {$slot} | total_parcelas={$total}");

        // Counters de monitoramento
        $countProcessados = 0;
        $countCorrigidos = 0;
        $countIgnorados = 0;
        $countFalhas = 0;
        $emprestimosProcessados = [];

        // Agrupar parcelas por empréstimo
        $parcelasPorEmprestimo = [];
        $query->chunkById(500, function ($parcelas) use (&$parcelasPorEmprestimo) {
            foreach ($parcelas as $parcela) {
                if (!isset($parcelasPorEmprestimo[$parcela->emprestimo_id])) {
                    $parcelasPorEmprestimo[$parcela->emprestimo_id] = [];
                }
                $parcelasPorEmprestimo[$parcela->emprestimo_id][] = $parcela;
            }
        });

        // 3. Processar cada empréstimo
        foreach ($parcelasPorEmprestimo as $emprestimoId => $parcelasComFeriado) {
            $countProcessados++;

            try {
                $emprestimo = $parcelasComFeriado[0]->emprestimo;

                if (!$emprestimo) {
                    $countIgnorados++;
                    continue;
                }

                // Buscar feriados da empresa
                $feriados = $feriadosPorEmpresa->get($emprestimo->company_id, []);

                if (empty($feriados)) {
                    $countIgnorados++;
                    continue;
                }

                // Buscar TODAS as parcelas não pagas do empréstimo (não apenas as que caem em feriado)
                $todasParcelas = Parcela::where('emprestimo_id', $emprestimoId)
                    ->whereNull('dt_baixa')
                    ->orderBy('parcela')
                    ->get();

                if ($todasParcelas->isEmpty()) {
                    $countIgnorados++;
                    continue;
                }

                // Função auxiliar para verificar se é feriado
                $isFeriado = function ($date) use ($feriados) {
                    $dateString = Carbon::parse($date)->format('Y-m-d');
                    return in_array($dateString, $feriados);
                };

                // Calcular intervalo entre parcelas
                $intervalo = 0;
                if ($todasParcelas->count() >= 2) {
                    $primeiraParcela = $todasParcelas->first();
                    $segundaParcela = $todasParcelas->skip(1)->first();
                    $data1 = Carbon::parse($primeiraParcela->venc);
                    $data2 = Carbon::parse($segundaParcela->venc);
                    $intervalo = $data1->diffInDays($data2);
                } else {
                    $primeiraParcela = $todasParcelas->first();
                    $dataLanc = Carbon::parse($emprestimo->dt_lancamento);
                    $dataVenc = Carbon::parse($primeiraParcela->venc);
                    $intervalo = $dataLanc->diffInDays($dataVenc);
                }

                if ($intervalo <= 0) {
                    $intervalo = 30;
                }

                // Função para encontrar o próximo dia útil (não feriado)
                $proximoDiaUtil = function ($date) use ($isFeriado) {
                    $currentDate = Carbon::parse($date);
                    do {
                        $currentDate->addDay();
                    } while ($isFeriado($currentDate));

                    return $currentDate;
                };

                // Recalcular as datas de vencimento real de TODAS as parcelas do empréstimo
                $dataBaseAnterior = null;
                $parcelasAtualizadas = 0;

                foreach ($todasParcelas as $index => $parcela) {
                    if ($index === 0) {
                        // Primeira parcela: usar a data de vencimento teórica (venc)
                        $novaDataVencReal = Carbon::parse($parcela->venc);
                    } else {
                        // Parcelas seguintes: calcular baseado na data ajustada da parcela anterior + intervalo
                        $novaDataVencReal = $dataBaseAnterior->copy()->addDays($intervalo);
                    }

                    // Verificar se cai em feriado e ajustar se necessário
                    while ($isFeriado($novaDataVencReal)) {
                        $novaDataVencReal = $proximoDiaUtil($novaDataVencReal);
                    }

                    // Atualizar a parcela apenas se a data mudou
                    if ($parcela->venc_real != $novaDataVencReal->format('Y-m-d')) {
                        $parcela->venc_real = $novaDataVencReal->format('Y-m-d');
                        $parcela->save();
                        $parcelasAtualizadas++;
                    }

                    // Atualizar data base para próxima parcela
                    $dataBaseAnterior = $novaDataVencReal->copy();
                }

                if ($parcelasAtualizadas > 0) {
                    $countCorrigidos++;
                    $emprestimosProcessados[] = $emprestimoId;
                    Log::info("[corrigir:datas-vencimento-feriados] Empréstimo {$emprestimoId} corrigido | parcelas_atualizadas={$parcelasAtualizadas}");
                } else {
                    $countIgnorados++;
                }

            } catch (\Throwable $e) {
                $countFalhas++;
                Log::error("[corrigir:datas-vencimento-feriados] ERRO emprestimo={$emprestimoId} | {$e->getMessage()} | line={$e->getLine()}");
            }
        }

        $fim = microtime(true);
        $duracao = number_format($fim - $inicio, 2);

        $this->info("Resumo slot {$slot}: processados={$countProcessados}, corrigidos={$countCorrigidos}, ignorados={$countIgnorados}, falhas={$countFalhas}, tempo={$duracao}s");
        Log::info("[corrigir:datas-vencimento-feriados] Fim | slot={$slot} | processados={$countProcessados} | corrigidos={$countCorrigidos} | ignorados={$countIgnorados} | falhas={$countFalhas} | duracao={$duracao}s | emprestimos_corrigidos=" . implode(',', $emprestimosProcessados));

        return self::SUCCESS;
    }
}

