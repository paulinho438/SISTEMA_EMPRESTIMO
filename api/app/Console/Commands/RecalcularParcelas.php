<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Parcela;
use App\Services\BcodexService;

use Carbon\Carbon;

class RecalcularParcelas extends Command
{
    /**
     * The name and signature of the console command.
     * Use: php artisan recalcular:Parcelas {slot}
     * Ex.: php artisan recalcular:Parcelas 0   (slots 0..9)
     */
    protected $signature = 'recalcular:Parcelas {slot : de 0 a 9 para particionar}';

    protected $description = 'Recalcular Parcelas em atraso (particionado em 10 slots)';

    public function handle()
    {
        $slot = (int) $this->argument('slot');
        if ($slot < 0 || $slot > 9) {
            $this->error('Slot inválido. Use 0..9.');
            Log::warning("[recalcular:Parcelas] Slot INVÁLIDO recebido: {$slot}");
            return self::FAILURE;
        }

        $inicio = microtime(true);
        $dataExec = now()->toDateTimeString();
        $this->info("Iniciando recalculo (slot {$slot}) às {$dataExec}");
        Log::info("[recalcular:Parcelas] Início | slot={$slot} | ts={$dataExec}");

        // QUERY: filtra no banco e particiona por slot (0..9)
        $query = Parcela::query()
            ->where('venc_real', '<', now()->subDay())
            ->whereNull('dt_baixa')
            ->whereHas('emprestimo', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('protesto')->orWhere('protesto', 0);
                });
            })
            ->whereRaw('MOD(parcelas.id, 10) = ?', [$slot])
            ->with([
                'emprestimo.company',
                'emprestimo.banco',
                'emprestimo.contaspagar',
                'emprestimo.quitacao',
                'emprestimo.pagamentominimo',
                'emprestimo.pagamentosaldopendente',
                'emprestimo.parcelas',
            ])
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info("Total de parcelas no slot {$slot}: {$total}");
        Log::info("[recalcular:Parcelas] Slot {$slot} | total={$total}");

        // Counters de monitoramento
        $countProcessadas = 0;
        $countAtualizadas = 0;
        $countIgnoradas   = 0;
        $countFalhas      = 0;

        $bcodexService = new BcodexService();
        $hoje = Carbon::today()->toDateString();

        // Processa em blocos para economizar memória
        $query->chunkById(500, function ($parcelas) use (
            &$countProcessadas, &$countAtualizadas, &$countIgnoradas, &$countFalhas,
            $bcodexService, $hoje, $slot
        ) {
            $ids = $parcelas->pluck('id')->all();
            Log::info("[recalcular:Parcelas] Slot {$slot} | chunk iniciando | ids=" . implode(',', $ids));

            foreach ($parcelas as $parcela) {
                $countProcessadas++;

                try {
                    // Pré-atualizações idempotentes
                    if ($parcela->emprestimo) {
                        $emprestimo = $parcela->emprestimo;
                        if ($emprestimo->deve_cobrar_hoje !== $hoje) {
                            $emprestimo->deve_cobrar_hoje = $hoje;
                            $emprestimo->save();
                            Log::debug("[recalcular:Parcelas] parcela={$parcela->id} | deve_cobrar_hoje atualizado p/ {$hoje}");
                        }
                    }

                    $updatedAt = Carbon::parse($parcela->updated_at)->startOfDay();
                    if (!$updatedAt->equalTo(Carbon::today())) {
                        $parcela->atrasadas = $parcela->atrasadas + 1;
                        $parcela->save();
                        Log::debug("[recalcular:Parcelas] parcela={$parcela->id} | atrasadas++ => {$parcela->atrasadas}");
                    }

                    // Regras de processamento
                    if (!($parcela->emprestimo && $parcela->emprestimo->contaspagar && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado")) {
                        $countIgnoradas++;
                        Log::debug("[recalcular:Parcelas] parcela={$parcela->id} | ignorada (contaspagar.status != 'Pagamento Efetuado')");
                        continue;
                    }

                    $juros = $parcela->emprestimo->company->juros ?? 1;
                    $valorJuros = (float) number_format($parcela->emprestimo->valor * ($juros / 100), 2, '.', '');
                    $novoValor = $valorJuros + $parcela->saldo;

                    $qtdParcelasEmp = count($parcela->emprestimo->parcelas ?? []);
                    if ($qtdParcelasEmp === 1) {
                        $novoValor  = $parcela->saldo + (1 * $parcela->saldo / 100);
                        $valorJuros = (1 * $parcela->saldo / 100);
                    }

                    // Cobrança principal (wallet)
                    if ($parcela->emprestimo->banco && $parcela->emprestimo->banco->wallet) {
                        if (!$this->podeProcessarParcela($parcela)) {
                            $countIgnoradas++;
                            Log::info("[recalcular:Parcelas] parcela={$parcela->id} | ignorada (já baixada)");
                            continue;
                        }

                        $txId = $parcela->identificador ?: null;
                        Log::info("[recalcular:Parcelas] Alterando cobrança | parcela={$parcela->id} | emprestimo={$parcela->emprestimo_id} | valor={$parcela->saldo} | txid={$txId}");

                        $response = $bcodexService->criarCobranca(
                            $parcela->saldo,
                            $parcela->emprestimo->banco->document,
                            $txId
                        );

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $newTxId = $response->json()['txid'] ?? null;

                            // Incrementar lucro_real com o valor dos juros/multa
                            $lucroRealAtual = (float) ($parcela->lucro_real ?? 0);
                            $parcela->lucro_real = $lucroRealAtual + $valorJuros;
                            
                            $parcela->saldo         = $novoValor;
                            $parcela->venc_real     = date('Y-m-d');
                            $parcela->identificador = $newTxId;
                            $parcela->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                            $parcela->save();

                            $countAtualizadas++;
                            Log::info("[recalcular:Parcelas] parcela={$parcela->id} | cobrança OK | novo_txid={$newTxId} | novo_saldo={$parcela->saldo}");
                        } else {
                            $countFalhas++;
                            Log::warning("[recalcular:Parcelas] parcela={$parcela->id} | cobrança FALHOU | txid={$txId}");
                            continue;
                        }
                    }

                    // Quitação
                    if ($parcela->emprestimo->quitacao) {
                        $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                        $parcela->emprestimo->quitacao->save();

                        $txId = $parcela->emprestimo->quitacao->identificador ?: null;
                        $resp = $bcodexService->criarCobranca(
                            $parcela->totalPendente(),
                            $parcela->emprestimo->banco->document,
                            $txId
                        );
                        Log::info("[recalcular:Parcelas] Quitação | parcela={$parcela->id} | quitacao={$parcela->emprestimo->quitacao->id} | txid={$txId}");

                        if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                            $parcela->emprestimo->quitacao->identificador = $resp->json()['txid'] ?? null;
                            $parcela->emprestimo->quitacao->chave_pix     = $resp->json()['pixCopiaECola'] ?? null;
                            $parcela->emprestimo->quitacao->saldo         = $parcela->totalPendente();
                            $parcela->emprestimo->quitacao->save();

                            Log::info("[recalcular:Parcelas] Quitação atualizada | parcela={$parcela->id}");
                        }
                    }

                    // Pagamento mínimo
                    if ($parcela->emprestimo->pagamentominimo) {
                        $parcela->emprestimo->pagamentominimo->valor += $valorJuros;
                        $parcela->emprestimo->pagamentominimo->save();

                        $txId = $parcela->emprestimo->pagamentominimo->identificador ?: null;
                        $resp = $bcodexService->criarCobranca(
                            $parcela->emprestimo->pagamentominimo->valor,
                            $parcela->emprestimo->banco->document,
                            $txId
                        );
                        Log::info("[recalcular:Parcelas] Pag. mínimo | parcela={$parcela->id} | valor={$parcela->emprestimo->pagamentominimo->valor} | txid={$txId}");

                        if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                            $parcela->emprestimo->pagamentominimo->identificador = $resp->json()['txid'] ?? null;
                            $parcela->emprestimo->pagamentominimo->chave_pix     = $resp->json()['pixCopiaECola'] ?? null;
                            $parcela->emprestimo->pagamentominimo->save();

                            Log::info("[recalcular:Parcelas] Pag. mínimo atualizado | parcela={$parcela->id}");
                        }
                    }

                    // Saldo pendente
                    if ($parcela->emprestimo->pagamentosaldopendente) {
                        $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->totalPendenteHoje();

                        if ($parcela->emprestimo->pagamentosaldopendente->valor <= 0) {
                            Log::info("[recalcular:Parcelas] Saldo pendente ZERO | parcela={$parcela->id}");
                        } else {
                            $parcela->emprestimo->pagamentosaldopendente->save();

                            $txId = $parcela->emprestimo->pagamentosaldopendente->identificador ?: null;
                            $resp = $bcodexService->criarCobranca(
                                $parcela->emprestimo->pagamentosaldopendente->valor,
                                $parcela->emprestimo->banco->document,
                                $txId
                            );
                            Log::info("[recalcular:Parcelas] Saldo pendente | parcela={$parcela->id} | valor={$parcela->emprestimo->pagamentosaldopendente->valor} | txid={$txId}");

                            if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                $parcela->emprestimo->pagamentosaldopendente->identificador = $resp->json()['txid'] ?? null;
                                $parcela->emprestimo->pagamentosaldopendente->chave_pix     = $resp->json()['pixCopiaECola'] ?? null;
                                $parcela->emprestimo->pagamentosaldopendente->save();

                                Log::info("[recalcular:Parcelas] Saldo pendente atualizado | parcela={$parcela->id}");
                            }
                        }
                    }

                } catch (\Throwable $e) {
                    $countFalhas++;
                    Log::error("[recalcular:Parcelas] ERRO parcela={$parcela->id} | {$e->getMessage()} | line={$e->getLine()}");
                }
            }

            Log::info("[recalcular:Parcelas] Slot {$slot} | chunk FINALIZADO");
        });

        $fim = microtime(true);
        $duracao = number_format($fim - $inicio, 2);

        $this->info("Resumo slot {$slot}: processadas={$countProcessadas}, atualizadas={$countAtualizadas}, ignoradas={$countIgnoradas}, falhas={$countFalhas}, tempo={$duracao}s");
        Log::info("[recalcular:Parcelas] Fim | slot={$slot} | processadas={$countProcessadas} | atualizadas={$countAtualizadas} | ignoradas={$countIgnoradas} | falhas={$countFalhas} | duracao={$duracao}s");

        return self::SUCCESS;
    }

    private static function podeProcessarParcela($parcela)
    {
        $parcelaPesquisa = Parcela::find($parcela->id);
        if ($parcelaPesquisa?->dt_baixa !== null) {
            Log::info("Parcela {$parcela->id} já baixada, não será processada novamente.");
            return false;
        }
        return true;
    }
}
