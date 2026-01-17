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
        $data = [
            'tipo_calculo' => $this->resource['tipo_calculo'] ?? 'presumido',
            'periodo' => $this->resource['periodo'],
            'configuracao' => $this->resource['configuracao'],
            'receita_bruta' => (float) $this->resource['receita_bruta'],
            'despesas_dedutiveis' => (float) $this->resource['despesas_dedutiveis'],
            'lucro_presumido' => (float) ($this->resource['lucro_presumido'] ?? 0),
            'base_tributavel' => (float) $this->resource['base_tributavel'],
            'irpj' => $this->resource['irpj'],
            'csll' => (float) $this->resource['csll'],
            'total_impostos' => (float) $this->resource['total_impostos'],
            'movimentacoes' => MovimentacaofinanceiraResource::collection($this->resource['movimentacoes']),
            'despesas' => ContaspagarResource::collection($this->resource['despesas']),
        ];

        // Adicionar campos específicos do cálculo proporcional
        if (($this->resource['tipo_calculo'] ?? 'presumido') === 'proporcional') {
            $data['lucro_proporcional_total'] = (float) ($this->resource['lucro_proporcional_total'] ?? 0);
            $data['detalhamento_emprestimos'] = $this->resource['detalhamento_emprestimos'] ?? [];
        } else {
            $data['lucro_proporcional_total'] = 0;
            $data['detalhamento_emprestimos'] = [];
        }

        return $data;
    }
}

