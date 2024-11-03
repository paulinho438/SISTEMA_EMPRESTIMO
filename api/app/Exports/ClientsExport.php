<?php
namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Dados mockados para exemplo
        return collect([
            ['João Silva', '01/01/2023'],
            ['Maria Oliveira', '05/01/2023'],
            ['Carlos Souza', '10/01/2023'],
            ['Ana Pereira', '15/01/2023'],
            ['Paulo Lima', '20/01/2023'],
            ['Fernanda Costa', '25/01/2023'],
            ['Ricardo Santos', '30/01/2023'],
            ['Juliana Almeida', '01/02/2023'],
            ['Roberto Fernandes', '05/02/2023'],
            ['Patrícia Gomes', '10/02/2023'],
            ['Lucas Martins', '15/02/2023'],
            ['Gabriela Rocha', '20/02/2023'],
            ['Marcos Dias', '25/02/2023'],
            ['Renata Barbosa', '01/03/2023'],
            ['Felipe Araújo', '05/03/2023'],
            ['Camila Ribeiro', '10/03/2023'],
            ['André Mendes', '15/03/2023'],
            ['Larissa Teixeira', '20/03/2023'],
            ['Thiago Nunes', '25/03/2023'],
            ['Vanessa Carvalho', '30/03/2023'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Nome do Cliente',
            'Data de Início',
        ];
    }
}
