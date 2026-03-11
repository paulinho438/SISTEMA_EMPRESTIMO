<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientesDisponiveisExport implements FromCollection, WithHeadings
{
    protected Collection $clients;

    public function __construct(Collection $clients)
    {
        $this->clients = $clients;
    }

    public function collection()
    {
        return $this->clients->map(function ($client) {
            $emprestimo = optional($client->emprestimos);
            $lateParcels = (int) ($emprestimo->count_late_parcels ?? 0);

            $classificacao = 'Pessimo pagador';
            if ($lateParcels <= 2) {
                $classificacao = 'Bom pagador';
            } elseif ($lateParcels <= 5) {
                $classificacao = 'Pagador mediano';
            } elseif ($lateParcels <= 8) {
                $classificacao = 'Pagador ruim';
            }

            return [
                $client->nome_completo,
                $client->cpf,
                $client->rg,
                $client->cnpj,
                $client->telefone_celular_1,
                $client->telefone_celular_2,
                optional($client->data_nascimento)->format('d/m/Y'),
                optional($client->created_at)->format('d/m/Y H:i:s'),
                optional($emprestimo->data_quitacao)->format('d/m/Y'),
                $lateParcels,
                $classificacao
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'CPF',
            'RG',
            'CNPJ',
            'Telefone Principal',
            'Telefone Secundario',
            'Data Nascimento',
            'Data Criacao',
            'Data Quitacao',
            'Parcelas em Atraso',
            'Classificacao'
        ];
    }
}
