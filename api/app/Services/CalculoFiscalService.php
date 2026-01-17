<?php

namespace App\Services;

use App\Models\Movimentacaofinanceira;
use App\Models\Contaspagar;
use App\Models\ConfiguracaoFiscal;
use Carbon\Carbon;

class CalculoFiscalService
{
    /**
     * Calcula a receita bruta do período (soma de todas as entradas)
     * Exclui movimentações de refinanciamento e desconto (baixas manuais são receita real)
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return float
     */
    public function calcularReceitaBruta(int $companyId, string $dataInicio, string $dataFim): float
    {
        $receitaBruta = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->sum('valor');

        return (float) $receitaBruta;
    }

    /**
     * Calcula despesas dedutíveis (contas a pagar pagas no período)
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return float
     */
    public function calcularDespesasDedutiveis(int $companyId, string $dataInicio, string $dataFim): float
    {
        $despesas = Contaspagar::where('company_id', $companyId)
            ->where('status', 'Pagamento Efetuado')
            ->whereNotNull('dt_baixa')
            ->whereBetween('dt_baixa', [$dataInicio, $dataFim])
            ->sum('valor');

        return (float) $despesas;
    }

    /**
     * Calcula o lucro presumido
     *
     * @param float $receitaBruta
     * @param float $percentualPresuncao
     * @return float
     */
    public function calcularLucroPresumido(float $receitaBruta, float $percentualPresuncao): float
    {
        return round($receitaBruta * ($percentualPresuncao / 100), 2);
    }

    /**
     * Calcula a base tributável
     * No lucro presumido, a base tributável é o próprio lucro presumido
     * Despesas não são deduzidas da base, apenas informativas para relatórios
     *
     * @param float $lucroPresumido
     * @param float $despesasDedutiveis (apenas informativo, não utilizado no cálculo)
     * @return float
     */
    public function calcularBaseTributavel(float $lucroPresumido, float $despesasDedutiveis = 0): float
    {
        // No lucro presumido, a base tributável é sempre igual ao lucro presumido
        // Despesas são apenas informativas e não reduzem a base de cálculo
        return max(0, round($lucroPresumido, 2));
    }

    /**
     * Calcula IRPJ (15% normal + 10% adicional sobre excedente)
     *
     * @param float $baseTributavel
     * @param float $faixaIsencao
     * @param float $aliquotaNormal
     * @param float $aliquotaAdicional
     * @return array
     */
    public function calcularIRPJ(float $baseTributavel, float $faixaIsencao, float $aliquotaNormal = 15.0, float $aliquotaAdicional = 10.0): array
    {
        $irpjNormal = round($baseTributavel * ($aliquotaNormal / 100), 2);
        $excedente = max(0, $baseTributavel - $faixaIsencao);
        $irpjAdicional = round($excedente * ($aliquotaAdicional / 100), 2);
        $irpjTotal = round($irpjNormal + $irpjAdicional, 2);

        return [
            'normal' => $irpjNormal,
            'adicional' => $irpjAdicional,
            'total' => $irpjTotal,
        ];
    }

    /**
     * Calcula CSLL (9% sobre base tributável)
     *
     * @param float $baseTributavel
     * @param float $aliquota
     * @return float
     */
    public function calcularCSLL(float $baseTributavel, float $aliquota = 9.0): float
    {
        return round($baseTributavel * ($aliquota / 100), 2);
    }

