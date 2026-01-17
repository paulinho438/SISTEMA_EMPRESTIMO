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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RelatorioLucroRealResumoSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $relatorio;

    public function __construct(array $relatorio)
    {
        $this->relatorio = $relatorio;
    }

    public function collection()
    {
        $resumo = $this->relatorio['resumo'];
        
        $data = [
            ['RELATÓRIO DE LUCRO REAL', '', '', ''],
            ['', '', '', ''],
            ['Período Início', $this->relatorio['periodo']['inicio'], '', ''],
            ['Período Fim', $this->relatorio['periodo']['fim'], '', ''],
            ['', '', '', ''],
            ['RESUMO FINANCEIRO', '', '', ''],
            ['', '', '', ''],
            ['Receita Bruta Total (Parcelas + Outras Receitas)', 'R$ ' . number_format($resumo['receita_bruta_total'], 2, ',', '.'), '', ''],
            ['Valor Recebido em Parcelas', 'R$ ' . number_format($resumo['valor_recebido_total'], 2, ',', '.'), '', ''],
            ['Outras Receitas', 'R$ ' . number_format($resumo['outras_receitas'], 2, ',', '.'), '', ''],
            ['', '', '', ''],
            ['LUCRO REAL', '', '', ''],
            ['', '', '', ''],
            ['Lucro Real Total', 'R$ ' . number_format($resumo['lucro_real_total'], 2, ',', '.'), '', ''],
            ['', '', '', ''],
            ['ESTATÍSTICAS', '', '', ''],
            ['', '', '', ''],
            ['Total de Parcelas Processadas', $resumo['total_parcelas_processadas'], '', ''],
            ['Total de Empréstimos', $resumo['total_emprestimos'], '', ''],
        ];

        return collect($data);
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Resumo';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 30,
            'C' => 15,
            'D' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
            ],
            6 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
            ],
            12 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
            ],
            16 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
            ],
        ];
    }
}

