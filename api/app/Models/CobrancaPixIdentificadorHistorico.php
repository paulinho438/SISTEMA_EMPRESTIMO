<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CobrancaPixIdentificadorHistorico extends Model
{
    protected $table = 'cobranca_pix_identificador_historicos';

    protected $fillable = [
        'identificador',
        'tipo_entidade',
        'entidade_id',
        'emprestimo_id',
        'banco_id',
        'company_id',
        'valor',
        'reference_interno',
        'provedor',
    ];

    /**
     * Registra o identificador retornado pela API (XGate/APIX) vinculado à entidade da cobrança.
     * Permite ao webhook localizar a parcela/quitação/etc. mesmo após novas gerações de PIX.
     */
    public static function registrarCobranca(
        string $provedor,
        ?string $identificador,
        $entidade,
        ?float $valor = null,
        ?string $referenceInterno = null
    ): void {
        if ($identificador === null || $identificador === '') {
            return;
        }

        $tipo = self::tipoEntidadeDeModel($entidade);
        if ($tipo === null) {
            return;
        }

        $emprestimoId = self::emprestimoIdDeEntidade($entidade);
        $bancoId = self::bancoIdDeEntidade($entidade);
        $companyId = self::companyIdDeEntidade($entidade);

        try {
            self::create([
                'identificador' => $identificador,
                'tipo_entidade' => $tipo,
                'entidade_id' => $entidade->id,
                'emprestimo_id' => $emprestimoId,
                'banco_id' => $bancoId,
                'company_id' => $companyId,
                'valor' => $valor,
                'reference_interno' => $referenceInterno,
                'provedor' => $provedor,
            ]);
        } catch (\Throwable $e) {
            Log::channel('xgate')->warning('CobrancaPixIdentificadorHistorico: não foi possível registrar', [
                'identificador' => $identificador,
                'tipo' => $tipo,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function tipoEntidadeDeModel($entidade): ?string
    {
        if ($entidade instanceof Parcela) {
            return 'parcela';
        }
        if ($entidade instanceof Quitacao) {
            return 'quitacao';
        }
        if ($entidade instanceof PagamentoSaldoPendente) {
            return 'pagamento_saldo_pendente';
        }
        if ($entidade instanceof PagamentoMinimo) {
            return 'pagamento_minimo';
        }
        if ($entidade instanceof Locacao) {
            return 'locacao';
        }
        if ($entidade instanceof PagamentoPersonalizado) {
            return 'pagamento_personalizado';
        }
        if ($entidade instanceof Deposito) {
            return 'deposito';
        }

        return null;
    }

    private static function emprestimoIdDeEntidade($entidade): ?int
    {
        if (isset($entidade->emprestimo_id)) {
            return (int) $entidade->emprestimo_id;
        }

        return null;
    }

    private static function bancoIdDeEntidade($entidade): ?int
    {
        if ($entidade instanceof Deposito) {
            return $entidade->banco_id ? (int) $entidade->banco_id : null;
        }
        if (isset($entidade->emprestimo) && $entidade->emprestimo && $entidade->emprestimo->banco_id) {
            return (int) $entidade->emprestimo->banco_id;
        }

        return null;
    }

    private static function companyIdDeEntidade($entidade): ?int
    {
        if ($entidade instanceof Locacao) {
            return $entidade->company_id ? (int) $entidade->company_id : null;
        }
        if ($entidade instanceof Deposito) {
            return $entidade->company_id !== null ? (int) $entidade->company_id : null;
        }
        if (isset($entidade->emprestimo) && $entidade->emprestimo && $entidade->emprestimo->company_id !== null) {
            return (int) $entidade->emprestimo->company_id;
        }

        return null;
    }
}
