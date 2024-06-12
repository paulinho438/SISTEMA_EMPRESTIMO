<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

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
            "saldo_banco" => $this->getSaldoBanco(),
            "efibank" => ($this->efibank) ? true : false,
            "clienteid" => $this->clienteid,
            "clientesecret" => $this->clientesecret,
            "juros" => $this->juros,
            "certificado" => $this->certificado,
            "chavepix" => $this->chavepix,
            "created_at" => $this->created_at->format('d/m/Y H:i:s'),
            "name_agencia_conta" => "{$this->name} - Agência {$this->agencia} Cc {$this->conta}",
        ];
    }

    private function getSaldoBanco()
    {

        try {

            if($this->efibank){
                $api = new EfiPay([
                    'client_id' => $this->clienteid,
                    'client_secret' => $this->clientesecret,
                    'certificate' => storage_path('app/public/documentos/' . $this->certificado),
                    'sandbox' => false,
                    'timeout' => 300,
                ]);

                $response = $api->getAccountBalance([ "bloqueios" => false ]);

                return $response['saldo'];
            } else {
                return null;
            }


        } catch (EfiException $e) {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'Error ao buscar saldo' . $e->code . ' ' . $e->error . ' ' . $e->errorDescription,
                'operation' => 'error'
            ]);
        } catch (Exception $e) {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => $e->getMessage(),
                'operation' => 'error'
            ]);
        }

    }
}
