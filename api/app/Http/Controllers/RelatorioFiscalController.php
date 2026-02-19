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
        $tipo = $request->query('tipo', 'presumido'); // 'presumido' ou 'proporcional'

        // Normalizar mês: se for número (1-12), montar YYYY-MM
        if (strlen((string) $mes) <= 2 || !str_contains((string) $mes, '-')) {
            $mes = $ano . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        }

        $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório fiscal mensal',
            'operation' => 'index'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim, $tipo);

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
        $tipo = $request->query('tipo', 'presumido'); // 'presumido' ou 'proporcional'

        $dataInicio = Carbon::createFromDate($ano, 1, 1)->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::createFromDate($ano, 12, 31)->endOfDay()->format('Y-m-d');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório fiscal anual',
            'operation' => 'index'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim, $tipo);

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
        $tipo = $request->query('tipo', 'presumido'); // 'presumido' ou 'proporcional'

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou o relatório fiscal do período ' . $dataInicio . ' a ' . $dataFim,
            'operation' => 'index'
        ]);

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim, $tipo);

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
            $mes = $request->query('mes', date('Y-m'));
            $ano = $request->query('ano', date('Y'));
            if (strlen((string) $mes) <= 2 || !str_contains((string) $mes, '-')) {
                $mes = $ano . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
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

        $tipo = $request->query('tipo', 'presumido');

        return Excel::download(
            new RelatorioFiscalExport($companyId, $dataInicio, $dataFim, $tipo),
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
            $mes = $request->query('mes', date('Y-m'));
            $ano = $request->query('ano', date('Y'));
            if (strlen((string) $mes) <= 2 || !str_contains((string) $mes, '-')) {
                $mes = $ano . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
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

        $tipo = $request->query('tipo', 'presumido');
        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim, $tipo);
        $company = Company::find($companyId);

        $nomeArquivo = 'relatorio-fiscal-' . $dataInicio . '-a-' . $dataFim . '.pdf';

        $pdf = Pdf::loadView('relatorio-fiscal-pdf', [
            'relatorio' => $relatorio,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($nomeArquivo);
    }

    /**
     * Exportar Sumário dos Contratos Ativos em CSV
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportarCSV(Request $request)
    {
        $companyId = $request->header('company-id');
        $mes = $request->query('mes', date('Y-m'));
        $ano = $request->query('ano', date('Y'));

        if (strlen((string) $mes) <= 2 || !str_contains((string) $mes, '-')) {
            $mes = $ano . '-' . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        }

        $dataInicio = Carbon::parse($mes . '-01')->startOfDay()->format('Y-m-d');
        $dataFim = Carbon::parse($mes . '-01')->endOfMonth()->format('Y-m-d');

        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal($companyId, $dataInicio, $dataFim, 'presumido');

        $linhas = [
            ['Métrica', 'Valor (R$)'],
            ['Total de Recebimentos no Mês', number_format($relatorio['total_recebimentos'] ?? 0, 2, ',', '.')],
            ['Total de Amortização', number_format($relatorio['total_amortizacao'] ?? 0, 2, ',', '.')],
            ['Total de Juros (Remuneratórios e Mora)', number_format($relatorio['total_juros'] ?? 0, 2, ',', '.')],
            ['Descontos aplicados', number_format($relatorio['descontos_aplicados'] ?? 0, 2, ',', '.')],
        ];

        if ($relatorio['mes_trimestral'] ?? false) {
            $linhas[] = ['IRPJ', number_format($relatorio['irpj']['total'] ?? 0, 2, ',', '.')];
            $linhas[] = ['Adicional do IRPJ', number_format($relatorio['irpj']['adicional'] ?? 0, 2, ',', '.')];
            $linhas[] = ['CSLL', number_format($relatorio['csll'] ?? 0, 2, ',', '.')];
        }

        $linhas[] = ['COFINS', number_format($relatorio['cofins'] ?? 0, 2, ',', '.')];
        $linhas[] = ['PIS', number_format($relatorio['pis'] ?? 0, 2, ',', '.')];
        $linhas[] = ['IOF Total (operações feitas este mês)', number_format($relatorio['iof_total_mes'] ?? 0, 2, ',', '.')];
        $linhas[] = ['Valor dos títulos atrasados (vencidos neste mês)', number_format($relatorio['titulos_atrasados'] ?? 0, 2, ',', '.')];

        $csv = implode("\n", array_map(function ($linha) {
            return '"' . implode('";"', $linha) . '"';
        }, $linhas));

        $csv = "\xEF\xBB\xBF" . $csv; // BOM UTF-8

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sumario-contratos-ativos-' . $mes . '.csv"',
        ]);
    }
}

