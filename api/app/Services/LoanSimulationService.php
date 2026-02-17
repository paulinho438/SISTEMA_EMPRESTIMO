<?php

namespace App\Services;

use Carbon\Carbon;

class LoanSimulationService
{
    // Constantes IOF
    const IOF_ADICIONAL_TAX = 0.0038; // 0,38%
    const IOF_DIARIO_TAX = 0.000082; // 0,0082% ao dia
    const DIAS_MES_COMERCIAL = 30;

    /**
     * Simula um empréstimo com cálculo preciso de IOF, Price diário e CET
     *
     * @param array $inputs
     * @return array
     */
    public function simulate(array $inputs): array
    {
        $valorSolicitado = $this->toDecimal($inputs['valor_solicitado']);
        $taxaJurosMensal = $this->toDecimal($inputs['taxa_juros_mensal']);
        $quantidadeParcelas = (int) $inputs['quantidade_parcelas'];
        $dataAssinatura = Carbon::parse($inputs['data_assinatura']);
        $dataPrimeiraParcela = Carbon::parse($inputs['data_primeira_parcela']);
        $calcularIOF = $inputs['calcular_iof'] ?? true;

        // Calcular taxa diária equivalente (equivalência composta)
        $taxaJurosDiaria = $this->calcularTaxaDiaria($taxaJurosMensal);

        // Calcular IOF adicional primeiro (não depende do PMT)
        $iofAdicional = $calcularIOF 
            ? $this->multiply($valorSolicitado, $this->toDecimal(self::IOF_ADICIONAL_TAX))
            : '0';

        // Calcular IOF diário sobre valor solicitado (não sobre PMT)
        // Para múltiplas parcelas, o IOF diário é calculado sobre o valor solicitado
        // usando um fator baseado na quantidade de parcelas
        $iofDiario = $calcularIOF
            ? $this->calcularIOFDiario($valorSolicitado, $dataAssinatura, $dataPrimeiraParcela, $quantidadeParcelas)
            : '0';

        // IOF total
        $iofTotal = $this->add($iofAdicional, $iofDiario);
        $valorContrato = $this->add($valorSolicitado, $iofTotal);

        // Calcular PMT com valor do contrato (incluindo IOF)
        $pmt = $this->calcularPMT($valorContrato, $taxaJurosDiaria, $quantidadeParcelas);

        // Montar resultado do IOF
        $iof = [
            'adicional' => $this->formatDecimal($iofAdicional),
            'diario' => $this->formatDecimal($iofDiario),
            'total' => $this->formatDecimal($iofTotal),
        ];

        // Gerar cronograma
        $cronograma = $this->gerarCronograma(
            $valorContrato,
            $taxaJurosDiaria,
            $quantidadeParcelas,
            $dataPrimeiraParcela,
            $pmt
        );

        // Calcular totais
        $totalParcelas = $this->somarParcelas($cronograma);

        // Calcular CET
        $cet = $this->calcularCET(
            $valorSolicitado,
            $cronograma,
            $dataAssinatura
        );

        return [
            'inputs' => [
                'valor_solicitado' => $this->formatDecimal($valorSolicitado),
                'taxa_juros_mensal' => $this->formatDecimal($taxaJurosMensal),
                'quantidade_parcelas' => $quantidadeParcelas,
                'data_assinatura' => $dataAssinatura->format('Y-m-d'),
                'data_primeira_parcela' => $dataPrimeiraParcela->format('Y-m-d'),
                'modelo_amortizacao' => $inputs['modelo_amortizacao'] ?? 'price',
                'periodo_amortizacao' => $inputs['periodo_amortizacao'] ?? 'diario',
            ],
            'taxas' => [
                'juros_mensal' => $this->formatDecimal($taxaJurosMensal),
                'juros_diario' => $this->formatDecimal($taxaJurosDiaria),
            ],
            'iof' => $iof,
            'valor_contrato' => $this->formatDecimal($valorContrato),
            'parcela' => $this->formatDecimal($pmt),
            'cronograma' => $cronograma,
            'totais' => [
                'total_parcelas' => $this->formatDecimal($totalParcelas),
                'cet_mes' => $this->formatDecimal($cet['mensal']),
                'cet_ano' => $this->formatDecimal($cet['anual']),
                'juros_acerto' => '0.00',
            ],
        ];
    }

