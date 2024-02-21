<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

class BancosResource extends JsonResource
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
            "name"                  => $this->name,
            "agencia"               => $this->agencia,
            "conta"                 => $this->conta,
            "saldo"                 => $this->saldo,
            "efibank"               => ($this->efibank) ? true : false,
            "created_at"            => $this->created_at->format('d/m/Y H:i:s'),
            "name_agencia_conta"    => "{$this->name} - Agência {$this->agencia} Cc {$this->conta}",
        ];
    }
}
