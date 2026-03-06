<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RelatorioLucroRealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'periodo' => $this->resource['periodo'],
            'resumo' => $this->resource['resumo'],
            'detalhamento_emprestimos' => $this->resource['detalhamento_emprestimos'],
            'detalhamento_movimentacoes' => $this->resource['detalhamento_movimentacoes'],
            'detalhamento_outras_receitas' => $this->resource['detalhamento_outras_receitas'] ?? [],
            'detalhamento_receita_bruta' => $this->resource['detalhamento_receita_bruta'] ?? [],
        ];
    }
}