    /**
     * Calcula o lucro proporcional recebido baseado nas parcelas pagas
     * Para cada parcela recebida, calcula: lucro_por_parcela = emprestimo->lucro / num_parcelas
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return array
     */
    public function calcularLucroProporcionalRecebido(int $companyId, string $dataInicio, string $dataFim): array
    {
        // Buscar movimentações de entrada com parcela_id (excluindo refinanciamentos e descontos)
        $movimentacoes = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->whereNotNull('parcela_id')
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->with(['parcela.emprestimo.parcelas', 'parcela.emprestimo.client'])
            ->orderBy('dt_movimentacao', 'asc')
            ->get();

        $lucroProporcionalTotal = 0;
        $detalhamento = [];
        $emprestimosMap = []; // Agrupar por empréstimo

        foreach ($movimentacoes as $movimentacao) {
            if (!$movimentacao->parcela || !$movimentacao->parcela->emprestimo) {
                continue;
            }

            $emprestimo = $movimentacao->parcela->emprestimo;
            $emprestimoId = $emprestimo->id;

            // Contar total de parcelas do empréstimo
            $numParcelas = $emprestimo->parcelas ? $emprestimo->parcelas->count() : 0;
            
            if ($numParcelas == 0 || $emprestimo->lucro == 0) {
                continue;
            }

            // Usar lucro_real da parcela (já inclui multas/juros quando aplicado)
            // Se não houver lucro_real, calcular como fallback
            $lucroRealParcela = (float) ($movimentacao->parcela->lucro_real ?? 0);
            
            if ($lucroRealParcela == 0) {
                // Fallback: calcular lucro base por parcela se não houver lucro_real
                $lucroRealParcela = round($emprestimo->lucro / $numParcelas, 2);
                
                // Se recebeu mais que o valor original da parcela, adicionar diferença
                $valorOriginalParcela = (float) ($movimentacao->parcela->valor ?? 0);
                $valorRecebido = (float) $movimentacao->valor;
                $lucroAdicional = max(0, $valorRecebido - $valorOriginalParcela);
                $lucroRealParcela += $lucroAdicional;
            }
            
            $lucroProporcionalTotal += $lucroRealParcela;

            // Agrupar por empréstimo
            if (!isset($emprestimosMap[$emprestimoId])) {
                $emprestimosMap[$emprestimoId] = [
                    'emprestimo_id' => $emprestimoId,
                    'cliente' => $emprestimo->client ? $emprestimo->client->nome_completo : 'N/A',
                    'valor_emprestado' => (float) $emprestimo->valor,
                    'lucro_total' => (float) $emprestimo->lucro,
                    'num_parcelas' => $numParcelas,
                    'lucro_por_parcela' => round($emprestimo->lucro / $numParcelas, 2),
                    'parcelas_recebidas_periodo' => [],
                    'total_lucro_periodo' => 0,
                ];
            }

            // Adicionar detalhe da parcela recebida
            $valorOriginalParcela = (float) ($movimentacao->parcela->valor ?? 0);
            $valorRecebido = (float) $movimentacao->valor;
            $lucroBaseCalculado = round($emprestimo->lucro / $numParcelas, 2);
            $lucroAdicional = max(0, $lucroRealParcela - $lucroBaseCalculado);
            
            $emprestimosMap[$emprestimoId]['parcelas_recebidas_periodo'][] = [
                'movimentacao_id' => $movimentacao->id,
                'parcela_id' => $movimentacao->parcela_id,
                'data_recebimento' => $movimentacao->dt_movimentacao,
                'valor_original_parcela' => $valorOriginalParcela,
                'valor_recebido' => $valorRecebido,
                'lucro_base_parcela' => $lucroBaseCalculado,
                'lucro_adicional' => $lucroAdicional, // multas/juros
                'lucro_proporcional' => $lucroRealParcela,
                'descricao' => $movimentacao->descricao,
            ];

            $emprestimosMap[$emprestimoId]['total_lucro_periodo'] += $lucroRealParcela;
        }

        // Converter map para array
        $detalhamentoEmprestimos = array_values($emprestimosMap);

        // Buscar movimentações sem parcela_id (outras receitas)
        $movimentacoesSemParcela = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('parcela_id')
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->sum('valor');

        return [
            'lucro_proporcional_total' => round($lucroProporcionalTotal, 2),
            'detalhamento_emprestimos' => $detalhamentoEmprestimos,
            'movimentacoes_sem_parcela' => (float) $movimentacoesSemParcela,
            'movimentacoes_processadas' => $movimentacoes->count(),
        ];
    }

