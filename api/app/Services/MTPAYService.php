<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\Transfer;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MTPAYService
{
    /**
     * Endpoints MtPay
     */
    protected string $pixUrl       = 'https://app.mtpay.co/api/v1/gateway/pix/receive';
    protected string $transferUrl  = 'https://app.mtpay.co/api/v1/gateway/transfers';

    /**
     * Credenciais
     */
    protected string $publicKey;
    protected string $secretKey;

    public function __construct()
    {
        // Ideal: usar .env + config/services.php
        $this->publicKey = config('services.mtpay.public_key');
        $this->secretKey = config('services.mtpay.secret_key');
    }

    /**
     * Cria uma cobrança PIX na MtPay e salva a Charge no banco (valor cheio; taxas descontadas no fluxo de liquidação).
     */
    public function criarCobranca(Wallet $wallet, array $cliente, float $valorBruto, float $taxaCliente = 0.20): Charge
    {
        // validações básicas
        if ($valorBruto <= 0) {
            throw new \InvalidArgumentException('O valor da cobrança deve ser maior que zero.');
        }
        foreach (['name','email','phone','document'] as $k) {
            if (!isset($cliente[$k]) || $cliente[$k] === '') {
                throw new \InvalidArgumentException("Campo cliente['{$k}'] é obrigatório.");
            }
        }

        $identifier    = Str::random(12);
        $webhookToken  = Str::random(20);

        // Taxas fixas conforme seu modelo
        $taxaGateway   = 0.10; // para seu controle interno
        $valorLiquido  = $valorBruto - $taxaCliente - $taxaGateway;

        // A MtPay exige dueDate > hoje
        $dueDate       = now()->addDays(2)->toDateString();
        $callbackUrl   = url("/api/pix/callback/{$webhookToken}");

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'Laravel MTPay Client',
                'x-public-key' => $this->publicKey,
                'x-secret-key' => $this->secretKey,
            ])
                ->timeout(30)
                ->retry(2, 300)
                ->post($this->pixUrl, [
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

                // Controle interno
                'valor_servico'           => $taxaGateway,
                'taxa_cliente'            => $taxaCliente,
                'valor_bruto'             => $valorBruto,
                'amount'                  => $valorBruto,
                'taxa_gateway'            => $taxaGateway,
                'valor_liquido'           => $valorLiquido, // recalculado/ajustado no webhook quando pagar

                // PIX
                'pix_code'                => data_get($data, 'pix.code'),
                'pix_base64'              => data_get($data, 'pix.base64'),

                // Webhook token para validar seu callback
                'webhook_token'           => $webhookToken,
            ]);
        } catch (\Throwable $e) {
            logger()->error('Erro ao criar cobrança Pix', ['exception' => $e]);
            throw new \Exception("Erro ao criar cobrança Pix: " . $e->getMessage());
        }
    }

    public function criarTransferencia(
        Wallet $wallet,
        float $amount,
        array $pix,
        array $owner,
        bool $discountFeeOfReceiver = false,
        ?string $callbackUrl = null,
        ?string $clientIdentifier = null
    ): Transfer {
        // --- validações básicas
        if ($amount <= 5) {
            throw new \InvalidArgumentException('O valor da transferência deve ser maior que cinco reais.');
        }
        foreach (['type','key'] as $k) {
            if (!isset($pix[$k]) || empty($pix[$k])) {
                throw new \InvalidArgumentException("Campo pix['{$k}'] é obrigatório.");
            }
        }
        foreach (['ip','name','document'] as $k) {
            if (!isset($owner[$k]) || empty($owner[$k])) {
                throw new \InvalidArgumentException("Campo owner['{$k}'] é obrigatório.");
            }
        }
        foreach (['type','number'] as $k) {
            if (!isset($owner['document'][$k]) || empty($owner['document'][$k])) {
                throw new \InvalidArgumentException("Campo owner['document']['{$k}'] é obrigatório.");
            }
        }

        // defaults
        $clientIdentifier = $clientIdentifier ?: 'transfer_' . Str::random(12);
        $callbackUrl      = $callbackUrl ?: url('/api/mtpay/transfers/callback/' . Str::random(16));

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'Laravel MTPay Client',
                'x-public-key' => $this->publicKey,
                'x-secret-key' => $this->secretKey,
            ])
                ->timeout(30)
                ->retry(2, 300)
                ->post($this->transferUrl, [
                    'clientIdentifier'      => $clientIdentifier,
                    'callbackUrl'           => $callbackUrl,
                    'amount'                => $amount,
                    'discountFeeOfReceiver' => $discountFeeOfReceiver,
                    'pix' => [
                        'type' => $pix['type'], // email|cpf|cnpj|phone|evp
                        'key'  => $pix['key'],
                    ],
                    'owner' => [
                        'ip'   => $owner['ip'],
                        'name' => $owner['name'],
                        'document' => [
                            'type'   => $owner['document']['type'],   // cpf|cnpj
                            'number' => $owner['document']['number'],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                throw new \Exception('Erro na requisição de transferência: ' . $response->body());
            }

            $data = $response->json();

            // Campos retornados (seguindo o exemplo da doc)
            $webhookToken = $data['webhookToken'] ?? null;

            $withdraw              = $data['withdraw']       ?? [];
            $withdrawId            = $withdraw['id']         ?? null;
            $withdrawAmount        = $withdraw['amount']     ?? null;
            $withdrawFeeAmount     = $withdraw['feeAmount']  ?? null;
            $withdrawCurrency      = $withdraw['currency']   ?? null;
            $withdrawStatus        = $withdraw['status']     ?? null;
            $withdrawCreatedAt     = $withdraw['createdAt']  ?? null;
            $withdrawUpdatedAt     = $withdraw['updatedAt']  ?? null;

            $payout                = $data['payoutAccount']  ?? [];
            $payoutAccountId       = $payout['id']           ?? null;
            $payoutAccountStatus   = $payout['status']       ?? null;
            $payoutPix             = $payout['pix']          ?? null;
            $payoutPixType         = $payout['pixType']      ?? null;
            $payoutCreatedAt       = $payout['createdAt']    ?? null;
            $payoutUpdatedAt       = $payout['updatedAt']    ?? null;
            $payoutDeletedAt       = $payout['deletedAt']    ?? null;

            return DB::transaction(function () use (
                $wallet, $clientIdentifier, $webhookToken, $amount, $discountFeeOfReceiver,
                $pix, $owner, $callbackUrl,
                $withdrawId, $withdrawAmount, $withdrawFeeAmount, $withdrawCurrency,
                $withdrawStatus, $withdrawCreatedAt, $withdrawUpdatedAt,
                $payoutAccountId, $payoutAccountStatus, $payoutPix, $payoutPixType,
                $payoutCreatedAt, $payoutUpdatedAt, $payoutDeletedAt, $data
            ) {
                return Transfer::create([
                    'wallet_id'                 => $wallet->id,

                    'client_identifier'         => $clientIdentifier,
                    'webhook_token'             => $webhookToken,

                    'amount'                    => $amount,
                    'discount_fee_of_receiver'  => $discountFeeOfReceiver,

                    'pix_type'                  => $pix['type'],
                    'pix_key'                   => $pix['key'],

                    'owner_ip'                  => $owner['ip'],
                    'owner_name'                => $owner['name'],
                    'owner_document_type'       => $owner['document']['type'],
                    'owner_document_number'     => $owner['document']['number'],

                    'callback_url'              => $callbackUrl,

                    'withdraw_id'               => $withdrawId,
                    'withdraw_amount'           => $withdrawAmount,
                    'withdraw_fee_amount'       => $withdrawFeeAmount,
                    'withdraw_currency'         => $withdrawCurrency,
                    'withdraw_status'           => $withdrawStatus,
                    'withdraw_created_at'       => $withdrawCreatedAt ? Carbon::parse($withdrawCreatedAt) : null,
                    'withdraw_updated_at'       => $withdrawUpdatedAt ? Carbon::parse($withdrawUpdatedAt) : null,

                    'payout_account_id'         => $payoutAccountId,
                    'payout_account_status'     => $payoutAccountStatus,
                    'payout_pix'                => $payoutPix,
                    'payout_pix_type'           => $payoutPixType,
                    'payout_created_at'         => $payoutCreatedAt ? Carbon::parse($payoutCreatedAt) : null,
                    'payout_updated_at'         => $payoutUpdatedAt ? Carbon::parse($payoutUpdatedAt) : null,
                    'payout_deleted_at'         => $payoutDeletedAt ? Carbon::parse($payoutDeletedAt) : null,

                    'status'                    => $withdrawStatus, // espelha por conveniência
                    'raw_response'              => $data,
                ]);
            });
        } catch (\Throwable $e) {
            logger()->error('Erro ao criar transferência MtPay', ['exception' => $e]);
            throw new \Exception("Erro ao criar transferência: " . $e->getMessage());
        }
    }
}
