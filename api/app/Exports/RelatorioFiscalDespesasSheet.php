<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class RelatorioFiscalDespesasSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths
{
    protected $despesas;

    public function __construct($despesas)
    {
        $this->despesas = $despesas;
    }

    public function collection()
    {
        $data = $this->despesas->map(function ($despesa) {
            return [
                $despesa->dt_baixa ? \Carbon\Carbon::parse($despesa->dt_baixa)->format('d/m/Y') : '',
                $despesa->descricao ?? '',
                $despesa->fornecedor ? ($despesa->fornecedor->nome_completo ?? '') : '',
                $despesa->tipodoc ?? '',
                'R$ ' . number_format($despesa->valor ?? 0, 2, ',', '.'),
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'Data de Pagamento',
            'Descrição',
            'Fornecedor',
            'Tipo Documento',
            'Valor',
        ];
    }

    public function title(): string
    {
        return 'Despesas';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 50,
            'C' => 40,
            'D' => 20,
            'E' => 20,
        ];
    }
}

