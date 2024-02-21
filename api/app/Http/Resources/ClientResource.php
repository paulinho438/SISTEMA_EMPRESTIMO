<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

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
            "nome_completo"         => $this->nome_completo,
            "cpf"                   => $this->cpf,
            "rg"                    => $this->rg,
            "data_nascimento"       => (new DateTime($this->data_nascimento))->format('d/m/Y'),
            "sexo"                  => $this->sexo,
            "telefone_celular_1"    => $this->telefone_celular_1,
            "telefone_celular_2"    => $this->telefone_celular_2,
            "email"                 => $this->email,
            "status"                => $this->status,
            "status_motivo"         => $this->status_motivo,
            "observation"           => $this->observation,
            "limit"                 => $this->limit,
            "created_at"            => $this->created_at->format('d/m/Y H:i:s'),
            "address"               => AddressResource::collection($this->address),
            "nome_completo_cpf"     => "{$this->nome_completo} - {$this->cpf}",

        ];
    }
}
