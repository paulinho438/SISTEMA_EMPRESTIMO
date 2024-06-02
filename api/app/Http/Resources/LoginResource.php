<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

class LoginResource extends JsonResource
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
            "id"                => $this->id,
            "login"             => $this->login,
            "nome_completo"     => $this->nome_completo,
            "cpf"               => $this->cpf,
            "rg"                => $this->rg,
            "data_nascimento"   => $this->data_nascimento,
            "sexo"              => $this->sexo,
            "telefone_celular"  => $this->telefone_celular,
            "email"             => $this->email,
            "status"            => $this->status,
            "status_motivo"     => $this->status_motivo,
            "tentativas"        => $this->tentativas,
            "companies"         => CompaniesResource::collection($this->companies),
            "permissions"       => PermissionsResource::collection($this->groups),
            // "permissions"       => $this->groups

        ];
    }
}

    //     "id" => 1,
	// 	"nome_completo" => "PAULO PEIXOTO",
	// 	"cpf" => "00000000000",
	// 	"rg" => "2834868",
	// 	"data_nascimento" => "2023-11-29",
	// 	"sexo" => "M",
	// 	"telefone_celular" => "(61) 9 9330-5267",
	// 	"email" => "admin@gmail.com",
	// 	"status" => "A",
	// 	"status_motivo" => "",
	// 	"tentativas" => 0,
	// 	"permgroup_id" => 1,
	// 	"created_at" => "2023-11-29T01 =>48 =>19.000000Z",
	// 	"updated_at" => "2023-11-29T01 =>48 =>19.000000Z",
	// 	"deleted_at" => null,
	// 	"companies" => [
	// 		{
	// 			"id" => 1,
	// 			"company" => "BSB EMPRESTIMOS",
	// 			"created_at" => null,
	// 			"updated_at" => null,
	// 			"pivot" => {
	// 				"user_id" => 1,
	// 				"company_id" => 1
	// 			}
	// 		},
	// 		{
	// 			"id" => 2,
	// 			"company" => "RJ EMPRESTIMOS",
	// 			"created_at" => null,
	// 			"updated_at" => null,
	// 			"pivot" => {
	// 				"user_id" => 1,
	// 				"company_id" => 2
	// 			}
	// 		}
	// 	]
	// },
