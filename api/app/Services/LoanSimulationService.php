<?php

namespace App\Services;

use Carbon\Carbon;

class LoanSimulationService
{
    // IOF adicional
    const IOF_ADICIONAL_TAX = 0.0038; // 0,38%

    // IOF diário (Brasil)
    // Padrão (PF e PJ fora do Simples): 0,0082% ao dia
    const IOF_DIARIO_TAX_PADRAO = 0.000082; // 0,0082% a.d.

    // Simples/MEI (até R$ 30 mil): 0,00274% ao dia (aprox. 0,0027% exibido na tela)
    const IOF_DIARIO_TAX_SIMPLES = 0.0000274; // 0,00274% a.d.

    const DIAS_MES_COMERCIAL = 30;

    public function simulate(array $inputs): array
    {
        $valorSolicitado = $this->toDecimal($inputs['valor_solicitado']);
        $taxaJurosMensal = $this->toDecimal($inputs['taxa_juros_mensal']);
        $quantidadeParcelas = (int) $inputs['quantidade_parcelas'];
        $dataAssinatura = Carbon::parse($inputs['data_assinatura']);
        $dataPrimeiraParcela = Carbon::parse($inputs['data_primeira_parcela']);
        $calcularIOF = $inputs['calcular_iof'] ?? true;

        $simplesNacional = (bool)($inputs['simples_nacional'] ?? false);

        // taxa diária equivalente (juros compostos)
        $taxaJurosDiaria = $this->calcularTaxaDiaria($taxaJurosMensal);

        // IOF adicional (sempre em cima do valor solicitado)
        $iofAdicional = $calcularIOF
            ? $this->multiply($valorSolicitado, $this->toDecimal(self::IOF_ADICIONAL_TAX))
            : '0';

        // -------------------------
        // IOF diário (CORRETO): usa prazo médio ponderado (PMP)
        // -------------------------
        $iofDiario = '0';

        if ($calcularIOF) {
            $taxaIofDiaria = $simplesNacional
                ? $this->toDecimal(self::IOF_DIARIO_TAX_SIMPLES)
                : $this->toDecimal(self::IOF_DIARIO_TAX_PADRAO);

            // 1ª passada: calcula PMT/schedule somente com o principal (sem IOF)
            $pmtSemIof = $this->calcularPMT($valorSolicitado, $taxaJurosDiaria, $quantidadeParcelas);

            $cronogramaSemIof = $this->gerarCronograma(
                $valorSolicitado,
                $taxaJurosDiaria,
                $quantidadeParcelas,
                $dataPrimeiraParcela,
                $pmtSemIof
            );

            // Prazo médio ponderado (em dias) usando amortização e dias até cada vencimento
            $prazoMedioDias = $this->calcularPrazoMedioPonderado(
                $valorSolicitado,
                $cronogramaSemIof,
                $dataAssinatura
            );

            // IOF diário = principal * taxa_diaria * prazo_medio
            $iofDiario = $this->multiply(
                $this->multiply($valorSolicitado, $taxaIofDiaria),
                $prazoMedioDias
            );
        }

        // IOF total e valor do contrato
        $iofTotal = $this->add($iofAdicional, $iofDiario);
        $valorContrato = $this->add($valorSolicitado, $iofTotal);

        // PMT final com valor do contrato (inclui IOF)
        $pmt = $this->calcularPMT($valorContrato, $taxaJurosDiaria, $quantidadeParcelas);

        $iof = [
            'adicional' => $this->formatDecimal($iofAdicional),
            'diario' => $this->formatDecimal($iofDiario),
            'total' => $this->formatDecimal($iofTotal),
        ];

        // Cronograma final
        $cronograma = $this->gerarCronograma(
            $valorContrato,
            $taxaJurosDiaria,
            $quantidadeParcelas,
            $dataPrimeiraParcela,
            $pmt
        );

        $totalParcelas = $this->somarParcelas($cronograma);

        // CET: IRR diária -> mensal (30d) -> anual (12m)
        $cet = $this->calcularCET($valorSolicitado, $cronograma, $dataAssinatura);

        return [
            'inputs' => [
                'valor_solicitado' => $this->formatDecimal($valorSolicitado),
                'taxa_juros_mensal' => $this->formatDecimal($taxaJurosMensal),
                'quantidade_parcelas' => $quantidadeParcelas,
                'data_assinatura' => $dataAssinatura->format('Y-m-d'),
                'data_primeira_parcela' => $dataPrimeiraParcela->format('Y-m-d'),
                'modelo_amortizacao' => $inputs['modelo_amortizacao'] ?? 'price',
                'periodo_amortizacao' => $inputs['periodo_amortizacao'] ?? 'diario',
                'simples_nacional' => $simplesNacional,
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
     * i_d = (1 + i_m)^(1/30) - 1
     */
    private function calcularTaxaDiaria(string $taxaMensal): string
    {
        $taxaMensalDecimal = $this->toDecimal($taxaMensal);
        $umMaisTaxaMensal = $this->add('1', $taxaMensalDecimal);

        $lnBase = $this->ln($umMaisTaxaMensal);
        $expoente = $this->divide('1', (string) self::DIAS_MES_COMERCIAL);
        $produto = $this->multiply($expoente, $lnBase);
        $resultado = $this->exp($produto);

        return $this->subtract($resultado, '1');
    }

    /**
     * Prazo Médio Ponderado (dias):
     * PMP = sum(amortizacao_k * dias_k) / principal
     */
    private function calcularPrazoMedioPonderado(string $principal, array $cronograma, Carbon $dataAssinatura): string
    {
        $numerador = '0';

        foreach ($cronograma as $parcela) {
            $dias = (string) $dataAssinatura->diffInDays(Carbon::parse($parcela['vencimento']));
            $amortizacao = $this->toDecimal($parcela['amortizacao']);

            $numerador = $this->add(
                $numerador,
                $this->multiply($amortizacao, $this->toDecimal($dias))
            );
        }

        // Evitar divisão por zero
        if ($this->compare($principal, '0') <= 0) {
            return '0';
        }

        return $this->divide($numerador, $principal);
    }

    private function calcularPMT(string $valorPresente, string $taxaDiaria, int $numeroParcelas): string
    {
        $umMaisTaxa = $this->add('1', $taxaDiaria);
        $expoenteNegativo = $this->multiply('-1', $this->toDecimal($numeroParcelas));
        $potenciaNegativa = $this->power($umMaisTaxa, $expoenteNegativo);
        $denominador = $this->subtract('1', $potenciaNegativa);

        // Se i = 0, PMT = PV/n
        if ($this->compare($taxaDiaria, '0') == 0) {
            return $this->divide($valorPresente, $this->toDecimal((string)$numeroParcelas));
        }

        $fator = $this->divide($taxaDiaria, $denominador);
        return $this->multiply($valorPresente, $fator);
    }

    private function gerarCronograma(string $valorContrato, string $taxaDiaria, int $quantidadeParcelas, Carbon $dataPrimeiraParcela, string $pmt): array
    {
        $cronograma = [];
        $saldoDevedor = $valorContrato;

        for ($k = 1; $k <= $quantidadeParcelas; $k++) {
            $dataVencimento = $dataPrimeiraParcela->copy()->addDays($k - 1);

            $juros = $this->multiply($saldoDevedor, $taxaDiaria);
            $amortizacao = $this->subtract($pmt, $juros);

            if ($k === $quantidadeParcelas) {
                $amortizacao = $saldoDevedor;
                $parcelaAjustada = $this->add($juros, $amortizacao);
            } else {
                $parcelaAjustada = $pmt;
            }

            $saldoDevedor = $this->subtract($saldoDevedor, $amortizacao);
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

    private function somarParcelas(array $cronograma): string
    {
        $total = '0';
        foreach ($cronograma as $parcela) {
            $total = $this->add($total, $this->toDecimal($parcela['parcela']));
        }
        return $total;
    }

    /**
     * CET:
     * 1) IRR diária (pela data)
     * 2) CET_mês = (1+irr)^30 - 1
     * 3) CET_ano = (1+CET_mês)^12 - 1   ✅ (ajuste aqui)
     */
    private function calcularCET(string $valorSolicitado, array $cronograma, Carbon $dataAssinatura): array
    {
        $fluxo = [[
            'data' => $dataAssinatura,
            'valor' => $this->toDecimal($valorSolicitado), // entrada
        ]];

        foreach ($cronograma as $parcela) {
            $fluxo[] = [
                'data' => Carbon::parse($parcela['vencimento']),
                'valor' => $this->multiply('-1', $this->toDecimal($parcela['parcela'])),
            ];
        }

        $irrDiaria = $this->calcularIRR($fluxo);
        $irr = (float) $irrDiaria;

        if (!is_finite($irr) || abs($irr) < 1e-12) {
            return ['mensal' => '0', 'anual' => '0'];
        }

        $umMais = 1 + $irr;
        if ($umMais <= 0 || !is_finite($umMais)) {
            return ['mensal' => '0', 'anual' => '0'];
        }

        $cetMensal = pow($umMais, self::DIAS_MES_COMERCIAL) - 1;
        $cetAnual  = pow(1 + $cetMensal, 12) - 1; // ✅ anualiza via mensal

        if (!is_finite($cetMensal)) $cetMensal = 0;
        if (!is_finite($cetAnual))  $cetAnual = 0;

        return [
            'mensal' => $this->toDecimal($cetMensal),
            'anual' => $this->toDecimal($cetAnual),
        ];
    }

    /**
     * IRR por bissecção com expansão de intervalo até achar mudança de sinal.
     */
    private function calcularIRR(array $fluxo): string
    {
        $min = '-0.50';  // -50% ao dia (limite bem baixo)
        $max = '0.50';   //  50% ao dia (limite bem alto)

        $vplMin = $this->calcularVPL($fluxo, $min);
        $vplMax = $this->calcularVPL($fluxo, $max);

        // Expandir até ter sinais opostos (ou até um limite)
        $tentativas = 0;
        while ($this->compare($this->multiply($vplMin, $vplMax), '0') > 0 && $tentativas < 20) {
            // Se ambos positivos: aumenta max; se ambos negativos: diminui min
            if ($this->compare($vplMin, '0') > 0 && $this->compare($vplMax, '0') > 0) {
                $max = $this->multiply($max, '2'); // dobra
                $vplMax = $this->calcularVPL($fluxo, $max);
            } else {
                $min = $this->multiply($min, '2'); // mais negativo
                $vplMin = $this->calcularVPL($fluxo, $min);
            }
            $tentativas++;
        }

        // Se não conseguiu “bracketing”, retorna 0
        if ($this->compare($this->multiply($vplMin, $vplMax), '0') > 0) {
            return '0';
        }

        $tolerancia = '0.00000001';
        $maxIteracoes = 200;
        $taxa = '0';

        for ($i = 0; $i < $maxIteracoes; $i++) {
            $taxa = $this->divide($this->add($min, $max), '2');
            $vpl = $this->calcularVPL($fluxo, $taxa);

            if ($this->compare($this->abs($vpl), $tolerancia) < 0) {
                return $taxa;
            }

            // Decide lado mantendo mudança de sinal
            if ($this->compare($this->multiply($vplMin, $vpl), '0') <= 0) {
                $max = $taxa;
                $vplMax = $vpl;
            } else {
                $min = $taxa;
                $vplMin = $vpl;
            }
        }

        return $taxa;
    }

    private function calcularVPL(array $fluxo, string $taxa): string
    {
        $vpl = '0';
        $dataBase = $fluxo[0]['data'];
        $taxaFloat = (float) $taxa;

        // float para taxas grandes
        if (abs($taxaFloat) > 0.1) {
            $vplFloat = 0;
            foreach ($fluxo as $item) {
                $dias = $dataBase->diffInDays($item['data']);
                $valor = (float) $item['valor'];
                $vplFloat += ($dias === 0) ? $valor : $valor / pow(1 + $taxaFloat, $dias);
            }
            return $this->toDecimal($vplFloat);
        }

        foreach ($fluxo as $item) {
            $dias = $dataBase->diffInDays($item['data']);
            if ($dias === 0) {
                $vpl = $this->add($vpl, $this->toDecimal($item['valor']));
            } else {
                $umMaisTaxa = $this->add('1', $taxa);
                $fatorDesconto = $this->power($umMaisTaxa, $this->toDecimal((string)$dias));
                $valorDescontado = $this->divide($this->toDecimal($item['valor']), $fatorDesconto);
                $vpl = $this->add($vpl, $valorDescontado);
            }
        }

        return $vpl;
    }

    // ==================== auxiliares decimais (mantive os seus) ====================

    private function toDecimal($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '0';
        }

        if (is_numeric($value) && !is_string($value)) {
            $floatValue = (float) $value;
            if (!is_finite($floatValue)) return '0';
            return number_format($floatValue, 10, '.', '');
        }

        if (is_string($value)) {
            $value = trim(str_replace(['R$', ' '], '', $value));
            if (strpos($value, ',') !== false) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            $value = preg_replace('/[^0-9.\-]/', '', $value);
            if ($value === '' || $value === '-') return '0';
        }

        $floatValue = (float) $value;
        if (!is_finite($floatValue)) return '0';
        return number_format($floatValue, 10, '.', '');
    }

    private function formatDecimal(string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function isValidBcNumber(string $value): bool
    {
        return preg_match('/^-?\d+(\.\d+)?$/', $value) === 1;
    }

    private function add(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bcadd($a, $b, 10);
    }

    private function subtract(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bcsub($a, $b, 10);
    }

    private function multiply(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bcmul($a, $b, 10);
    }

    private function divide(string $a, string $b): string
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        if ($b === '0' || $b === '0.0000000000') {
            throw new \InvalidArgumentException('Divisão por zero');
        }
        return bcdiv($a, $b, 10);
    }

    private function compare(string $a, string $b): int
    {
        $a = $this->isValidBcNumber($a) ? $a : $this->toDecimal($a);
        $b = $this->isValidBcNumber($b) ? $b : $this->toDecimal($b);
        return bccomp($a, $b, 10);
    }

    private function power(string $base, string $expoente): string
    {
        if (strpos($expoente, '.') === false) {
            return bcpow($base, $expoente, 10);
        }

        if ($this->compare($base, '1') === 0) return '1';

        $lnBase = $this->ln($base);
        $produto = $this->multiply($expoente, $lnBase);
        return $this->exp($produto);
    }

    private function ln(string $x): string
    {
        if ($this->compare($x, '1') === 0) return '0';

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

    private function exp(string $x): string
    {
        $resultado = '1';
        $termo = '1';
        $iteracoes = 50;

        for ($i = 1; $i <= $iteracoes; $i++) {
            $termo = $this->divide($this->multiply($termo, $x), (string) $i);
            $resultado = $this->add($resultado, $termo);

            if ($this->compare($this->abs($termo), '0.0000000001') < 0) {
                break;
            }
        }

        return $resultado;
    }

    private function abs(string $value): string
    {
        if ($this->compare($value, '0') < 0) {
            return $this->multiply('-1', $value);
        }
        return $value;
    }
}
