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
     * @return array
     */
    public function gerarRelatorioFiscal(int $companyId, string $dataInicio, string $dataFim): array
    {
        $config = $this->obterConfiguracaoFiscal($companyId);

        $receitaBruta = $this->calcularReceitaBruta($companyId, $dataInicio, $dataFim);
        $despesasDedutiveis = $this->calcularDespesasDedutiveis($companyId, $dataInicio, $dataFim);
        $lucroPresumido = $this->calcularLucroPresumido($receitaBruta, $config->percentual_presuncao);
        $baseTributavel = $this->calcularBaseTributavel($lucroPresumido, $despesasDedutiveis);

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

        return [
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
            'lucro_presumido' => $lucroPresumido,
            'base_tributavel' => $baseTributavel,
            'irpj' => $irpj,
            'csll' => $csll,
            'total_impostos' => $totalImpostos,
            'movimentacoes' => $movimentacoes,
            'despesas' => $despesas,
        ];
    }
}

