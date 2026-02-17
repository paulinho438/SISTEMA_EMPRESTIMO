<?php

namespace App\Services;

use Carbon\Carbon;

class LoanSimulationService
{
    // IOF adicional
    const IOF_ADICIONAL_TAX = 0.0038; // 0,38%

    // IOF diário (Brasil)
    const IOF_DIARIO_TAX_PADRAO = 0.000082; // 0,0082% a.d.

    // ✅ Para bater com o print: sistema exibe 0,0027% (não 0,00274%)
    const IOF_DIARIO_TAX_SIMPLES = 0.000027; // 0,0027% a.d.

    const DIAS_MES_COMERCIAL = 30;

    public function simulate(array $inputs): array
    {
        $valorSolicitado      = $this->toDecimal($inputs['valor_solicitado']);
        $taxaJurosMensal      = $this->toDecimal($inputs['taxa_juros_mensal']); // ex.: "0.20" para 20% a.m.
        $quantidadeParcelas   = (int) $inputs['quantidade_parcelas'];
        $dataAssinatura       = Carbon::parse($inputs['data_assinatura']);
        $dataPrimeiraParcela  = Carbon::parse($inputs['data_primeira_parcela']);
        $calcularIOF          = $inputs['calcular_iof'] ?? true;
        $simplesNacional      = (bool)($inputs['simples_nacional'] ?? false);

        // ✅ taxa diária equivalente: i_d = (1+i_m)^(1/30) - 1  (usando pow/float para bater com sistemas)
        $taxaJurosDiaria = $this->calcularTaxaDiaria($taxaJurosMensal);

        // IOF adicional (sempre em cima do valor solicitado)
        $iofAdicional = $calcularIOF
            ? $this->multiply($valorSolicitado, $this->toDecimal(self::IOF_ADICIONAL_TAX))
            : '0';

        // ✅ IOF diário para bater com o print:
        // IOF_diario = principal * aliquota_diaria * dias_corridos(assinatura -> último vencimento)
        $iofDiario = '0';
        if ($calcularIOF) {
            $taxaIofDiaria = $simplesNacional
                ? $this->toDecimal(self::IOF_DIARIO_TAX_SIMPLES)
                : $this->toDecimal(self::IOF_DIARIO_TAX_PADRAO);

            // último vencimento = primeira parcela + (n-1) dias (parcelas diárias)
            $dataUltimaParcela = $dataPrimeiraParcela->copy()->addDays($quantidadeParcelas - 1);

            // dias corridos entre assinatura e último vencimento
            $diasIOF = (string) $dataAssinatura->diffInDays($dataUltimaParcela);

            // IOF diário = principal * taxa_diaria * dias
            $iofDiario = $this->multiply(
                $this->multiply($valorSolicitado, $taxaIofDiaria),
                $this->toDecimal($diasIOF)
            );
        }

        $iofTotal      = $this->add($iofAdicional, $iofDiario);
        $valorContrato = $this->add($valorSolicitado, $iofTotal);

        // ✅ PMT usando float/pow para ficar igual ao print (ex.: 26,75)
        $pmtExata   = $this->calcularPMT($valorContrato, $taxaJurosDiaria, $quantidadeParcelas);
        $pmtDisplay = $this->formatDecimal($pmtExata); // só para exibir

        $iof = [
            'adicional' => $this->formatDecimal($iofAdicional),
            'diario'    => $this->formatDecimal($iofDiario),
            'total'     => $this->formatDecimal($iofTotal),
        ];

        // Cronograma (cálculo usa PMT exata; exibição arredonda)
        $cronograma = $this->gerarCronograma(
            $valorContrato,
            $taxaJurosDiaria,
            $quantidadeParcelas,
            $dataPrimeiraParcela,
            $pmtExata
        );

        // ✅ Total das parcelas: somar as parcelas EXATAS do cronograma e só então formatar (isso tende a bater com 534,94)
        $totalParcelas = $this->somarParcelasExatas($cronograma);

        // CET: IRR diária -> mensal (30d) -> anual (12m)
        $cet = $this->calcularCET($valorSolicitado, $cronograma, $dataAssinatura);

        return [
            'inputs' => [
                'valor_solicitado'        => $this->formatDecimal($valorSolicitado),
                'taxa_juros_mensal'       => $this->formatDecimal($taxaJurosMensal),
                'quantidade_parcelas'     => $quantidadeParcelas,
                'data_assinatura'         => $dataAssinatura->format('Y-m-d'),
                'data_primeira_parcela'   => $dataPrimeiraParcela->format('Y-m-d'),
                'modelo_amortizacao'      => $inputs['modelo_amortizacao'] ?? 'price',
                'periodo_amortizacao'     => $inputs['periodo_amortizacao'] ?? 'diario',
                'simples_nacional'        => $simplesNacional,
            ],
            'taxas' => [
                'juros_mensal' => $this->formatDecimal($taxaJurosMensal),
                'juros_diario' => $this->formatDecimal($taxaJurosDiaria),
            ],
            'iof' => $iof,
            'valor_contrato' => $this->formatDecimal($valorContrato),
            'parcela' => $pmtDisplay,
            'cronograma' => $cronograma,
            'totais' => [
                'total_parcelas' => $this->formatDecimal($totalParcelas),
                'cet_mes'        => $this->formatDecimal($cet['mensal']),
                'cet_ano'        => $this->formatDecimal($cet['anual']),
                'juros_acerto'   => '0.00',
            ],
        ];
    }

