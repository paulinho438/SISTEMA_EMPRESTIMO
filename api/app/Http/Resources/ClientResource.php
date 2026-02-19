<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;
use Carbon\Carbon;

use DateTime;

class ClientResource extends JsonResource
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
            "id"                    => $this->id,
            "tipo_pessoa"           => $this->tipo_pessoa ?? 'PF',
            "nome_completo"         => $this->nome_completo,
            "razao_social"          => $this->razao_social,
            "nome_fantasia"         => $this->nome_fantasia,
            "cpf"                   => $this->cpf,
            "rg"                    => $this->rg,
            "orgao_emissor_rg"      => $this->orgao_emissor_rg,
            "cnpj"                  => $this->cnpj,
            "estado_civil"          => $this->estado_civil,
            "regime_bens"           => $this->regime_bens,
            "renda_mensal"          => $this->renda_mensal,
            "data_nascimento"       => Carbon::parse($this->data_nascimento)->format('d/m/Y'),
            "sexo"                  => $this->sexo,
            "telefone_celular_1"    => $this->telefone_celular_1,
            "telefone_celular_2"    => $this->telefone_celular_2,
            "email"                 => $this->email,
            "status"                => $this->status,
            "status_motivo"         => $this->status_motivo,
            "observation"           => $this->observation,
            "nome_usuario_criacao"  => $this->nome_usuario_criacao,
            "limit"                 => $this->limit,
            "pix_cliente"           => $this->pix_cliente,
            "created_at"            => $this->created_at->format('d/m/Y H:i:s'),
            "address"               => AddressResource::collection($this->address),
            "nome_completo_cpf"     => "{$this->nome_completo} - {$this->cpf}",
            "label_completo"        => ($this->tipo_pessoa ?? 'PF') === 'PJ'
                ? (($this->razao_social ?: $this->nome_fantasia ?: $this->nome_completo) . ($this->cnpj ? " - {$this->cnpj}" : ''))
                : "{$this->nome_completo} - {$this->cpf}",

        ];
    }
}
