<?php

namespace App\Exports;

use App\Services\CalculoFiscalService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RelatorioFiscalExport implements WithMultipleSheets
{
    protected $companyId;
    protected $dataInicio;
    protected $dataFim;
    protected $tipo;
    protected $calculoFiscalService;

    public function __construct($companyId, $dataInicio, $dataFim, $tipo = 'presumido')
    {
        $this->companyId = $companyId;
        $this->dataInicio = $dataInicio;
        $this->dataFim = $dataFim;
        $this->tipo = $tipo;
        $this->calculoFiscalService = new CalculoFiscalService();
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $relatorio = $this->calculoFiscalService->gerarRelatorioFiscal(
            $this->companyId,
            $this->dataInicio,
            $this->dataFim,
            $this->tipo
        );

        $sheets = [
            new RelatorioFiscalResumoSheet($relatorio),
            new RelatorioFiscalMovimentacoesSheet($relatorio['movimentacoes']),
            new RelatorioFiscalDespesasSheet($relatorio['despesas']),
        ];

        // Se for cálculo proporcional, adicionar aba de detalhamento por empréstimo
        if ($this->tipo === 'proporcional' && !empty($relatorio['detalhamento_emprestimos'])) {
            $sheets[] = new RelatorioFiscalDetalhamentoSheet($relatorio['detalhamento_emprestimos']);
        }

        return $sheets;
    }
}