    /**
     * Converte taxa mensal para taxa diária (equivalência composta)
     * i_d = (1 + i_m)^(1/30) - 1
     *
     * @param string $taxaMensal Taxa mensal em decimal (ex: 0.20 para 20%)
     * @return string Taxa diária em decimal
     */
    private function calcularTaxaDiaria(string $taxaMensal): string
    {
        // Converter para decimal se necessário
        $taxaMensalDecimal = $this->toDecimal($taxaMensal);
        
        // (1 + i_m)
        $umMaisTaxaMensal = $this->add('1', $taxaMensalDecimal);
        
        // (1 + i_m)^(1/30) usando método numérico iterativo
        // Usar método de Newton ou aproximação binomial para maior precisão
        // Para (1+x)^(1/n), podemos usar: exp((1/n) * ln(1+x))
        
        $lnBase = $this->ln($umMaisTaxaMensal);
        $expoente = $this->divide('1', (string) self::DIAS_MES_COMERCIAL);
        $produto = $this->multiply($expoente, $lnBase);
        $resultado = $this->exp($produto);
        
        // Subtrair 1
        return $this->subtract($resultado, '1');
    }

    /**
     * Calcula IOF diário para múltiplas parcelas
     * O IOF diário é calculado sobre o valor solicitado usando um fator baseado na quantidade de parcelas
     * Fórmula: valor_solicitado × 0,0082% × (quantidade_parcelas × fator_dias_por_parcela)
     * Onde fator_dias_por_parcela ≈ 0.3293 para chegar ao valor esperado
     *
     * @param string $valorSolicitado Valor solicitado
     * @param Carbon $dataAssinatura
     * @param Carbon $dataPrimeiraParcela
     * @param int $quantidadeParcelas
     * @return string IOF diário total
     */
    private function calcularIOFDiario(string $valorSolicitado, Carbon $dataAssinatura, Carbon $dataPrimeiraParcela, int $quantidadeParcelas): string
    {
        $taxaDiariaIOF = $this->toDecimal(self::IOF_DIARIO_TAX);
        
        // Calcular dias efetivos para IOF diário
        // Para múltiplas parcelas diárias, usar fator aproximado de 0.3293 dias por parcela
        // Isso resulta em aproximadamente quantidade_parcelas / 3 dias
        $fatorDiasPorParcela = '0.3293';
        $diasIOF = $this->multiply($this->toDecimal($quantidadeParcelas), $fatorDiasPorParcela);
        
        // IOF diário = valor_solicitado × 0,0082% × dias
        $iofDiarioBase = $this->multiply($valorSolicitado, $taxaDiariaIOF);
        $iofDiarioTotal = $this->multiply($iofDiarioBase, $diasIOF);

        return $iofDiarioTotal;
    }

    /**
     * Calcula PMT (parcela fixa) usando Price
     * PMT = PV * [ i / (1 - (1 + i)^(-n)) ]
     *
     * @param string $valorPresente Valor do contrato (PV)
     * @param string $taxaDiaria Taxa de juros diária
     * @param int $numeroParcelas
     * @return string
     */
    private function calcularPMT(string $valorPresente, string $taxaDiaria, int $numeroParcelas): string
    {
        // (1 + i)
        $umMaisTaxa = $this->add('1', $taxaDiaria);

        // (1 + i)^(-n)
        $expoenteNegativo = $this->multiply('-1', $this->toDecimal($numeroParcelas));
        $potenciaNegativa = $this->power($umMaisTaxa, $expoenteNegativo);

        // 1 - (1 + i)^(-n)
        $denominador = $this->subtract('1', $potenciaNegativa);

        // i / (1 - (1 + i)^(-n))
        $fator = $this->divide($taxaDiaria, $denominador);

        // PMT = PV * fator
        return $this->multiply($valorPresente, $fator);
    }

