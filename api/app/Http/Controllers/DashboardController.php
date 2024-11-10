<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Juros;
use App\Models\CustomLog;
use App\Models\Client;
use App\Models\Emprestimo;

use App\Http\Resources\EmprestimoResource;

use App\Http\Resources\JurosResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class DashboardController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log)
    {
        $this->custom_log = $custom_log;
    }

    public function infoConta(Request $request)
    {
        $companyId = $request->header('company-id');
        $emprestimos = Emprestimo::where('company_id', $companyId)->get();

        $totalEmprestimos = $emprestimos->count();
        $totalEmprestimosAtrasados = 0;
        $totalEmprestimosPagos = 0;
        $totalEmprestimosVencidos = 0;
        $totalEmprestimosEmDias = 0;
        $totalEmprestimosMuitoAtrasados = 0;
        $totalJaRecebido = 0;

        foreach ($emprestimos as $emprestimo) {
            $totalJaRecebido += $emprestimo->total_pago;

            $status = $this->getStatus($emprestimo);

            switch ($status) {
                case 'Atrasado':
                    $totalEmprestimosAtrasados++;
                    break;
                case 'Pago':
                    $totalEmprestimosPagos++;
                    break;
                case 'Vencido':
                    $totalEmprestimosVencidos++;
                    break;
                case 'Em Dias':
                    $totalEmprestimosEmDias++;
                    break;
                case 'Muito Atrasado':
                    $totalEmprestimosMuitoAtrasados++;
                    break;
            }
        }
        $t = Client::where('company_id', $companyId)->count();

        return [
            'total_clientes' => $t,
            'total_emprestimos' => $totalEmprestimos,
            'total_emprestimos_atrasados' => $totalEmprestimosAtrasados,
            'total_emprestimos_pagos' => $totalEmprestimosPagos,
            'total_emprestimos_vencidos' => $totalEmprestimosVencidos,
            'total_emprestimos_em_dias' => $totalEmprestimosEmDias,
            'total_emprestimos_muito_atrasados' => $totalEmprestimosMuitoAtrasados
        ];
    }

    private function getStatus($emprestimo)
    {
        $status = 'Em Dias'; // Padrão
        $qtParcelas = count($emprestimo->parcelas);
        $qtPagas = 0;
        $qtAtrasadas = 0;

        foreach ($emprestimo->parcelas as $parcela) {
            if ($parcela->atrasadas > 0 && $parcela->saldo > 0) {
                $qtAtrasadas++;
            }
        }

        if ($qtAtrasadas > 0) {
            $status = 'Muito Atrasado';

            if ($qtAtrasadas == $qtParcelas) {
                $status = 'Vencido';
            }
        }

        foreach ($emprestimo->parcelas as $parcela) {
            if ($parcela->dt_baixa != null) {
                $qtPagas++;
            }
        }

        if ($qtParcelas == $qtPagas) {
            $status = 'Pago';
        }

        return $status;
    }

    private function isMaiorQuatro($qtAtrasadas, $qtParcelas)
    {
        return $qtAtrasadas > 4;
    }


}
