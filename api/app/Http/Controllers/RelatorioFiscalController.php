<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalculoFiscalService;
use App\Models\ConfiguracaoFiscal;
use App\Models\CustomLog;
use App\Models\Company;
use App\Http\Resources\RelatorioFiscalResource;
use App\Exports\RelatorioFiscalExport;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class RelatorioFiscalController extends Controller
{
    protected $custom_log;
    protected $calculoFiscalService;

    public function __construct(CustomLog $custom_log, CalculoFiscalService $calculoFiscalService)
    {
        $this->custom_log = $custom_log;
        $this->calculoFiscalService = $calculoFiscalService;
    }

    /**
     * Relatório fiscal mensal
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function relatorioMensal(Request $request)
    {
        $companyId = $request->header('company-id');
        $mes = $request->query('mes', date('Y-m'));
        $ano = $request->query('ano', date('Y'));

        // Se mes não contém ano, usa o ano informado ou atual
        if (strlen($mes) === 2 || (strlen($mes) === 7 && !str_contains($mes, '-'))) {
            $mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
        }

        $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório fiscal mensal',
            'operation' => 'index'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim);

        return response()->json(new RelatorioFiscalResource($relatorio));
    }

    /**
     * Relatório fiscal anual
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function relatorioAnual(Request $request)
    {
        $companyId = $request->header('company-id');
        $ano = $request->query('ano', date('Y'));

        $dataInicio = Carbon::createFromDate($ano, 1, 1)->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::createFromDate($ano, 12, 31)->endOfDay()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório fiscal anual',
            'operation' => 'index'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim);

        return response()->json(new RelatorioFiscalResource($relatorio));
    }

    /**
     * Relatório fiscal por período customizado
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório fiscal do período ' . $dataInicio . ' a ' . $dataFim,
            'operation' => 'index'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim);

        return response()->json(new RelatorioFiscalResource($relatorio));
    }

    /**
     * Configurar percentual de presunção e outras configurações fiscais
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function configurarPercentualPresuncao(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'percentual_presuncao' => 'required|numeric|min:0|max:100',
            'aliquota_irpj' => 'nullable|numeric|min:0|max:100',
            'aliquota_irpj_adicional' => 'nullable|numeric|min:0|max:100',
            'aliquota_csll' => 'nullable|numeric|min:0|max:100',
            'faixa_isencao_irpj' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $companyId = $request->header('company-id');

        $config = ConfiguracaoFiscal::updateOrCreate(
            ['company_id' => $companyId],
            [
                'percentual_presuncao' => $request->percentual_presuncao,
                'aliquota_irpj' => $request->aliquota_irpj ?? 15.00,
                'aliquota_irpj_adicional' => $request->aliquota_irpj_adicional ?? 10.00,
                'aliquota_csll' => $request->aliquota_csll ?? 9.00,
                'faixa_isencao_irpj' => $request->faixa_isencao_irpj ?? 20000.00,
            ]
        );

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' configurou o percentual de presunção para ' . $request->percentual_presuncao . '%',
            'operation' => 'update'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuração fiscal atualizada com sucesso',
            'data' => $config
        ]);
    }

    /**
     * Exportar relatório em Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarExcel(Request $request)
    {
        $companyId = $request->header('company-id');
        
        // Determinar período baseado nos parâmetros
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        
        if (!$dataInicio || !$dataFim) {
            // Se não informado, usa mês atual
            $mes = $request->query('mes', date('Y-m'));
            $ano = $request->query('ano', date('Y'));
            
            if (strlen($mes) === 2 || (strlen($mes) === 7 && !str_contains($mes, '-'))) {
                $mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
            }
            
            $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');
        } else {
            $dataInicio = Carbon::parse($dataInicio)->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($dataFim)->endOfDay()->format('Y-m-d');
        }

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' exportou o relatório fiscal em Excel',
            'operation' => 'export'
        ]);

        $nomeArquivo = 'relatorio-fiscal-' . $dataInicio . '-a-' . $dataFim . '.xlsx';

        return Excel::download(
            new RelatorioFiscalExport($companyId, $dataInicio, $dataFim),
            $nomeArquivo
        );
    }

    /**
     * Exportar relatório em PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportarPDF(Request $request)
    {
        $companyId = $request->header('company-id');
        
        // Determinar período baseado nos parâmetros
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        
        if (!$dataInicio || !$dataFim) {
            // Se não informado, usa mês atual
            $mes = $request->query('mes', date('Y-m'));
            $ano = $request->query('ano', date('Y'));
            
            if (strlen($mes) === 2 || (strlen($mes) === 7 && !str_contains($mes, '-'))) {
                $mes = $ano . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);
            }
            
            $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');
        } else {
            $dataInicio = Carbon::parse($dataInicio)->startOfDay()->format('Y-m-d');
            $dataFim = Carbon::parse($dataFim)->endOfDay()->format('Y-m-d');
        }

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' exportou o relatório fiscal em PDF',
            'operation' => 'export'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim);
        $company = Company::find($companyId);

        $nomeArquivo = 'relatorio-fiscal-' . $dataInicio . '-a-' . $dataFim . '.pdf';

        $pdf = Pdf::loadView('relatorio-fiscal-pdf', [
            'relatorio' => $relatorio,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($nomeArquivo);
    }
}