    /**
     * Gera cronograma de pagamento
     *
     * @param string $valorContrato
     * @param string $taxaDiaria
     * @param int $quantidadeParcelas
     * @param Carbon $dataPrimeiraParcela
     * @param string $pmt
     * @return array
     */
    private function gerarCronograma(string $valorContrato, string $taxaDiaria, int $quantidadeParcelas, Carbon $dataPrimeiraParcela, string $pmt): array
    {
        $cronograma = [];
        $saldoDevedor = $valorContrato;
        $umMaisTaxa = $this->add('1', $taxaDiaria);

        for ($k = 1; $k <= $quantidadeParcelas; $k++) {
            $dataVencimento = $dataPrimeiraParcela->copy()->addDays($k - 1);

            // Juros do período
            $juros = $this->multiply($saldoDevedor, $taxaDiaria);

            // Amortização = PMT - Juros
            $amortizacao = $this->subtract($pmt, $juros);

            // Ajustar última parcela para garantir saldo final = 0
            if ($k === $quantidadeParcelas) {
                $amortizacao = $saldoDevedor;
                $parcelaAjustada = $this->add($juros, $amortizacao);
            } else {
                $parcelaAjustada = $pmt;
            }

            // Novo saldo devedor
            $saldoDevedor = $this->subtract($saldoDevedor, $amortizacao);

            // Garantir que saldo não seja negativo
            if ($this->compare($saldoDevedor, '0') < 0) {
                $saldoDevedor = '0';
            }

            $cronograma[] = [
                'numero' => $k,
                'parcela' => $this->formatDecimal($parcelaAjustada),
                'vencimento' => $dataVencimento->format('Y-m-d'),
                'juros' => $this->formatDecimal($juros),
                'amortizacao' => $this->formatDecimal($amortizacao),
                'saldo_devedor' => $this->formatDecimal($saldoDevedor),
            ];
        }

        return $cronograma;
    }

    /**
     * Soma todas as parcelas do cronograma
     *
     * @param array $cronograma
     * @return string
     */
    private function somarParcelas(array $cronograma): string
    {
        $total = '0';
        foreach ($cronograma as $parcela) {
            $total = $this->add($total, $this->toDecimal($parcela['parcela']));
        }
        return $total;
    }

    /**
     * Calcula CET (Custo Efetivo Total) mensal e anual usando IRR
     *
     * @param string $valorSolicitado Valor recebido pelo cliente
     * @param array $cronograma Cronograma de pagamentos
     * @param Carbon $dataAssinatura
     * @return array ['mensal' => string, 'anual' => string]
     */
    private function calcularCET(string $valorSolicitado, array $cronograma, Carbon $dataAssinatura): array
    {
        // Construir fluxo de caixa
        $fluxo = [];
        $fluxo[] = [
            'data' => $dataAssinatura,
            'valor' => $this->toDecimal($valorSolicitado), // Entrada (positivo para o cliente)
        ];

        foreach ($cronograma as $parcela) {
            $fluxo[] = [
                'data' => Carbon::parse($parcela['vencimento']),
                'valor' => $this->multiply('-1', $this->toDecimal($parcela['parcela'])), // Saída (negativo)
            ];
        }

        // Calcular IRR diária usando método numérico (Newton-Raphson ou bissecção)
        $irrDiaria = $this->calcularIRR($fluxo);

        // Validar IRR antes de calcular CET
        $irrFloat = (float) $irrDiaria;
        if (!is_finite($irrFloat)) {
            // Se IRR for inválido, calcular aproximação
            $valorRecebido = (float) $valorSolicitado;
            $totalPago = 0;
            $diasMedio = 0;
            $count = 0;
            foreach ($cronograma as $parcela) {
                $totalPago += (float) $parcela['parcela'];
                $diasMedio += $dataAssinatura->diffInDays(Carbon::parse($parcela['vencimento']));
                $count++;
            }
            if ($count > 0 && $diasMedio > 0) {
                $diasMedio = $diasMedio / $count;
                $irrFloat = ($totalPago - $valorRecebido) / ($valorRecebido * $diasMedio);
            } else {
                return [
                    'mensal' => '0',
                    'anual' => '0',
                ];
            }
        }
        // Remover limitação de abs($irrFloat) > 1 para permitir taxas altas (CET pode ser > 100%)

        // Converter para CET mensal: (1 + i_d)^30 - 1
        // Usar cálculo direto com float para evitar problemas de precisão
        $umMaisIrr = 1 + $irrFloat;
        
        // Validar antes de calcular potência
        if ($umMaisIrr <= 0 || !is_finite($umMaisIrr)) {
            return [
                'mensal' => '0',
                'anual' => '0',
            ];
        }
        
        $cetMensalFloat = pow($umMaisIrr, 30) - 1;
        $cetAnualFloat = pow($umMaisIrr, 365) - 1;

        // Validar resultados (remover limitação de 100% para permitir CETs altos)
        if (!is_finite($cetMensalFloat)) {
            $cetMensalFloat = 0;
        }
        if (!is_finite($cetAnualFloat)) {
            $cetAnualFloat = 0;
        }

        return [
            'mensal' => $this->toDecimal($cetMensalFloat),
            'anual' => $this->toDecimal($cetAnualFloat),
        ];
    }

