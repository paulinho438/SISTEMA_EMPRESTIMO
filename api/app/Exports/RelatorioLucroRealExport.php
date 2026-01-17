<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RelatorioLucroRealExport implements WithMultipleSheets
{
    protected $relatorio;

    public function __construct(array $relatorio)
    {
        $this->relatorio = $relatorio;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [
            new RelatorioLucroRealResumoSheet($this->relatorio),
            new RelatorioLucroRealEmprestimosSheet($this->relatorio['detalhamento_emprestimos']),
        ];

        // Adicionar aba de movimentações detalhadas
        if (!empty($this->relatorio['detalhamento_movimentacoes'])) {
            $sheets[] = new RelatorioLucroRealMovimentacoesSheet($this->relatorio['detalhamento_movimentacoes']);
        }

        return $sheets;
    }
}