    /**
     * ✅ i_d = (1 + i_m)^(1/30) - 1
     * Usando float/pow para bater com a calculadora do sistema.
     */
    private function calcularTaxaDiaria(string $taxaMensal): string
    {
        $im = (float) $this->toDecimal($taxaMensal);
        if (!is_finite($im)) return '0';

        $id = pow(1.0 + $im, 1.0 / self::DIAS_MES_COMERCIAL) - 1.0;
        if (!is_finite($id)) return '0';

        return $this->toDecimal($id);
    }

    /**
     * ✅ PMT (Price)
     * PMT = PV * [ i / (1 - (1 + i)^(-n)) ]
     * Usando float/pow para reduzir diferenças de centavos.
     */
    private function calcularPMT(string $valorPresente, string $taxaDiaria, int $numeroParcelas): string
    {
        $pv = (float) $this->toDecimal($valorPresente);
        $i  = (float) $this->toDecimal($taxaDiaria);
        $n  = (int) $numeroParcelas;

        if ($n <= 0) return '0';
        if (abs($i) < 1e-18) {
            return $this->toDecimal($pv / $n);
        }

        $den = 1.0 - pow(1.0 + $i, -$n);
        if (abs($den) < 1e-18) return '0';

        $pmt = $pv * ($i / $den);
        if (!is_finite($pmt)) return '0';

        return $this->toDecimal($pmt);
    }

    /**
     * Cronograma: cálculo com alta precisão, exibição em 2 casas.
     * Para bater com o print: datas diárias a partir da dataPrimeiraParcela.
     */
    private function gerarCronograma(
        string $valorContrato,
        string $taxaDiaria,
        int $quantidadeParcelas,
        Carbon $dataPrimeiraParcela,
        string $pmtExata
    ): array {
        $cronograma   = [];
        $saldoDevedor = $this->toDecimal($valorContrato);

        for ($k = 1; $k <= $quantidadeParcelas; $k++) {
            $dataVencimento = $dataPrimeiraParcela->copy()->addDays($k - 1);

            // juros do período (preciso)
            $juros = $this->multiply($saldoDevedor, $taxaDiaria);

            // amortização = pmt - juros
            $amortizacao = $this->subtract($pmtExata, $juros);

            // última parcela ajusta para zerar saldo (sem “saldo negativo”)
            $parcelaExata = $pmtExata;
            if ($k === $quantidadeParcelas) {
                $amortizacao = $saldoDevedor;
                $parcelaExata = $this->add($juros, $amortizacao);
            }

            // novo saldo
            $saldoDevedor = $this->subtract($saldoDevedor, $amortizacao);
            if ($this->compare($saldoDevedor, '0') < 0) {
                $saldoDevedor = '0';
            }

            $cronograma[] = [
                'numero'        => $k,
                // exibe arredondado (como o print)
                'parcela'       => $this->formatDecimal($parcelaExata),
                'vencimento'    => $dataVencimento->format('Y-m-d'),
                'juros'         => $this->formatDecimal($juros),
                'amortizacao'   => $this->formatDecimal($amortizacao),
                'saldo_devedor' => $this->formatDecimal($saldoDevedor),

                // ✅ campo interno para total e CET (não expõe no front se você não quiser)
                '_parcela_exata' => $parcelaExata,
            ];
        }

        return $cronograma;
    }

