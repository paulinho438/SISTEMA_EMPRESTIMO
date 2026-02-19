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
            'pis' => (float) ($this->resource['pis'] ?? 0),
            'cofins' => (float) ($this->resource['cofins'] ?? 0),
            'iof_total_mes' => (float) ($this->resource['iof_total_mes'] ?? 0),
            'total_impostos' => (float) $this->resource['total_impostos'],
            'total_recebimentos' => (float) ($this->resource['total_recebimentos'] ?? $this->resource['receita_bruta']),
            'total_amortizacao' => (float) ($this->resource['total_amortizacao'] ?? 0),
            'total_juros' => (float) ($this->resource['total_juros'] ?? 0),
            'descontos_aplicados' => (float) ($this->resource['descontos_aplicados'] ?? 0),
            'titulos_atrasados' => (float) ($this->resource['titulos_atrasados'] ?? 0),
            'mes_trimestral' => (bool) ($this->resource['mes_trimestral'] ?? false),
            'vencimento_impostos' => $this->resource['vencimento_impostos'] ?? null,
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

