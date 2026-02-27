<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;
use App\Models\Parcela;
use App\Services\ApixService;
use App\Services\BcodexService;
use App\Services\XGateService;
use App\Models\CustomLog;
use Illuminate\Support\Facades\Log;

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
            "wallet" => ($this->wallet) ? true : false,
            "bank_type" => ($this->wallet ? 'bcodex' : null) ?? $this->bank_type ?? 'normal',
            "juros" => $this->juros,
            "document" => $this->document,
            "accountId" => $this->accountId,
            "client_id" => $this->client_id,
            "certificate_path" => $this->certificate_path,
            "private_key_path" => $this->private_key_path,
            "velana_secret_key_configured" => !empty($this->velana_secret_key),
            "velana_public_key" => $this->velana_public_key,
            "chavepix" => $this->chavepix,
            "info_recebedor_pix" => $this->info_recebedor_pix,
            "created_at" => $this->created_at->format('d/m/Y H:i:s'),
            "name_agencia_conta" => "{$this->name} - Agência {$this->agencia} Cc {$this->conta}",
        ];
    }

    private function getSaldoBanco()
    {
        $bankType = $this->bank_type ?? ($this->wallet ? 'bcodex' : 'normal');

        if ($bankType === 'xgate') {
            try {
                $xgateService = new XGateService($this->resource);
                $result = $xgateService->consultarSaldo();
                if (!empty($result['success']) && isset($result['response'])) {
                    return $this->extrairSaldoXGate($result['response']);
                }
            } catch (\Throwable $e) {
                Log::channel('xgate')->warning('BancosComSaldoResource: erro ao consultar saldo XGate - ' . $e->getMessage());
            }
            return null;
        }

        if ($bankType === 'apix') {
            try {
                $apixService = new ApixService($this->resource);
                $result = $apixService->consultarSaldo();
                if (!empty($result['success'])) {
                    if (isset($result['balance']) && is_numeric($result['balance'])) {
                        return (float) $result['balance'];
                    }
                    if (isset($result['response']['balance']) && is_numeric($result['response']['balance'])) {
                        return (float) $result['response']['balance'];
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('apix')->warning('BancosComSaldoResource: erro ao consultar saldo APIX - ' . $e->getMessage());
            }
            return null;
        }

        if ($this->wallet) {
            $bcodexService = new BcodexService();
            $response = $bcodexService->consultarSaldo($this->accountId);

            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                return ($response->json()['balance'] / 100);
            }
        }

        return null;
    }

    /**
     * Extrai valor numérico do saldo da resposta da API XGate (balance/company).
     */
    private function extrairSaldoXGate($data): ?float
    {
        if (is_array($data) && isset($data['balance']) && is_numeric($data['balance'])) {
            return (float) $data['balance'];
        }
        if (is_array($data) && isset($data['amount']) && is_numeric($data['amount'])) {
            return (float) $data['amount'];
        }
        if (is_array($data) && isset($data['totalAmount']) && is_numeric($data['totalAmount'])) {
            return (float) $data['totalAmount'];
        }
        if (is_array($data)) {
            foreach ($data as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (isset($item['balance']) && is_numeric($item['balance'])) {
                    return (float) $item['balance'];
                }
                if (isset($item['amount']) && is_numeric($item['amount'])) {
                    return (float) $item['amount'];
                }
                // API XGate balance/company retorna array de objetos com totalAmount (ex.: [{"currency":{...},"totalAmount":7.5}])
                if (isset($item['totalAmount']) && is_numeric($item['totalAmount'])) {
                    return (float) $item['totalAmount'];
                }
            }
        }
        return null;
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
