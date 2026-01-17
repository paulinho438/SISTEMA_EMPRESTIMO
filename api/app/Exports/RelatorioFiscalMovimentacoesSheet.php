<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class RelatorioFiscalMovimentacoesSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths
{
    protected $movimentacoes;

    public function __construct($movimentacoes)
    {
        $this->movimentacoes = $movimentacoes;
    }

    public function collection()
    {
        $data = $this->movimentacoes->map(function ($movimentacao) {
            return [
                $movimentacao->dt_movimentacao ? \Carbon\Carbon::parse($movimentacao->dt_movimentacao)->format('d/m/Y') : '',
                $movimentacao->descricao ?? '',
                $movimentacao->banco ? ($movimentacao->banco->name ?? '') : '',
                'R$ ' . number_format($movimentacao->valor ?? 0, 2, ',', '.'),
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            'Data',
            'Descrição',
            'Banco',
            'Valor',
        ];
    }

    public function title(): string
    {
        return 'Movimentações';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 50,
            'C' => 30,
            'D' => 20,
        ];
    }
}

