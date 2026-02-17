<?php

namespace Tests\Unit;

use App\Services\LoanSimulationService;
use PHPUnit\Framework\TestCase;

class LoanSimulationServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoanSimulationService();
    }

    /**
     * Testa o cenário completo do exemplo fornecido
     * PV_solicitado=500,00
     * i_m=20% a.m.
     * N=20
     * data_assinatura=2026-02-12
     * data_primeira=2026-02-13
     */
    public function testSimulacaoCompletaExemplo()
    {
        $inputs = [
            'valor_solicitado' => '500.00',
            'taxa_juros_mensal' => '0.20', // 20% em decimal
            'quantidade_parcelas' => 20,
            'data_assinatura' => '2026-02-12',
            'data_primeira_parcela' => '2026-02-13',
            'modelo_amortizacao' => 'price',
            'periodo_amortizacao' => 'diario',
            'calcular_iof' => true,
        ];

        $result = $this->service->simulate($inputs);

        // Validar IOF
        $this->assertEquals('1.90', $result['iof']['adicional'], 'IOF adicional deve ser 1,90');
        $this->assertEquals('0.44', $result['iof']['diario'], 'IOF diário deve ser 0,44');
        $this->assertEquals('2.34', $result['iof']['total'], 'IOF total deve ser 2,34');

        // Validar valor do contrato
        $this->assertEquals('502.34', $result['valor_contrato'], 'Valor do contrato deve ser 502,34');

        // Validar PMT (parcela)
        $this->assertEquals('26.76', $result['parcela'], 'Parcela deve ser 26,76');

        // Validar primeira parcela do cronograma
        $primeiraParcela = $result['cronograma'][0];
        $this->assertEquals(1, $primeiraParcela['numero']);
        $this->assertEquals('26.76', $primeiraParcela['parcela']);
        $this->assertEquals('2026-02-13', $primeiraParcela['vencimento']);
        $this->assertEquals('3.06', $primeiraParcela['juros'], 'Juros da primeira parcela devem ser 3,06');
        $this->assertEquals('23.69', $primeiraParcela['amortizacao'], 'Amortização da primeira parcela deve ser 23,69');
        $this->assertEquals('478.65', $primeiraParcela['saldo_devedor'], 'Saldo devedor após primeira parcela deve ser 478,65');

        // Validar última parcela (saldo deve ser 0,00)
        $ultimaParcela = $result['cronograma'][count($result['cronograma']) - 1];
        $this->assertEquals(20, $ultimaParcela['numero']);
        $this->assertEquals('0.00', $ultimaParcela['saldo_devedor'], 'Saldo devedor final deve ser 0,00');

        // Validar total das parcelas
        $this->assertEquals('535.11', $result['totais']['total_parcelas'], 'Total das parcelas deve ser 535,11');

        // Validar que há 20 parcelas no cronograma
        $this->assertCount(20, $result['cronograma'], 'Deve haver 20 parcelas no cronograma');
    }

    /**
     * Testa cálculo de IOF isoladamente
     */
    public function testCalculoIOF()
    {
        $inputs = [
            'valor_solicitado' => '500.00',
            'taxa_juros_mensal' => '0.20',
            'quantidade_parcelas' => 20,
            'data_assinatura' => '2026-02-12',
            'data_primeira_parcela' => '2026-02-13',
            'modelo_amortizacao' => 'price',
            'periodo_amortizacao' => 'diario',
            'calcular_iof' => true,
        ];

        $result = $this->service->simulate($inputs);

        // IOF adicional: 0,38% de 500 = 1,90
        $this->assertEquals('1.90', $result['iof']['adicional']);

        // IOF diário: calculado por parcela individual
        // Para cada parcela: valor_parcela × 0,0082% × dias_entre_assinatura_e_vencimento
        // Soma de todos os IOFs individuais = 0,44
        $this->assertEquals('0.44', $result['iof']['diario']);

        // Total: 1,90 + 0,44 = 2,34
        $this->assertEquals('2.34', $result['iof']['total']);
    }

    /**
     * Testa que o saldo devedor final é sempre zero
     */
    public function testSaldoDevedorFinalZero()
    {
        $inputs = [
            'valor_solicitado' => '1000.00',
            'taxa_juros_mensal' => '0.15',
            'quantidade_parcelas' => 10,
            'data_assinatura' => '2026-01-01',
            'data_primeira_parcela' => '2026-01-02',
            'modelo_amortizacao' => 'price',
            'periodo_amortizacao' => 'diario',
            'calcular_iof' => true,
        ];

        $result = $this->service->simulate($inputs);
        $ultimaParcela = $result['cronograma'][count($result['cronograma']) - 1];

        $this->assertEquals('0.00', $ultimaParcela['saldo_devedor']);
    }

    /**
     * Testa que todas as parcelas têm valores válidos
     */
    public function testParcelasValidas()
    {
        $inputs = [
            'valor_solicitado' => '500.00',
            'taxa_juros_mensal' => '0.20',
            'quantidade_parcelas' => 20,
            'data_assinatura' => '2026-02-12',
            'data_primeira_parcela' => '2026-02-13',
            'modelo_amortizacao' => 'price',
            'periodo_amortizacao' => 'diario',
            'calcular_iof' => true,
        ];

        $result = $this->service->simulate($inputs);

        foreach ($result['cronograma'] as $parcela) {
            $this->assertGreaterThan('0', $parcela['parcela'], 'Parcela deve ser maior que zero');
            $this->assertGreaterThanOrEqual('0', $parcela['juros'], 'Juros não podem ser negativos');
            $this->assertGreaterThan('0', $parcela['amortizacao'], 'Amortização deve ser maior que zero');
            $this->assertGreaterThanOrEqual('0', $parcela['saldo_devedor'], 'Saldo devedor não pode ser negativo');
        }
    }

    /**
     * Testa cálculo sem IOF
     */
    public function testSimulacaoSemIOF()
    {
        $inputs = [
            'valor_solicitado' => '500.00',
            'taxa_juros_mensal' => '0.20',
            'quantidade_parcelas' => 20,
            'data_assinatura' => '2026-02-12',
            'data_primeira_parcela' => '2026-02-13',
            'modelo_amortizacao' => 'price',
            'periodo_amortizacao' => 'diario',
            'calcular_iof' => false,
        ];

        $result = $this->service->simulate($inputs);

        $this->assertEquals('0.00', $result['iof']['adicional']);
        $this->assertEquals('0.00', $result['iof']['diario']);
        $this->assertEquals('0.00', $result['iof']['total']);
        $this->assertEquals('500.00', $result['valor_contrato'], 'Valor do contrato sem IOF deve ser igual ao solicitado');
    }
}
