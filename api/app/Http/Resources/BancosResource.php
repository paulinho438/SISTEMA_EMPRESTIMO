<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

use App\Models\CustomLog;

use Efi\Exception\EfiException;
use Efi\EfiPay;

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
            "id" => $this->id,
            "name" => $this->name,
            "agencia" => $this->agencia,
            "conta" => $this->conta,
            "saldo" => $this->saldo,
            "caixa_empresa" => $this->company->caixa,
            "caixa_pix" => $this->company->caixa_pix,
            "wallet" => ($this->wallet) ? true : false,
            "bank_type" => ($this->wallet ? 'bcodex' : null) ?? $this->bank_type ?? 'normal',
            "clienteid" => $this->clienteid,
            "clientesecret" => $this->clientesecret,
            "juros" => $this->juros,
            "certificado" => $this->certificado,
            "chavepix" => $this->chavepix,
            "info_recebedor_pix" => $this->info_recebedor_pix,
            "accountId" => $this->accountId,
            "client_id" => $this->client_id,
            "certificate_path" => $this->certificate_path,
            "private_key_path" => $this->private_key_path,
            "created_at" => $this->created_at->format('d/m/Y H:i:s'),
            "name_agencia_conta" => "{$this->name} - Agência {$this->agencia} Cc {$this->conta}",
            "apix_base_url" => $this->apix_base_url ?? null,
            "apix_client_id" => $this->apix_client_id ?? null,
        ];
    }

}
