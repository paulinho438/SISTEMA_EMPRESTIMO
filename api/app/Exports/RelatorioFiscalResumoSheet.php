<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioFiscalResumoSheet implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $relatorio;

    public function __construct(array $relatorio)
    {
        $this->relatorio = $relatorio;
    }

    public function collection()
    {
        $data = [
            [
                'Período Início',
                $this->relatorio['periodo']['inicio'],
                '',
                '',
            ],
            [
                'Período Fim',
                $this->relatorio['periodo']['fim'],
                '',
                '',
            ],
            [
                '',
                '',
                '',
                '',
            ],
            [
                'CONFIGURAÇÃO FISCAL',
                '',
                '',
                '',
            ],
            [
                'Percentual de Presunção (%)',
                number_format($this->relatorio['configuracao']['percentual_presuncao'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'Alíquota IRPJ (%)',
                number_format($this->relatorio['configuracao']['aliquota_irpj'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'Alíquota IRPJ Adicional (%)',
                number_format($this->relatorio['configuracao']['aliquota_irpj_adicional'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'Alíquota CSLL (%)',
                number_format($this->relatorio['configuracao']['aliquota_csll'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'Faixa de Isenção IRPJ',
                'R$ ' . number_format($this->relatorio['configuracao']['faixa_isencao_irpj'], 2, ',', '.'),
                '',
                '',
            ],
            [
                '',
                '',
                '',
                '',
            ],
            [
                'RESUMO FINANCEIRO',
                '',
                '',
                '',
            ],
            [
                'Receita Bruta',
                'R$ ' . number_format($this->relatorio['receita_bruta'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'Despesas Dedutíveis',
                'R$ ' . number_format($this->relatorio['despesas_dedutiveis'], 2, ',', '.'),
                '',
                '',
            ],
            [
                '',
                '',
                '',
                '',
            ],
            [
                'CÁLCULOS TRIBUTÁRIOS',
                '',
                '',
                '',
            ],
            [
                'Lucro Presumido',
                'R$ ' . number_format($this->relatorio['lucro_presumido'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'Base Tributável',
                'R$ ' . number_format($this->relatorio['base_tributavel'], 2, ',', '.'),
                '',
                '',
            ],
            [
                '',
                '',
                '',
                '',
            ],
            [
                'IMPOSTOS',
                '',
                '',
                '',
            ],
            [
                'IRPJ Normal (15%)',
                'R$ ' . number_format($this->relatorio['irpj']['normal'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'IRPJ Adicional (10%)',
                'R$ ' . number_format($this->relatorio['irpj']['adicional'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'IRPJ Total',
                'R$ ' . number_format($this->relatorio['irpj']['total'], 2, ',', '.'),
                '',
                '',
            ],
            [
                'CSLL (9%)',
                'R$ ' . number_format($this->relatorio['csll'], 2, ',', '.'),
                '',
                '',
            ],
            [
                '',
                '',
                '',
                '',
            ],
            [
                'TOTAL DE IMPOSTOS',
                'R$ ' . number_format($this->relatorio['total_impostos'], 2, ',', '.'),
                '',
                '',
            ],
        ];

        return new Collection($data);
    }

    public function headings(): array
    {
        return [
            'Descrição',
            'Valor',
            '',
            '',
        ];
    }

    public function title(): string
    {
        return 'Resumo';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 25,
            'C' => 10,
            'D' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true, 'size' => 12]],
            11 => ['font' => ['bold' => true, 'size' => 12]],
            15 => ['font' => ['bold' => true, 'size' => 12]],
            19 => ['font' => ['bold' => true, 'size' => 12]],
            25 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}

