<?php

namespace App\Console\Commands;

use App\Models\Banco;
use App\Models\Emprestimo;
use App\Services\MigrarEmprestimoBancoService;
use Illuminate\Console\Command;

class MigrarEmprestimosBancoAutomaticoParaXgate extends Command
{
    protected $signature = 'emprestimos:migrar-banco-automatico-para-xgate
                            {--origem=Banco automático : Nome exato do banco de origem na tabela bancos}
                            {--destino=XGATE : Nome exato do banco XGATE na mesma empresa do empréstimo}
                            {--company= : Filtrar por company_id (opcional)}
                            {--dry-run : Apenas lista os empréstimos que seriam migrados}
                            {--limit= : Processar no máximo N empréstimos (útil para teste)}';

    protected $description = 'Migra empréstimos do banco "Banco automático" para o banco XGATE da mesma empresa, do mais recente ao mais antigo (created_at desc, id desc).';

    public function handle(MigrarEmprestimoBancoService $migrarEmprestimoBancoService): int
    {
        $origemNome = (string) $this->option('origem');
        $destinoNome = (string) $this->option('destino');
        $companyId = $this->option('company') !== null && $this->option('company') !== ''
            ? (int) $this->option('company')
            : null;
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null && $this->option('limit') !== ''
            ? max(1, (int) $this->option('limit'))
            : null;

        $baseQuery = Emprestimo::query()
            ->whereHas('banco', function ($q) use ($origemNome) {
                $q->where('name', $origemNome);
            })
            ->with('banco')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($companyId !== null) {
            $baseQuery->where('company_id', $companyId);
        }

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            $this->warn('Nenhum empréstimo encontrado com banco de origem "' . $origemNome . '".');

            return self::SUCCESS;
        }

        $this->info('Encontrados ' . $total . ' empréstimo(s) com banco "' . $origemNome . '". Ordem: mais recente → mais antigo.');

        if ($dryRun) {
            $q = (clone $baseQuery)->select('id');
            if ($limit !== null) {
                $q->limit($limit);
            }
            foreach ($q->cursor() as $row) {
                $this->line('  — emprestimo_id=' . $row->id);
            }
            $this->comment('Dry-run: nenhuma alteração feita.');

            return self::SUCCESS;
        }

        $toProcess = $limit === null ? $total : min($total, $limit);
        $bar = $this->output->createProgressBar($toProcess);
        $bar->start();

        $ok = 0;
        $fail = 0;
        $skipped = 0;
        $processed = 0;

        foreach ($baseQuery->cursor() as $emprestimo) {
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $bancoDestino = Banco::query()
                ->where('company_id', $emprestimo->company_id)
                ->where('name', $destinoNome)
                ->first();

            if (!$bancoDestino) {
                $this->newLine();
                $this->warn('Emprestimo ' . $emprestimo->id . ': banco destino "' . $destinoNome . '" não encontrado para company_id=' . $emprestimo->company_id . '.');
                $skipped++;
                $bar->advance();
                $processed++;

                continue;
            }

            $result = $migrarEmprestimoBancoService->migrar((int) $emprestimo->id, (int) $bancoDestino->id);

            if ($result['success']) {
                $ok++;
            } else {
                $this->newLine();
                $this->error('Emprestimo ' . $emprestimo->id . ': ' . $result['message']);
                $fail++;
            }

            $bar->advance();
            $processed++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Concluído: {$ok} migrados, {$fail} falhas, {$skipped} ignorados (sem banco destino na empresa).");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
