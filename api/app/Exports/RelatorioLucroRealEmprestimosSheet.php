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

class RelatorioLucroRealEmprestimosSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $emprestimos;

    public function __construct(array $emprestimos)
    {
        $this->emprestimos = $emprestimos;
    }

    public function collection()
    {
        $data = [];

        foreach ($this->emprestimos as $emprestimo) {
            // Linha de cabeçalho do empréstimo
            $data[] = [
                'Empréstimo ID: ' . $emprestimo['emprestimo_id'],
                'Cliente: ' . $emprestimo['cliente'],
                'CPF: ' . $emprestimo['cpf_cliente'],
                '',
                'Valor Emprestado: R$ ' . number_format($emprestimo['valor_emprestado'], 2, ',', '.'),
                'Lucro Total: R$ ' . number_format($emprestimo['lucro_total_emprestimo'], 2, ',', '.'),
                '',
            ];

            // Parcelas recebidas
            foreach ($emprestimo['parcelas_recebidas_periodo'] as $parcela) {
                $data[] = [
                    '',
                    'Parcela ' . $parcela['parcela_numero'],
                    Carbon::parse($parcela['data_recebimento'])->format('d/m/Y'),
                    'R$ ' . number_format($parcela['valor_recebido'], 2, ',', '.'),
                    'R$ ' . number_format($parcela['lucro_real'], 2, ',', '.'),
                    $parcela['descricao'],
                    $parcela['banco'],
                ];
            }

            // Totais do empréstimo
            $data[] = [
                '',
                'TOTAL',
                '',
                'R$ ' . number_format($emprestimo['total_valor_recebido'], 2, ',', '.'),
                'R$ ' . number_format($emprestimo['total_lucro_real_periodo'], 2, ',', '.'),
                '',
                '',
            ];

            // Linha em branco entre empréstimos
            $data[] = ['', '', '', '', '', '', ''];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Empréstimo',
            'Cliente/Parcela',
            'Data',
            'Valor Recebido',
            'Lucro Real',
            'Descrição',
            'Banco',
        ];
    }

    public function title(): string
    {
        return 'Detalhamento por Empréstimo';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 30,
            'C' => 15,
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

