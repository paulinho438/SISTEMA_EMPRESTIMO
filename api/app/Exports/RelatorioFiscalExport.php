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
    protected $calculoFiscalService;

    public function __construct($companyId, $dataInicio, $dataFim)
    {
        $this->companyId = $companyId;
        $this->dataInicio = $dataInicio;
        $this->dataFim = $dataFim;
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
            $this->dataFim
        );

        return [
            new RelatorioFiscalResumoSheet($relatorio),
            new RelatorioFiscalMovimentacoesSheet($relatorio['movimentacoes']),
            new RelatorioFiscalDespesasSheet($relatorio['despesas']),
        ];
    }
}