    /**
     * ✅ Soma usando parcela EXATA (não a exibida), para bater com totais do sistema.
     */
    private function somarParcelasExatas(array $cronograma): string
    {
        $total = '0';
        foreach ($cronograma as $p) {
            $parcelaExata = isset($p['_parcela_exata']) ? $this->toDecimal($p['_parcela_exata']) : $this->toDecimal($p['parcela']);
            $total = $this->add($total, $parcelaExata);
        }
        return $total;
    }

    /**
 * CET (Custo Efetivo Total) mensal e anual
 *
 * ✅ Para bater com o print:
 * - Usa TIR diária por períodos (k = 1..n) com parcelas em 2 casas (como exibidas)
 * - CET_mês = (1 + i_d)^30 - 1
 * - CET_ano = (1 + CET_mês)^12 - 1
 * - Retorna em PERCENTUAL (ex.: 21.86), pois o front usa formatDecimal direto
 */
private function calcularCET(string $valorSolicitado, array $cronograma, Carbon $dataAssinatura): array
{
    // Entrada (cliente recebe)
    $pv = (float) $this->toDecimal($valorSolicitado);
    if (!is_finite($pv) || $pv <= 0) {
        return ['mensal' => '0', 'anual' => '0'];
    }

    // Parcelas (usar valor exibido em 2 casas para bater com o print)
    $parcelas = [];
    foreach ($cronograma as $p) {
        // usa o que você já coloca como string "26.75" etc.
        $parcelas[] = (float) $this->toDecimal($p['parcela']);
    }

    // Calcula TIR diária por bissecção (por períodos 1..n)
    $irrDiaria = $this->irrDiariaPorPeriodos($pv, $parcelas);
    if (!is_finite($irrDiaria) || $irrDiaria <= -0.999999999) {
        return ['mensal' => '0', 'anual' => '0'];
    }

    // CET mensal (decimal)
    $cetMensalDec = pow(1.0 + $irrDiaria, self::DIAS_MES_COMERCIAL) - 1.0;

    // CET anual (decimal) — anualiza via mensal (como o print)
    $cetAnualDec  = pow(1.0 + $cetMensalDec, 12) - 1.0;

    if (!is_finite($cetMensalDec)) $cetMensalDec = 0.0;
    if (!is_finite($cetAnualDec))  $cetAnualDec  = 0.0;

    // ✅ Retornar em % (não em decimal), pois o simulate() faz formatDecimal direto
    $cetMensalPct = $cetMensalDec * 100.0;
    $cetAnualPct  = $cetAnualDec  * 100.0;

    return [
        'mensal' => $this->toDecimal($cetMensalPct),
        'anual'  => $this->toDecimal($cetAnualPct),
    ];
}

/**
 * IRR diária por períodos (k=1..n) usando bissecção.
 * Resolve: PV = Σ parcela_k / (1+r)^k
 */
private function irrDiariaPorPeriodos(float $pv, array $parcelas): float
{
    $n = count($parcelas);
    if ($n === 0) return 0.0;

    // NPV(r) = PV - Σ parcela/(1+r)^k
    $npv = function (float $r) use ($pv, $parcelas): float {
        $base = 1.0 + $r;
        if ($base <= 0) return NAN;

        $soma = 0.0;
        $k = 1;
        foreach ($parcelas as $pmt) {
            $soma += $pmt / pow($base, $k);
            $k++;
        }
        return $pv - $soma;
    };

    // Intervalo inicial
    $low  = -0.999; // quase -100%
    $high =  1.0;   // 100% ao dia (começa aqui e expande se precisar)

    $fLow  = $npv($low);
    $fHigh = $npv($high);

    // Expandir high até ter mudança de sinal (ou limite)
    $tries = 0;
    while (is_finite($fLow) && is_finite($fHigh) && ($fLow * $fHigh) > 0 && $tries < 60) {
        $high *= 2.0; // 200%, 400%, 800% ao dia...
        $fHigh = $npv($high);
        $tries++;
        if ($high > 1e6) break;
    }

    // Se não achou intervalo, retorna 0
    if (!is_finite($fLow) || !is_finite($fHigh) || ($fLow * $fHigh) > 0) {
        return 0.0;
    }

    // Bissecção
    $tol = 1e-12;
    for ($i = 0; $i < 400; $i++) {
        $mid = ($low + $high) / 2.0;
        $fMid = $npv($mid);

        if (!is_finite($fMid)) return 0.0;
        if (abs($fMid) < $tol) return $mid;

        if (($fLow * $fMid) <= 0) {
            $high = $mid;
            $fHigh = $fMid;
        } else {
            $low = $mid;
            $fLow = $fMid;
        }
    }

    return ($low + $high) / 2.0;
}

