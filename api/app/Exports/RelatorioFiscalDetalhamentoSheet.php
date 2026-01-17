<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioFiscalDetalhamentoSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $detalhamento;

    public function __construct(array $detalhamento)
    {
        $this->detalhamento = $detalhamento;
    }

    public function collection()
    {
        $data = [];

        foreach ($this->detalhamento as $emprestimo) {
            // Linha de cabeçalho do empréstimo
            $data[] = [
                'EMPRÉSTIMO #' . $emprestimo['emprestimo_id'],
                '',
                '',
                '',
                '',
                '',
            ];

            // Informações do empréstimo
            $data[] = [
                'Cliente',
                $emprestimo['cliente'],
                '',
                '',
                '',
                '',
            ];

            $data[] = [
                'Valor Emprestado',
                'R$ ' . number_format($emprestimo['valor_emprestado'], 2, ',', '.'),
                '',
                '',
                '',
                '',
            ];

            $data[] = [
                'Lucro Total',
                'R$ ' . number_format($emprestimo['lucro_total'], 2, ',', '.'),
                '',
                '',
                '',
                '',
            ];

            $data[] = [
                'Número de Parcelas',
                $emprestimo['num_parcelas'],
                '',
                '',
                '',
                '',
            ];

            $data[] = [
                'Lucro por Parcela',
                'R$ ' . number_format($emprestimo['lucro_por_parcela'], 2, ',', '.'),
                '',
                '',
                '',
                '',
            ];

            $data[] = [
                '',
                '',
                '',
                '',
                '',
                '',
            ];

            // Cabeçalho das parcelas
            $data[] = [
                'Data Recebimento',
                'Valor Recebido',
                'Lucro Proporcional',
                '',
                '',
                '',
            ];

            // Parcelas recebidas
            foreach ($emprestimo['parcelas_recebidas_periodo'] as $parcela) {
                $data[] = [
                    \Carbon\Carbon::parse($parcela['data_recebimento'])->format('d/m/Y'),
                    'R$ ' . number_format($parcela['valor_recebido'], 2, ',', '.'),
                    'R$ ' . number_format($parcela['lucro_proporcional'], 2, ',', '.'),
                    '',
                    '',
                    '',
                ];
            }

            $data[] = [
                'TOTAL LUCRO NO PERÍODO',
                '',
                'R$ ' . number_format($emprestimo['total_lucro_periodo'], 2, ',', '.'),
                '',
                '',
                '',
            ];

            // Linha em branco entre empréstimos
            $data[] = [
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        return [
            'DETALHAMENTO POR EMPRÉSTIMO',
            '',
            '',
            '',
            '',
            '',
        ];
    }

    public function title(): string
    {
        return 'Detalhamento por Empréstimo';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 30,
            'C' => 20,
            'D' => 15,
            'E' => 15,
            'F' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}

