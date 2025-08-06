<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\RequestException;

class MTPAYService
{
    protected string $baseUrl = 'https://app.mtpay.co/api/v1/gateway/pix/receive';

    protected string $publicKey;
    protected string $secretKey;

    public function __construct()
    {
        // Idealmente, use .env
        $this->publicKey = config('services.mtpay.public_key');
        $this->secretKey = config('services.mtpay.secret_key');
    }

    public function criarCobranca(Wallet $wallet, array $cliente, float $valorBruto, float $taxaCliente = 0.20): Charge
    {
        $identifier    = Str::random(12);
        $webhookToken  = Str::random(20);
        $taxaGateway   = 0.10; // Valor fixo
        $valorLiquido  = $valorBruto - $taxaCliente - $taxaGateway; // Valor cheio para o cliente
        $dueDate       = now()->addDays(2)->toDateString();
        $callbackUrl   = url("/api/pix/callback/{$webhookToken}");

        try {
            $response = Http::withHeaders([
                'Content-Type'   => 'application/json',
                'Accept'         => '*/*',
                'User-Agent'     => 'Mozilla/5.0 (Laravel Client)',
                'x-public-key'   => $this->publicKey,
                'x-secret-key'   => $this->secretKey,
            ])->post($this->baseUrl, [
                'identifier'  => $identifier,
                'amount'      => $valorBruto,
                'client'      => [
                    'name'     => $cliente['name'],
                    'email'    => $cliente['email'],
                    'phone'    => $cliente['phone'],
                    'document' => $cliente['document'],
                ],
                'dueDate'     => $dueDate,
                'callbackUrl' => $callbackUrl,
            ]);

            if ($response->failed()) {
                throw new \Exception('Erro na requisição Pix: ' . $response->body());
            }

            $data = $response->json();

            return Charge::create([
                'wallet_id'               => $wallet->id,
                'gateway_transaction_id'  => $data['transactionId'] ?? null,
                'external_transaction_id' => $data['acquirerExternalIds']['transaction'] ?? null,
                'order_id'                => $data['order']['id'] ?? null,
                'status'                  => $data['status'] ?? 'pending',
                'valor_servico'           => $taxaGateway,
                'taxa_cliente'            => $taxaCliente,
                'valor_bruto'             => $valorBruto,
                'amount'                  => $valorBruto,
                'taxa_gateway'            => $taxaGateway,
                'valor_liquido'           => $valorLiquido, // será calculado no webhook
                'pix_code'                => $data['pix']['code'] ?? null,
                'pix_base64'              => $data['pix']['base64'] ?? null,
                'webhook_token'           => $webhookToken,
            ]);
        } catch (\Throwable $e) {
            logger()->error('Erro ao criar cobrança Pix', ['exception' => $e]);
            throw new \Exception("Erro ao criar cobrança Pix: " . $e->getMessage());
        }
    }
}