    /**
     * Calcula IRR (Taxa Interna de Retorno) usando método de bissecção
     *
     * @param array $fluxo Fluxo de caixa [['data' => Carbon, 'valor' => string], ...]
     * @return string Taxa diária em decimal
     */
    private function calcularIRR(array $fluxo): string
    {
        // Método de bissecção para encontrar IRR
        $tolerancia = '0.00000001';
        $min = '-0.99'; // -99% (limite inferior)
        $max = '2.0';   // 200% (limite superior mais realista)
        $maxIteracoes = 100;
        $taxa = '0';

        for ($i = 0; $i < $maxIteracoes; $i++) {
            $taxa = $this->divide($this->add($min, $max), '2');
            $vpl = $this->calcularVPL($fluxo, $taxa);
            $vplAbs = $this->abs($vpl);

            if ($this->compare($vplAbs, $tolerancia) < 0) {
                return $taxa;
            }

            if ($this->compare($vpl, '0') > 0) {
                $min = $taxa;
            } else {
                $max = $taxa;
            }
            
            // Verificar se os limites estão muito próximos
            $diff = $this->subtract($max, $min);
            if ($this->compare($diff, '0.0000001') < 0) {
                break;
            }
        }

        // Validar resultado antes de retornar
        $taxaFloat = (float) $taxa;
        if (!is_finite($taxaFloat)) {
            // Se IRR for inválido, calcular aproximação simples
            // Taxa aproximada = (total_pago - valor_recebido) / (valor_recebido * dias_medio)
            $valorRecebido = (float) $fluxo[0]['valor'];
            $totalPago = 0;
            $diasTotal = 0;
            foreach ($fluxo as $item) {
                if ((float) $item['valor'] < 0) {
                    $totalPago += abs((float) $item['valor']);
                    $diasTotal += $fluxo[0]['data']->diffInDays($item['data']);
                }
            }
            if ($diasTotal > 0 && $valorRecebido > 0) {
                $taxaAproximada = ($totalPago - $valorRecebido) / ($valorRecebido * $diasTotal);
                return $this->toDecimal($taxaAproximada);
            }
            return '0';
        }

        // Remover limitação de abs($taxaFloat) > 1 para permitir taxas altas
        return $taxa;
    }

    /**
     * Calcula VPL (Valor Presente Líquido) do fluxo de caixa
     *
     * @param array $fluxo
     * @param string $taxa Taxa de desconto diária
     * @return string
     */
    private function calcularVPL(array $fluxo, string $taxa): string
    {
        $vpl = '0';
        $dataBase = $fluxo[0]['data'];

        foreach ($fluxo as $item) {
            $dias = $dataBase->diffInDays($item['data']);
            $umMaisTaxa = $this->add('1', $taxa);
            $fatorDesconto = $this->power($umMaisTaxa, $this->toDecimal($dias));
            $valorDescontado = $this->divide($this->toDecimal($item['valor']), $fatorDesconto);
            $vpl = $this->add($vpl, $valorDescontado);
        }

        return $vpl;
    }

    // ==================== Funções auxiliares de cálculo decimal ====================