    /**
     * IRR diária por bissecção (float) com NPV por dias corridos.
     */
    private function calcularIRRFloat(array $fluxo): float
    {
        $min = -0.50;
        $max =  0.50;

        $vplMin = $this->npvFloat($fluxo, $min);
        $vplMax = $this->npvFloat($fluxo, $max);

        // expandir até achar sinais opostos
        $tent = 0;
        while (($vplMin * $vplMax) > 0 && $tent < 30) {
            if ($vplMin > 0 && $vplMax > 0) {
                $max *= 2;
                $vplMax = $this->npvFloat($fluxo, $max);
            } else {
                $min *= 2;
                $vplMin = $this->npvFloat($fluxo, $min);
            }
            $tent++;
        }

        if (($vplMin * $vplMax) > 0) {
            return 0.0;
        }

        $tol = 1e-10;
        for ($i = 0; $i < 300; $i++) {
            $mid = ($min + $max) / 2.0;
            $vpl = $this->npvFloat($fluxo, $mid);

            if (!is_finite($vpl)) return 0.0;

            if (abs($vpl) < $tol) {
                return $mid;
            }

            if (($vplMin * $vpl) <= 0) {
                $max = $mid;
                $vplMax = $vpl;
            } else {
                $min = $mid;
                $vplMin = $vpl;
            }
        }

        return ($min + $max) / 2.0;
    }

    private function npvFloat(array $fluxo, float $taxa): float
    {
        $dataBase = $fluxo[0]['data'];
        $vpl = 0.0;

        foreach ($fluxo as $item) {
            /** @var Carbon $d */
            $d = $item['data'];
            $dias = $dataBase->diffInDays($d);
            $valor = (float) $item['valor'];

            if ($dias === 0) {
                $vpl += $valor;
            } else {
                $base = 1.0 + $taxa;
                if ($base <= 0) return NAN;
                $vpl += $valor / pow($base, $dias);
            }
        }

        return $vpl;
    }

    // ==================== auxiliares decimais ====================

    private function toDecimal($value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '0';
        }

        if (is_numeric($value) && !is_string($value)) {
            $f = (float) $value;
            if (!is_finite($f)) return '0';
            return number_format($f, 10, '.', '');
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

        $f = (float) $value;
        if (!is_finite($f)) return '0';
        return number_format($f, 10, '.', '');
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
}
