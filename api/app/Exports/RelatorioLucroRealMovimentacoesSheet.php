<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class RelatorioLucroRealMovimentacoesSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $movimentacoes;

    public function __construct(array $movimentacoes)
    {
        $this->movimentacoes = $movimentacoes;
    }

    public function collection()
    {
        $data = [];

        foreach ($this->movimentacoes as $mov) {
            $data[] = [
                Carbon::parse($mov['data'])->format('d/m/Y'),
                $mov['cliente'],
                $mov['parcela'],
                'R$ ' . number_format($mov['valor_recebido'], 2, ',', '.'),
                'R$ ' . number_format($mov['lucro_real'], 2, ',', '.'),
                $mov['descricao'],
                $mov['banco'],
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Data',
            'Cliente',
            'Parcela',
            'Valor Recebido',
            'Lucro Real',
            'Descrição',
            'Banco',
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
            'B' => 30,
            'C' => 12,
            'D' => 20,
            'E' => 20,
            'F' => 40,
            'G' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
        ];
    }
}

