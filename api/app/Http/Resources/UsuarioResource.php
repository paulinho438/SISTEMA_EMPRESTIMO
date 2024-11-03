<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

use DateTime;

class UsuarioResource extends JsonResource
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
            "login"                 => $this->login,
            "nome_completo"         => $this->nome_completo,
            "rg"                    => $this->rg,
            "cpf"                   => $this->cpf,
            "data_nascimento"       => $this->data_nascimento,
            "sexo"                  => $this->sexo,
            "telefone_celular"      => $this->telefone_celular,
            "status"                => $this->status,
            "status_motivo"         => $this->status_motivo,
            "tentativas"            => $this->tentativas,
            "companies"             => $this->getCompaniesAsString(),
            "empresas"              => EmpresaResource::collection($this->companies),
            "permissao"             => $this->groups,

        ];
    }
}
