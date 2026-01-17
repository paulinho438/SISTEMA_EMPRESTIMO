<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Emprestimo;
use App\Models\Parcela;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AtualizarLucroRealEmprestimos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emprestimo:atualizar-lucro-real 
                            {--id= : ID do empréstimo específico}
                            {--company-id= : ID da empresa (opcional)}
                            {--forcar : Força atualização mesmo se lucro_real já estiver preenchido}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o lucro_real das parcelas dos empréstimos (calcula lucro_total / num_parcelas)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $emprestimoId = $this->option('id');
        $companyId = $this->option('company-id');
        $forcar = $this->option('forcar');

        $this->info('=' . str_repeat('=', 70));
        $this->info('Atualização de Lucro Real das Parcelas');
        $this->info('=' . str_repeat('=', 70));
        $this->newLine();

        try {
            if ($emprestimoId) {
                // Atualizar um empréstimo específico
                $emprestimo = Emprestimo::find($emprestimoId);
                
                if (!$emprestimo) {
                    $this->error("Empréstimo #{$emprestimoId} não encontrado!");
                    return Command::FAILURE;
                }

                if ($companyId && $emprestimo->company_id != $companyId) {
                    $this->error("Empréstimo #{$emprestimoId} não pertence à empresa #{$companyId}!");
                    return Command::FAILURE;
                }

                $this->processarEmprestimo($emprestimo, $forcar);
            } else {
                // Atualizar todos os empréstimos (ou de uma empresa específica)
                $query = Emprestimo::with('parcelas');
                
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }

                $totalEmprestimos = $query->count();
                $this->info("Total de empréstimos a processar: {$totalEmprestimos}");
                $this->newLine();

                if ($totalEmprestimos == 0) {
                    $this->warn('Nenhum empréstimo encontrado para atualizar.');
                    return Command::SUCCESS;
                }

                $bar = $this->output->createProgressBar($totalEmprestimos);
                $bar->start();

                $totalAtualizados = 0;
                $totalParcelasAtualizadas = 0;

                $query->chunk(100, function ($emprestimos) use (&$totalAtualizados, &$totalParcelasAtualizadas, $bar, $forcar) {
                    foreach ($emprestimos as $emprestimo) {
                        $parcelasAtualizadas = $this->processarEmprestimo($emprestimo, $forcar, false);
                        $totalParcelasAtualizadas += $parcelasAtualizadas;
                        
                        if ($parcelasAtualizadas > 0) {
                            $totalAtualizados++;
                        }
                        
                        $bar->advance();
                    }
                });

                $bar->finish();
                $this->newLine(2);
                $this->info('=' . str_repeat('=', 70));
                $this->info("Processamento concluído!");
                $this->info("  - Empréstimos atualizados: {$totalAtualizados}/{$totalEmprestimos}");
                $this->info("  - Total de parcelas atualizadas: {$totalParcelasAtualizadas}");
                $this->info('=' . str_repeat('=', 70));
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erro ao processar: " . $e->getMessage());
            Log::error('Erro ao atualizar lucro_real: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Processa um empréstimo específico
     * 
     * @param Emprestimo $emprestimo
     * @param bool $forcar Força atualização mesmo se já tiver lucro_real
     * @param bool $mostrarLog Se deve mostrar logs detalhados
     * @return int Número de parcelas atualizadas
     */
    private function processarEmprestimo(Emprestimo $emprestimo, bool $forcar = false, bool $mostrarLog = true): int
    {
        $parcelas = $emprestimo->parcelas;
        $numParcelas = $parcelas->count();
        $lucroTotal = (float) $emprestimo->lucro;

        if ($numParcelas == 0) {
            if ($mostrarLog) {
                $this->warn("  Empréstimo #{$emprestimo->id} não possui parcelas");
            }
            return 0;
        }

        if ($lucroTotal == 0) {
            if ($mostrarLog) {
                $this->warn("  Empréstimo #{$emprestimo->id} tem lucro = 0, pulando...");
            }
            return 0;
        }

        $lucroPorParcela = round($lucroTotal / $numParcelas, 2);

        if ($mostrarLog) {
            $this->info("Empréstimo #{$emprestimo->id}:");
            $this->info("  - Lucro Total: R$ " . number_format($lucroTotal, 2, ',', '.'));
            $this->info("  - Número de Parcelas: {$numParcelas}");
            $this->info("  - Lucro por Parcela: R$ " . number_format($lucroPorParcela, 2, ',', '.'));
            $this->newLine();
        }

        $parcelasAtualizadas = 0;
        $parcelasIgnoradas = 0;

        DB::beginTransaction();
        try {
            foreach ($parcelas as $parcela) {
                $lucroRealAtual = (float) ($parcela->lucro_real ?? 0);
                
                if ($forcar || $lucroRealAtual == 0) {
                    $parcela->lucro_real = $lucroPorParcela;
                    $parcela->save();
                    $parcelasAtualizadas++;

                    if ($mostrarLog) {
                        $this->line("  ✓ Parcela #{$parcela->parcela} (ID: {$parcela->id}) atualizada: R$ " . number_format($lucroPorParcela, 2, ',', '.'));
                    }

                    Log::info("Lucro_real atualizado", [
                        'emprestimo_id' => $emprestimo->id,
                        'parcela_id' => $parcela->id,
                        'parcela' => $parcela->parcela,
                        'lucro_real_anterior' => $lucroRealAtual,
                        'lucro_real_novo' => $lucroPorParcela
                    ]);
                } else {
                    $parcelasIgnoradas++;
                    if ($mostrarLog) {
                        $this->line("  ⊘ Parcela #{$parcela->parcela} (ID: {$parcela->id}) já possui lucro_real = R$ " . number_format($lucroRealAtual, 2, ',', '.') . " (ignorada)");
                    }
                }
            }

            DB::commit();

            if ($mostrarLog) {
                $this->newLine();
                $this->info("  Resultado:");
                $this->info("    - Parcelas atualizadas: {$parcelasAtualizadas}");
                if ($parcelasIgnoradas > 0) {
                    $this->info("    - Parcelas ignoradas: {$parcelasIgnoradas}");
                }
                $this->newLine();
            }

            return $parcelasAtualizadas;

        } catch (\Exception $e) {
            DB::rollBack();
            if ($mostrarLog) {
                $this->error("  Erro ao atualizar parcelas: " . $e->getMessage());
            }
            Log::error("Erro ao atualizar lucro_real do empréstimo #{$emprestimo->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
