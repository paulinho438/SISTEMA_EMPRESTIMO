<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulacaoEmprestimoRequest;
use App\Models\Contaspagar;
use App\Models\Costcenter;
use App\Models\Emprestimo;
use App\Models\SimulacaoEmprestimo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SimulacaoEmprestimoController extends Controller
{
    private function resolveCompanyId(Request $request): int
    {
        $companyId = $request->header('company-id');
        if (!$companyId && auth()->check() && auth()->user()->company_id) {
            $companyId = auth()->user()->company_id;
        }
        return (int) ($companyId ?: 1);
    }

    private function aliquotaDiaria(bool $simplesNacional, float $valorSolicitado): string
    {
        // Regra usada no frontend: simples + valor <= 30.000 => 0,00274% ao dia; senão 0,0082%
        if ($simplesNacional && $valorSolicitado <= 30000) {
            return '0,00274%';
        }
        return '0,0082%';
    }

    /**
     * Lista contratos (simulações) com filtro opcional por situação
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);

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
     * Retorna um contrato (simulação) para edição (preenche wizard)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        $s = SimulacaoEmprestimo::with('client')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $inputs = [
            'valor_solicitado' => (float) $s->valor_solicitado,
            'periodo_amortizacao' => $s->periodo_amortizacao,
            'modelo_amortizacao' => $s->modelo_amortizacao,
            'quantidade_parcelas' => (int) $s->quantidade_parcelas,
            'taxa_juros_mensal' => (float) $s->taxa_juros_mensal,
            'data_assinatura' => $s->data_assinatura?->format('Y-m-d'),
            'data_primeira_parcela' => $s->data_primeira_parcela?->format('Y-m-d'),
            'simples_nacional' => (bool) $s->simples_nacional,
            'calcular_iof' => (bool) $s->calcular_iof,
            'garantias' => $s->garantias ?? [],
            'inadimplencia' => $s->inadimplencia ?? null,
        ];

        return response()->json([
            'id' => $s->id,
            'client_id' => $s->client_id,
            'banco_id' => $s->banco_id,
            'situacao' => $s->situacao,
            'result' => [
                'inputs' => array_merge($inputs, [
                    // para UI "Outras Informações"
                    'data_assinatura' => $inputs['data_assinatura'],
                ]),
                'iof' => [
                    'total' => (float) $s->iof_total,
                    'adicional' => (float) $s->iof_adicional,
                    'diario' => (float) $s->iof_diario,
                    'aliquota_diaria' => $this->aliquotaDiaria((bool) $s->simples_nacional, (float) $s->valor_solicitado),
                ],
                'totais' => [
                    'total_parcelas' => (float) $s->total_parcelas,
                    'cet_mes' => $s->cet_mes,
                    'cet_ano' => $s->cet_ano,
                    'juros_acerto' => 0,
                ],
                'valor_contrato' => (float) $s->valor_contrato,
                'parcela' => (float) $s->parcela,
                'cronograma' => $s->cronograma ?? [],
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

            $companyId = $this->resolveCompanyId($request);

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
                'inadimplencia' => $inputs['inadimplencia'] ?? null,

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

    /**
     * Marca contrato como efetivado
     */
    public function efetivar(Request $request, int $id): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        $s = SimulacaoEmprestimo::where('company_id', $companyId)->findOrFail($id);

        if (!$s->banco_id) {
            return response()->json([
                'success' => false,
                'message' => 'Selecione o banco pagador antes de iniciar o contrato.',
            ], 422);
        }
        if (!$s->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Selecione o cliente antes de iniciar o contrato.',
            ], 422);
        }

        // Se já existe empréstimo vinculado, reaproveita
        $emprestimoExistente = Emprestimo::where('company_id', $companyId)
            ->where('simulacao_emprestimo_id', $s->id)
            ->first();

        if (!$emprestimoExistente) {
            $costcenter = Costcenter::where('company_id', $companyId)->first();
            if (!$costcenter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum centro de custo encontrado para a empresa.',
                ], 422);
            }

            $valor = (float) $s->valor_solicitado;
            $totalPrazo = (float) $s->total_parcelas;
            $lucro = max(0, $totalPrazo - $valor);

            $emprestimoExistente = Emprestimo::create([
                'dt_lancamento' => $s->data_assinatura ? $s->data_assinatura->format('Y-m-d') : Carbon::now()->format('Y-m-d'),
                'valor' => $valor,
                'lucro' => $lucro,
                'juros' => (float) $s->taxa_juros_mensal,
                'costcenter_id' => $costcenter->id,
                'banco_id' => $s->banco_id,
                'client_id' => $s->client_id,
                'user_id' => auth()->id(),
                'company_id' => $companyId,
                'simulacao_emprestimo_id' => $s->id,
                'liberar_minimo' => 1,
            ]);

            // Contas a pagar para efetuar a transferência ao cliente (aprovação)
            Contaspagar::create([
                'banco_id' => $s->banco_id,
                'emprestimo_id' => $emprestimoExistente->id,
                'costcenter_id' => $costcenter->id,
                'status' => 'Aguardando Pagamento',
                'tipodoc' => 'Empréstimo',
                'lanc' => Carbon::now()->format('Y-m-d'),
                'venc' => Carbon::now()->format('Y-m-d'),
                'valor' => $valor,
                'descricao' => 'Empréstimo (Contrato) Nº ' . $s->id,
                'company_id' => $companyId,
            ]);
        }

        $s->situacao = 'efetivado';
        $s->save();

        return response()->json([
            'success' => true,
            'message' => 'Contrato iniciado com sucesso.',
            'emprestimo_id' => $emprestimoExistente->id,
        ]);
    }
}
