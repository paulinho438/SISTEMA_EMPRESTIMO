<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RelatorioLucroRealService;
use App\Models\CustomLog;
use App\Http\Resources\RelatorioLucroRealResource;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RelatorioLucroRealExport;
use Barryvdh\DomPDF\Facade\Pdf;

class RelatorioLucroRealController extends Controller
{
    protected $custom_log;
    protected $relatorioLucroRealService;

    public function __construct(CustomLog $custom_log, RelatorioLucroRealService $relatorioLucroRealService)
    {
        $this->custom_log = $custom_log;
        $this->relatorioLucroRealService = $relatorioLucroRealService;
    }

    /**
     * Relatório de lucro real mensal
     */
    public function relatorioMensal(Request $request)
    {
        $companyId = $request->header('company-id');
        $mes = $request->query('mes', date('Y-m'));

        // Se mes não contém ano, usa o ano atual
        if (strlen($mes) === 2 || (strlen($mes) === 7 && !str_contains($mes, '-'))) {
            $ano = $request->query('ano', date('Y'));
            $mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        }

        $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório de lucro real mensal',
            'operation' => 'index'
        ]);

        $relatorio = $this->relatorioLucroRealService->gerarRelatorioLucroReal($companyId, $dataInicio, $dataFim);

        return response()->json(new RelatorioLucroRealResource($relatorio));
    }

    /**
     * Relatório de lucro real anual
     */
    public function relatorioAnual(Request $request)
    {
        $companyId = $request->header('company-id');
        $ano = $request->query('ano', date('Y'));

        $dataInicio = Carbon::createFromDate($ano, 1, 1)->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::createFromDate($ano, 12, 31)->endOfDay()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório de lucro real anual',
            'operation' => 'index'
        ]);

        $relatorio = $this->relatorioLucroRealService->gerarRelatorioLucroReal($companyId, $dataInicio, $dataFim);

        return response()->json(new RelatorioLucroRealResource($relatorio));
    }

    /**
     * Relatório de lucro real por período customizado
     */
    public function relatorioPeriodo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $companyId = $request->header('company-id');
        $dataInicio = Carbon::parse($request->data_inicio)->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::parse($request->data_fim)->endOfDay()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório de lucro real por período',
            'operation' => 'index'
        ]);

        $relatorio = $this->relatorioLucroRealService->gerarRelatorioLucroReal($companyId, $dataInicio, $dataFim);

        return response()->json(new RelatorioLucroRealResource($relatorio));
    }

    /**
     * Exportar relatório de lucro real para Excel
     */
    public function exportarExcel(Request $request)
    {
        $companyId = $request->header('company-id');
        $tipo = $request->query('tipo', 'mensal'); // mensal, anual, periodo
        $mes = $request->query('mes', date('Y-m'));
        $ano = $request->query('ano', date('Y'));
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');

        if ($tipo === 'mensal') {
            if (strlen($mes) === 2 || (strlen($mes) === 7 && !str_contains($mes, '-'))) {
                $mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
            }
            $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');
            $nomeArquivo = 'relatorio_lucro_real_mensal_' . Carbon::parse($mes)->format('Y_m');
        } elseif ($tipo === 'anual') {
            $dataInicio = Carbon::createFromDate($ano, 1, 1)->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::createFromDate($ano, 12, 31)->endOfDay()->format('Y-m-d');
            $nomeArquivo = 'relatorio_lucro_real_anual_' . $ano;
        } else {
            $dataInicio = Carbon::parse($dataInicio)->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($dataFim)->endOfDay()->format('Y-m-d');
            $nomeArquivo = 'relatorio_lucro_real_periodo_' . $dataInicio . '_a_' . $dataFim;
        }

        $relatorio = $this->relatorioLucroRealService->gerarRelatorioLucroReal($companyId, $dataInicio, $dataFim);

        return Excel::download(new RelatorioLucroRealExport($relatorio), $nomeArquivo . '.xlsx');
    }

    /**
     * Exportar relatório de lucro real para PDF
     */
    public function exportarPDF(Request $request)
    {
        $companyId = $request->header('company-id');
        $tipo = $request->query('tipo', 'mensal');
        $mes = $request->query('mes', date('Y-m'));
        $ano = $request->query('ano', date('Y'));
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');

        if ($tipo === 'mensal') {
            if (strlen($mes) === 2 || (strlen($mes) === 7 && !str_contains($mes, '-'))) {
                $mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
            }
            $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');
            $nomeArquivo = 'relatorio_lucro_real_mensal_' . Carbon::parse($mes)->format('Y_m');
        } elseif ($tipo === 'anual') {
            $dataInicio = Carbon::createFromDate($ano, 1, 1)->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::createFromDate($ano, 12, 31)->endOfDay()->format('Y-m-d');
            $nomeArquivo = 'relatorio_lucro_real_anual_' . $ano;
        } else {
            $dataInicio = Carbon::parse($dataInicio)->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($dataFim)->endOfDay()->format('Y-m-d');
            $nomeArquivo = 'relatorio_lucro_real_periodo_' . $dataInicio . '_a_' . $dataFim;
        }

        $relatorio = $this->relatorioLucroRealService->gerarRelatorioLucroReal($companyId, $dataInicio, $dataFim);

        $pdf = Pdf::loadView('relatorio-lucro-real-pdf', ['relatorio' => $relatorio]);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($nomeArquivo . '.pdf');
    }
}
