<?php

namespace App\Services;

use App\Models\Movimentacaofinanceira;
use App\Models\Contaspagar;
use App\Models\ConfiguracaoFiscal;
use App\Models\SimulacaoEmprestimo;
use App\Models\Parcela;
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
     * Calcula PIS (0,65% sobre receita bruta)
     *
     * @param float $receitaBruta
     * @param float $aliquota
     * @return float
     */
    public function calcularPIS(float $receitaBruta, float $aliquota = 0.65): float
    {
        return round($receitaBruta * ($aliquota / 100), 2);
    }

    /**
     * Calcula COFINS (3% sobre receita bruta)
     *
     * @param float $receitaBruta
     * @param float $aliquota
     * @return float
     */
    public function calcularCOFINS(float $receitaBruta, float $aliquota = 3.0): float
    {
        return round($receitaBruta * ($aliquota / 100), 2);
    }

    /**
     * Calcula IOF total das operações (simulações) feitas no período
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return float
     */
    public function calcularIOFOperacoesMes(int $companyId, string $dataInicio, string $dataFim): float
    {
        $iofTotal = SimulacaoEmprestimo::where('company_id', $companyId)
            ->whereBetween('data_assinatura', [$dataInicio, $dataFim])
            ->sum('iof_total');

        return (float) $iofTotal;
    }

    /**
     * Calcula total de amortização e juros do período a partir das movimentações com parcela
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return array{total_amortizacao: float, total_juros: float}
     */
    public function calcularTotalAmortizacaoEJuros(int $companyId, string $dataInicio, string $dataFim): array
    {
        $movimentacoes = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->whereNotNull('parcela_id')
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->with(['parcela.emprestimo.parcelas'])
            ->get();

        $totalJuros = 0;
        $totalAmortizacao = 0;

        foreach ($movimentacoes as $movimentacao) {
            if (!$movimentacao->parcela || !$movimentacao->parcela->emprestimo) {
                continue;
            }

            $emprestimo = $movimentacao->parcela->emprestimo;
            $numParcelas = $emprestimo->parcelas ? $emprestimo->parcelas->count() : 0;

            $lucroRealParcela = (float) ($movimentacao->parcela->lucro_real ?? 0);
            if ($lucroRealParcela == 0 && $numParcelas > 0 && $emprestimo->lucro > 0) {
                $lucroRealParcela = round($emprestimo->lucro / $numParcelas, 2);
                $valorRecebido = (float) $movimentacao->valor;
                $valorOriginalParcela = (float) ($movimentacao->parcela->valor ?? 0);
                $lucroRealParcela += max(0, $valorRecebido - $valorOriginalParcela);
            }

            $valorRecebido = (float) $movimentacao->valor;
            $totalJuros += $lucroRealParcela;
            $totalAmortizacao += $valorRecebido - $lucroRealParcela;
        }

        return [
            'total_amortizacao' => round($totalAmortizacao, 2),
            'total_juros' => round($totalJuros, 2),
        ];
    }

    /**
     * Calcula descontos aplicados no período (movimentações com descrição contendo "desconto")
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return float
     */
    public function calcularDescontosAplicados(int $companyId, string $dataInicio, string $dataFim): float
    {
        $descontos = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->where('descricao', 'like', '%desconto%')
            ->sum('valor');

        return (float) $descontos;
    }

    /**
     * Calcula valor dos títulos atrasados (parcelas vencidas no mês com saldo > 0)
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return float
     */
    public function calcularTitulosAtrasados(int $companyId, string $dataInicio, string $dataFim): float
    {
        $valor = Parcela::whereHas('emprestimo', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->whereNull('dt_baixa')
            ->whereBetween('venc_real', [$dataInicio, $dataFim])
            ->where('saldo', '>', 0)
            ->sum('saldo');

        return round((float) $valor, 2);
    }

    /**
     * Verifica se o mês é de apuração trimestral (Março, Junho, Setembro, Dezembro)
     *
     * @param int $mes
     * @return bool
     */
    public function isMesTrimestral(int $mes): bool
    {
        return in_array($mes, [3, 6, 9, 12], true);
    }

    /**
     * Retorna o primeiro dia do trimestre para o mês informado
     *
     * @param int $ano
     * @param int $mes
     * @return string
     */
    public function getInicioTrimestre(int $ano, int $mes): string
    {
        $trimestre = (int) ceil($mes / 3);
        $mesInicio = ($trimestre - 1) * 3 + 1;
        return sprintf('%04d-%02d-01', $ano, $mesInicio);
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
            // Cria configuração padrão se não existir (38,4% para atividade de crédito)
            $config = ConfiguracaoFiscal::create([
                'company_id' => $companyId,
                'percentual_presuncao' => 38.40,
                'aliquota_irpj' => 15.00,
                'aliquota_irpj_adicional' => 10.00,
                'aliquota_csll' => 9.00,
                'aliquota_pis' => 0.65,
                'aliquota_cofins' => 3.00,
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

        // Detectar mês para lógica trimestral IRPJ/CSLL
        $dataFimCarbon = Carbon::parse($dataFim);
        $mes = (int) $dataFimCarbon->format('n');
        $ano = (int) $dataFimCarbon->format('Y');
        $mesTrimestral = $this->isMesTrimestral($mes);

        // Para IRPJ/CSLL trimestrais: usar receita do trimestre inteiro
        $receitaParaIRPJCSLL = $receitaBruta;
        $dataInicioTrimestre = $dataInicio;
        $dataFimTrimestre = $dataFim;

        if ($mesTrimestral && $tipo === 'presumido') {
            $dataInicioTrimestre = $this->getInicioTrimestre($ano, $mes);
            $dataFimTrimestre = $dataFim;
            $receitaParaIRPJCSLL = $this->calcularReceitaBruta($companyId, $dataInicioTrimestre, $dataFimTrimestre);
        }

        $lucroProporcional = null;
        $detalhamentoEmprestimos = [];

        // Escolher método de cálculo
        if ($tipo === 'proporcional') {
            $dataInicioProporcional = $mesTrimestral ? $dataInicioTrimestre : $dataInicio;
            $dataFimProporcional = $mesTrimestral ? $dataFimTrimestre : $dataFim;
            $lucroProporcionalData = $this->calcularLucroProporcionalRecebido($companyId, $dataInicioProporcional, $dataFimProporcional);
            $lucroProporcional = $lucroProporcionalData['lucro_proporcional_total'];
            $detalhamentoEmprestimos = $lucroProporcionalData['detalhamento_emprestimos'];
            $baseTributavel = $mesTrimestral ? max(0, round($lucroProporcional, 2)) : 0;
            $lucroPresumido = 0;
        } else {
            $lucroPresumido = $this->calcularLucroPresumido($receitaParaIRPJCSLL, $config->percentual_presuncao);
            $baseTributavel = $this->calcularBaseTributavel($lucroPresumido, $despesasDedutiveis);
        }

        // IRPJ e CSLL: zerar em meses não trimestrais
        $irpj = ['normal' => 0, 'adicional' => 0, 'total' => 0];
        $csll = 0;

        if ($mesTrimestral) {
            $irpj = $this->calcularIRPJ(
                $baseTributavel,
                $config->faixa_isencao_irpj,
                $config->aliquota_irpj,
                $config->aliquota_irpj_adicional
            );
            $csll = $this->calcularCSLL($baseTributavel, $config->aliquota_csll ?? 9.0);
        }

        // PIS e COFINS: mensais, sobre receita bruta do mês
        $aliquotaPis = (float) ($config->aliquota_pis ?? 0.65);
        $aliquotaCofins = (float) ($config->aliquota_cofins ?? 3.0);
        $pis = $this->calcularPIS($receitaBruta, $aliquotaPis);
        $cofins = $this->calcularCOFINS($receitaBruta, $aliquotaCofins);

        // IOF: operações do mês
        $iofTotalMes = $this->calcularIOFOperacoesMes($companyId, $dataInicio, $dataFim);

        // Métricas adicionais para Sumário dos Contratos Ativos
        $amortizacaoJuros = $this->calcularTotalAmortizacaoEJuros($companyId, $dataInicio, $dataFim);
        $descontosAplicados = $this->calcularDescontosAplicados($companyId, $dataInicio, $dataFim);
        $titulosAtrasados = $this->calcularTitulosAtrasados($companyId, $dataInicio, $dataFim);

        $totalImpostos = round($irpj['total'] + $csll + $pis + $cofins, 2);

        // Vencimento: mês seguinte ao cálculo
        $vencimentoImpostos = Carbon::parse($dataFim)->addMonth()->firstOfMonth()->format('Y-m-d');

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
                'aliquota_pis' => $aliquotaPis,
                'aliquota_cofins' => $aliquotaCofins,
                'faixa_isencao_irpj' => $config->faixa_isencao_irpj,
            ],
            'receita_bruta' => $receitaBruta,
            'despesas_dedutiveis' => $despesasDedutiveis,
            'base_tributavel' => $baseTributavel,
            'irpj' => $irpj,
            'csll' => $csll,
            'pis' => $pis,
            'cofins' => $cofins,
            'iof_total_mes' => $iofTotalMes,
            'total_impostos' => $totalImpostos,
            'total_recebimentos' => $receitaBruta,
            'total_amortizacao' => $amortizacaoJuros['total_amortizacao'],
            'total_juros' => $amortizacaoJuros['total_juros'],
            'descontos_aplicados' => $descontosAplicados,
            'titulos_atrasados' => $titulosAtrasados,
            'mes_trimestral' => $mesTrimestral,
            'vencimento_impostos' => $vencimentoImpostos,
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

