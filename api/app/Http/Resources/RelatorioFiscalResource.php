<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MovimentacaofinanceiraResource;
use App\Http\Resources\ContaspagarResource;

class RelatorioFiscalResource extends JsonResource
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
            'configuracao' => $this->resource['configuracao'],
            'receita_bruta' => (float) $this->resource['receita_bruta'],
            'despesas_dedutiveis' => (float) $this->resource['despesas_dedutiveis'],
            'lucro_presumido' => (float) $this->resource['lucro_presumido'],
            'base_tributavel' => (float) $this->resource['base_tributavel'],
            'irpj' => $this->resource['irpj'],
            'csll' => (float) $this->resource['csll'],
            'total_impostos' => (float) $this->resource['total_impostos'],
            'movimentacoes' => MovimentacaofinanceiraResource::collection($this->resource['movimentacoes']),
            'despesas' => ContaspagarResource::collection($this->resource['despesas']),
        ];
    }
}