    /**
     * Obtém ou cria configuração fiscal da empresa
     *
     * @param int $companyId
     * @return ConfiguracaoFiscal
     */
    public function obterConfiguracaoFiscal(int $companyId): ConfiguracaoFiscal
    {
        $config = ConfiguracaoFiscal::where('company_id', $companyId)->first();

        if (!$config) {
            // Cria configuração padrão se não existir
            $config = ConfiguracaoFiscal::create([
                'company_id' => $companyId,
                'percentual_presuncao' => 32.00,
                'aliquota_irpj' => 15.00,
                'aliquota_irpj_adicional' => 10.00,
                'aliquota_csll' => 9.00,
                'faixa_isencao_irpj' => 20000.00,
            ]);
        }

        return $config;
    }

    /**
     * Gera relatório fiscal completo
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @param string $tipo 'presumido' ou 'proporcional' (padrão: 'presumido')
     * @return array
     */
    public function gerarRelatorioFiscal(int $companyId, string $dataInicio, string $dataFim, string $tipo = 'presumido'): array
    {
        $config = $this->obterConfiguracaoFiscal($companyId);
        $receitaBruta = $this->calcularReceitaBruta($companyId, $dataInicio, $dataFim);
        $despesasDedutiveis = $this->calcularDespesasDedutiveis($companyId, $dataInicio, $dataFim);

        $lucroProporcional = null;
        $detalhamentoEmprestimos = [];

        // Escolher método de cálculo
        if ($tipo === 'proporcional') {
            $lucroProporcionalData = $this->calcularLucroProporcionalRecebido($companyId, $dataInicio, $dataFim);
            $lucroProporcional = $lucroProporcionalData['lucro_proporcional_total'];
            $detalhamentoEmprestimos = $lucroProporcionalData['detalhamento_emprestimos'];
            $baseTributavel = max(0, round($lucroProporcional, 2));
            $lucroPresumido = 0; // Não usado no cálculo proporcional
        } else {
            $lucroPresumido = $this->calcularLucroPresumido($receitaBruta, $config->percentual_presuncao);
            $baseTributavel = $this->calcularBaseTributavel($lucroPresumido, $despesasDedutiveis);
        }

        $irpj = $this->calcularIRPJ(
            $baseTributavel,
            $config->faixa_isencao_irpj,
            $config->aliquota_irpj,
            $config->aliquota_irpj_adicional
        );

        $csll = $this->calcularCSLL($baseTributavel, $config->aliquota_csll);

        $totalImpostos = round($irpj['total'] + $csll, 2);

        // Buscar movimentações detalhadas (excluindo refinanciamentos e descontos)
        // Baixas manuais são incluídas pois são receitas reais recebidas
        $movimentacoes = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->with(['banco', 'parcela'])
            ->orderBy('dt_movimentacao', 'asc')
            ->get();

        // Buscar despesas detalhadas
        $despesas = Contaspagar::where('company_id', $companyId)
            ->where('status', 'Pagamento Efetuado')
            ->whereNotNull('dt_baixa')
            ->whereBetween('dt_baixa', [$dataInicio, $dataFim])
            ->with(['fornecedor', 'banco', 'costcenter'])
            ->orderBy('dt_baixa', 'asc')
            ->get();

        $resultado = [
            'tipo_calculo' => $tipo,
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
            ],
            'configuracao' => [
                'percentual_presuncao' => $config->percentual_presuncao,
                'aliquota_irpj' => $config->aliquota_irpj,
                'aliquota_irpj_adicional' => $config->aliquota_irpj_adicional,
                'aliquota_csll' => $config->aliquota_csll,
                'faixa_isencao_irpj' => $config->faixa_isencao_irpj,
            ],
            'receita_bruta' => $receitaBruta,
            'despesas_dedutiveis' => $despesasDedutiveis,
            'base_tributavel' => $baseTributavel,
            'irpj' => $irpj,
            'csll' => $csll,
            'total_impostos' => $totalImpostos,
            'movimentacoes' => $movimentacoes,
            'despesas' => $despesas,
        ];

        if ($tipo === 'proporcional') {
            $resultado['lucro_proporcional_total'] = $lucroProporcional;
            $resultado['detalhamento_emprestimos'] = $detalhamentoEmprestimos;
            $resultado['lucro_presumido'] = 0;
        } else {
            $resultado['lucro_presumido'] = $lucroPresumido;
            $resultado['lucro_proporcional_total'] = 0;
            $resultado['detalhamento_emprestimos'] = [];
        }

        return $resultado;
    }
}

