<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulacaoEmprestimoRequest;
use App\Models\SimulacaoEmprestimo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulacaoEmprestimoController extends Controller
{
    /**
     * Lista contratos (simulações) com filtro opcional por situação
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company-id') ?: (auth()->user()->company_id ?? 1);

        $query = SimulacaoEmprestimo::with('client')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('situacao')) {
            $situacao = $request->get('situacao');
            $query->where('situacao', $situacao);
        }

        if ($request->filled('q')) {
            $q = $request->get('q');
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('client', function ($c) use ($q) {
                    $c->where('razao_social', 'like', "%{$q}%")
                        ->orWhere('nome_completo', 'like', "%{$q}%");
                });
                if (is_numeric($q)) {
                    $qry->orWhere('id', $q);
                }
            });
        }

        $perPage = min((int) $request->get('per_page', 10), 50);
        $paginated = $query->paginate($perPage);

        $items = $paginated->getCollection()->map(function ($s) {
            $ano = $s->created_at->format('Y');
            $numero = str_pad((string) $s->id, 6, '0', STR_PAD_LEFT);
            return [
                'id' => $s->id,
                'numero' => "{$ano}/{$numero}",
                'tipo' => 'Empréstimo',
                'cliente' => $s->client ? ($s->client->razao_social ?? $s->client->nome_completo ?? '—') : '—',
                'valor_contrato' => (float) $s->valor_contrato,
                'taxa' => (float) $s->taxa_juros_mensal,
                'data_assinatura' => $s->data_assinatura?->format('Y-m-d'),
                'situacao' => $s->situacao === 'efetivado' ? 'Efetivado' : 'Em preenchimento',
            ];
        });

        $baseQuery = SimulacaoEmprestimo::where('company_id', $companyId);
        $totalEmPreenchimento = (clone $baseQuery)->where('situacao', 'em_preenchimento')->count();
        $totalEfetivados = (clone $baseQuery)->where('situacao', 'efetivado')->count();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'total_em_preenchimento' => $totalEmPreenchimento,
                'total_efetivados' => $totalEfetivados,
            ],
        ]);
    }

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
                'situacao' => 'em_preenchimento',
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
