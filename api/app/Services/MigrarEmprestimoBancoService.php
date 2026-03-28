<?php

namespace App\Services;

use App\Jobs\ProcessarPixApixJob;
use App\Jobs\ProcessarPixJob;
use App\Jobs\ProcessarPixXgateJob;
use App\Models\Banco;
use App\Models\Contaspagar;
use App\Models\Contasreceber;
use App\Models\Emprestimo;
use App\Models\PagamentoMinimo;
use App\Models\PagamentoSaldoPendente;
use App\Models\Parcela;
use App\Models\Quitacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrarEmprestimoBancoService
{
    public function __construct(
        protected BcodexService $bcodexService
    ) {
    }

    /**
     * Define qual integração de PIX usar. Prioriza bank_type (xgate, apix, etc.) sobre a flag wallet,
     * para não tratar XGATE/APIX como Bcodex quando o cadastro tiver wallet=1 por engano ou legado.
     */
    private function tipoBancoParaCobrancaPix(Banco $banco): string
    {
        $tipo = $banco->bank_type ?? 'normal';
        if (in_array($tipo, ['apix', 'xgate', 'velana', 'cora'], true)) {
            return $tipo;
        }
        if ($banco->wallet) {
            return 'bcodex';
        }

        return $tipo;
    }

    /**
     * Migra um empréstimo para outro banco (mesma regra do endpoint migrar-banco).
     *
     * @return array{success: bool, message: string, emprestimo?: Emprestimo}
     */
    public function migrar(int $emprestimoId, int $novoBancoId): array
    {
        $emprestimo = Emprestimo::with(['banco', 'parcelas.contasreceber', 'quitacao', 'pagamentominimo', 'pagamentosaldopendente'])
            ->find($emprestimoId);

        if (!$emprestimo) {
            return ['success' => false, 'message' => 'Empréstimo não encontrado.'];
        }

        $novoBancoId = (int) $novoBancoId;
        $bancoAtualId = (int) $emprestimo->banco_id;

        if ($novoBancoId === $bancoAtualId) {
            return ['success' => false, 'message' => 'O banco destino deve ser diferente do banco atual.'];
        }

        $bancoDestino = Banco::find($novoBancoId);
        if (!$bancoDestino) {
            return ['success' => false, 'message' => 'Banco destino não encontrado.'];
        }

        $bankTypesPermitidos = ['bcodex', 'apix', 'xgate', 'velana', 'cora'];
        $bankType = $this->tipoBancoParaCobrancaPix($bancoDestino);
        if (!in_array($bankType, $bankTypesPermitidos, true)) {
            return ['success' => false, 'message' => 'O banco destino deve ser do tipo Bcodex, APIX, XGate, Velana ou Cora.'];
        }

        $temParcelaPendente = $emprestimo->parcelas->contains(fn ($p) => $p->dt_baixa === null);
        if (!$temParcelaPendente) {
            return ['success' => false, 'message' => 'Não é possível migrar empréstimo quitado.'];
        }

        if ((int) $bancoDestino->company_id !== (int) $emprestimo->company_id) {
            return ['success' => false, 'message' => 'O banco destino deve ser da mesma empresa do empréstimo.'];
        }

        DB::beginTransaction();
        try {
            Emprestimo::where('id', $emprestimoId)->update(['banco_id' => $novoBancoId]);
            Contaspagar::where('emprestimo_id', $emprestimoId)->update(['banco_id' => $novoBancoId]);

            $parcelaIds = $emprestimo->parcelas->pluck('id')->toArray();
            if (!empty($parcelaIds)) {
                Contasreceber::whereIn('parcela_id', $parcelaIds)->update(['banco_id' => $novoBancoId]);
            }

            Parcela::where('emprestimo_id', $emprestimoId)->update([
                'identificador' => null,
                'chave_pix' => null,
                'ult_dt_geracao_pix' => null,
            ]);

            Quitacao::where('emprestimo_id', $emprestimoId)->update([
                'identificador' => null,
                'chave_pix' => null,
                'ult_dt_geracao_pix' => null,
            ]);

            PagamentoMinimo::where('emprestimo_id', $emprestimoId)->update([
                'identificador' => null,
                'chave_pix' => null,
                'ult_dt_geracao_pix' => null,
            ]);

            PagamentoSaldoPendente::where('emprestimo_id', $emprestimoId)->update([
                'identificador' => null,
                'chave_pix' => null,
                'ult_dt_geracao_pix' => null,
            ]);

            DB::commit();

            $emprestimo->refresh();
            $emprestimo->load('banco');

            if ($bankType === 'apix') {
                ProcessarPixApixJob::dispatch($emprestimo, []);
            } elseif ($bankType === 'xgate') {
                ProcessarPixXgateJob::dispatch($emprestimo, []);
            } elseif ($bankType === 'bcodex') {
                ProcessarPixJob::dispatch($emprestimo, $this->bcodexService, [], true);
            }

            return [
                'success' => true,
                'message' => 'Empréstimo migrado para o banco ' . ($bancoDestino->name ?? 'destino') . ' com sucesso.',
                'emprestimo' => $emprestimo,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MigrarEmprestimoBancoService: ' . $e->getMessage(), [
                'emprestimo_id' => $emprestimoId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao migrar empréstimo para o novo banco: ' . $e->getMessage(),
            ];
        }
    }
}
