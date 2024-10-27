<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;
use App\Models\Parcela;

use App\Models\CustomLog;

use Efi\Exception\EfiException;
use Efi\EfiPay;

class BancosComSaldoResource extends JsonResource
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
            "parcelas_baixa_manual" => $this->getParcelasBaixaManual(),
            "caixa_empresa" => $this->company->caixa,
            "caixa_pix" => $this->company->caixa_pix,
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

            if ($this->efibank) {
                $api = new EfiPay([
                    'clientId' => $this->clienteid,
                    'clientSecret' => $this->clientesecret,
                    'certificate' => storage_path('app/public/documentos/' . $this->certificado),
                    'sandbox' => false,
                    "debug" => false,
                    'timeout' => 60,
                ]);

                $response = $api->getAccountBalance(["bloqueios" => false]);

                if (isset($response['saldo'])) {
                    return $response['saldo'];
                }else{
                    return null;
                }

            } else {
                return null;
            }


        } catch (EfiException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }

    }

    private function getParcelasBaixaManual()
    {

        $id = $this->id;

        $parcelas = Parcela::whereHas('emprestimo', function ($query) use ($id) {
            $query->where('banco_id', $id)
                  ->whereNull('dt_baixa')
                  ->where('valor_recebido', '>', 0);
        })->get();

        return $parcelas;

    }
}