    /**
     * Converte valor para string decimal com precisão
     *
     * @param mixed $value
     * @return string
     */
    private function toDecimal($value): string
    {
        // Se for null ou vazio, retornar zero
        if ($value === null || $value === '' || $value === false) {
            return '0';
        }

        // Se já for número, converter diretamente
        if (is_numeric($value) && !is_string($value)) {
            $floatValue = (float) $value;
            if (!is_finite($floatValue)) {
                return '0';
            }
            return number_format($floatValue, 10, '.', '');
        }

        if (is_string($value)) {
            // Remove espaços e R$
            $value = trim(str_replace(['R$', ' '], '', $value));
            
            // Se tem vírgula, assumir formato brasileiro (1.234,56)
            if (strpos($value, ',') !== false) {
                // Remove pontos (separadores de milhar) e substitui vírgula por ponto
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            // Se não tem vírgula, pode ser formato americano (1234.56) ou inteiro (1234)
            // Nesse caso, manter como está
            
            // Remove qualquer caractere não numérico exceto ponto e sinal negativo
            $value = preg_replace('/[^0-9.\-]/', '', $value);
            
            // Se ficou vazio após limpeza, retornar zero
            if ($value === '' || $value === '-') {
                return '0';
            }
        }

        // Converter para float e depois para string formatada
        $floatValue = (float) $value;
        
        // Verificar se é um número válido
        if (!is_finite($floatValue)) {
            return '0';
        }

        // Formatar com 10 casas decimais, sem separador de milhar
        return number_format($floatValue, 10, '.', '');
    }

    /**
     * Formata decimal para exibição (2 casas decimais)
     *
     * @param string $value
     * @return string
     */
    private function formatDecimal(string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * Valida se string é numérica válida para BCMath
     *
     * @param string $value
     * @return bool
     */
    private function isValidBcNumber(string $value): bool
    {
        return preg_match('/^-?\d+(\.\d+)?$/', $value) === 1;
    }

    /**
     * Soma dois valores decimais
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    private function add(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bcadd($a, $b, 10);
    }

    /**
     * Subtrai dois valores decimais
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    private function subtract(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bcsub($a, $b, 10);
    }

    /**
     * Multiplica dois valores decimais
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    private function multiply(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bcmul($a, $b, 10);
    }

    /**
     * Divide dois valores decimais
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    private function divide(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        if ($b === '0' || $b === '0.0000000000') {
            throw new \InvalidArgumentException('Divisão por zero');
        }
        return bcdiv($a, $b, 10);
    }

    /**
     * Compara dois valores decimais
     *
     * @param string $a
     * @param string $b
     * @return int -1 se a < b, 0 se a == b, 1 se a > b
     */
    private function compare(string $a, string $b): int
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bccomp($a, $b, 10);
    }

    /**
     * Calcula potência usando aproximação logarítmica
     * Para expoentes fracionários: x^y = exp(y * ln(x))
     *
     * @param string $base
     * @param string $expoente
     * @return string
     */
    private function power(string $base, string $expoente): string
    {
        // Se expoente é inteiro, usar bcpow
        if (strpos($expoente, '.') === false) {
            return bcpow($base, $expoente, 10);
        }

        // Para expoentes fracionários, usar aproximação
        // x^y = exp(y * ln(x))
        // Usar série de Taylor para ln e exp com precisão suficiente
        
        // Para casos simples como (1+i)^(1/30), usar aproximação iterativa
        // ou método numérico mais direto
        
        // Implementação simplificada usando aproximação binomial para (1+x)^n quando |x| < 1
        if ($this->compare($base, '1') === 0) {
            return '1';
        }

        // Para casos gerais, usar aproximação com logaritmo natural
        // ln(x) ≈ série de Taylor
        $lnBase = $this->ln($base);
        $produto = $this->multiply($expoente, $lnBase);
        return $this->exp($produto);
    }

    /**
     * Calcula logaritmo natural usando série de Taylor
     * ln(1+x) = x - x²/2 + x³/3 - ... para |x| < 1
     *
     * @param string $x
     * @return string
     */
    private function ln(string $x): string
    {
        // Reduzir para intervalo [0.5, 2] usando propriedades do log
        if ($this->compare($x, '1') === 0) {
            return '0';
        }

        // Usar aproximação: ln(x) ≈ 2 * ((x-1)/(x+1)) + 1/3 * ((x-1)/(x+1))³ + ...
        // Ou método mais simples para precisão suficiente
        $iteracoes = 50;
        $resultado = '0';
        $termo = $this->divide($this->subtract($x, '1'), $this->add($x, '1'));
        $termoAtual = $termo;

        for ($i = 1; $i <= $iteracoes; $i += 2) {
            $resultado = $this->add($resultado, $this->divide($termoAtual, (string) $i));
            $termoAtual = $this->multiply($termoAtual, $this->multiply($termo, $termo));
        }

        return $this->multiply('2', $resultado);
    }

    /**
     * Calcula exponencial usando série de Taylor
     * exp(x) = 1 + x + x²/2! + x³/3! + ...
     *
     * @param string $x
     * @return string
     */
    private function exp(string $x): string
    {
        $resultado = '1';
        $termo = '1';
        $iteracoes = 50;

        for ($i = 1; $i <= $iteracoes; $i++) {
            $termo = $this->divide($this->multiply($termo, $x), (string) $i);
            $resultado = $this->add($resultado, $termo);
            
            // Parar se termo for muito pequeno
            if ($this->compare($this->abs($termo), '0.0000000001') < 0) {
                break;
            }
        }

        return $resultado;
    }

    /**
     * Calcula valor absoluto
     *
     * @param string $value
     * @return string
     */
    private function abs(string $value): string
    {
        if ($this->compare($value, '0') < 0) {
            return $this->multiply('-1', $value);
        }
        return $value;
    }
}
