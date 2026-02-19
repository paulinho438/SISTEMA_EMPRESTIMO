<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulacaoEmprestimoRequest;
use App\Models\SimulacaoEmprestimo;
use Illuminate\Http\JsonResponse;

class SimulacaoEmprestimoController extends Controller
{
    /**
     * Salva a simulação no banco para relatórios futuros
     */
    public function store(StoreSimulacaoEmprestimoRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $inputs = $data['inputs'];
            $iof = $data['iof'];
            $totais = $data['totais'];

            $periodo = $inputs['periodo_amortizacao'];
            $periodoNorm = strtolower(preg_replace('/[^a-z]/', '', $periodo));
            if (in_array($periodoNorm, ['diario', 'diaria'])) {
                $periodoNorm = 'diario';
            } elseif (in_array($periodoNorm, ['semanal', 'semana'])) {
                $periodoNorm = 'semanal';
            } elseif (in_array($periodoNorm, ['mensal', 'mes'])) {
                $periodoNorm = 'mensal';
            } else {
                $periodoNorm = 'diario';
            }

            $companyId = $request->header('company-id');
            if (!$companyId && auth()->check() && auth()->user()->company_id) {
                $companyId = auth()->user()->company_id;
            }
            $companyId = $companyId ?: 1;

            $simulacao = SimulacaoEmprestimo::create([
                'valor_solicitado' => $inputs['valor_solicitado'],
                'periodo_amortizacao' => $periodoNorm,
                'modelo_amortizacao' => strtolower($inputs['modelo_amortizacao'] ?? 'price'),
                'quantidade_parcelas' => (int) $inputs['quantidade_parcelas'],
                'taxa_juros_mensal' => $inputs['taxa_juros_mensal'],
                'data_assinatura' => $inputs['data_assinatura'],
                'data_primeira_parcela' => $inputs['data_primeira_parcela'],
                'simples_nacional' => (bool) ($inputs['simples_nacional'] ?? false),
                'calcular_iof' => (bool) ($inputs['calcular_iof'] ?? true),
                'garantias' => $inputs['garantias'] ?? null,

                'iof_adicional' => $iof['adicional'],
                'iof_diario' => $iof['diario'],
                'iof_total' => $iof['total'],
                'valor_contrato' => $data['valor_contrato'],
                'parcela' => $data['parcela'],
                'total_parcelas' => $totais['total_parcelas'],
                'cet_mes' => $totais['cet_mes'] ?? null,
                'cet_ano' => $totais['cet_ano'] ?? null,
                'cronograma' => $data['cronograma'],

                'client_id' => $request->input('client_id'),
                'banco_id' => $request->input('banco_id'),
                'costcenter_id' => $request->input('costcenter_id'),
                'user_id' => auth()->id(),
                'company_id' => $companyId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Simulação salva com sucesso.',
                'id' => $simulacao->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar simulação.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
