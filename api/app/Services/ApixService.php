<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Banco;
use App\Models\Client;

/**
 * Serviço de integração com a API APIX (apixpag.com).
 * Autenticação: POST /api/auth/token com client_id e client_secret; usa o token Bearer nas requisições.
 * Documentação: https://app.apixpag.com/docs/deposits
 */
class ApixService
{
    protected $baseUrl = 'https://api.apixpag.com';
    protected $banco;
    protected $clientId;
    protected $clientSecret;
    protected $token = null;
    protected $tokenExpiresAt = null;
    protected $lastResponse = null;

    public function __construct(?Banco $banco = null)
    {
        $this->banco = $banco;

        if ($banco && ($banco->bank_type ?? '') === 'apix') {
            $this->baseUrl = rtrim($banco->apix_base_url ?? $this->baseUrl, '/');
            $this->clientId = $banco->apix_client_id ?? null;
            $this->clientSecret = $banco->apix_client_secret ?? null;
            if ($this->clientSecret) {
                try {
                    $this->clientSecret = Crypt::decryptString($banco->apix_client_secret);
                } catch (\Exception $e) {
                    $this->clientSecret = $banco->apix_client_secret;
                }
            }
            if (empty($this->clientId) || empty($this->clientSecret)) {
                throw new \Exception('Client ID e Client Secret APIX não configurados para este banco.');
            }
            $this->authenticate();
        } else {
            throw new \Exception('Banco não é do tipo APIX ou credenciais não configuradas.');
        }
    }

    /**
     * Obtém token via POST /api/auth/token com client_id e client_secret.
     * Cache do token para evitar requisição a cada chamada.
     */
    protected function authenticate(): string
    {
        $cacheKey = 'apix_token_' . md5($this->clientId);
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $this->token = $cached['token'];
            $this->tokenExpiresAt = $cached['expires_at'] ?? null;
            if ($this->tokenExpiresAt && $this->tokenExpiresAt > (time() + 300)) {
                return $this->token;
            }
        }

        $url = $this->baseUrl . '/api/auth/token';
        $response = Http::asJson()->post($url, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = $err['message'] ?? $err['error'] ?? 'Erro ao autenticar na API APIX';
            throw new \Exception('Erro ao autenticar APIX: ' . $msg);
        }

        $data = $response->json();
        $this->token = $data['token'] ?? $data['access_token'] ?? null;
        if (empty($this->token)) {
            throw new \Exception('Resposta da APIX não contém token.');
        }
        $expiresIn = $data['expires_in'] ?? 3600;
        $this->tokenExpiresAt = time() + (int) $expiresIn;
        Cache::put($cacheKey, [
            'token' => $this->token,
            'expires_at' => $this->tokenExpiresAt,
        ], now()->addSeconds($expiresIn - 60));

        return $this->token;
    }

    protected function ensureAuthenticated(): string
    {
        if (!$this->token || ($this->tokenExpiresAt && $this->tokenExpiresAt <= time() + 300)) {
            return $this->authenticate();
        }
        return $this->token;
    }

    /**
     * Monta o comando CURL equivalente à requisição (para exibir na tela de teste).
     * Formato: curl -X GET URL \n -H "Header: value"
     */
    protected function buildCurlString(string $method, string $url, array $headers, array $data): string
    {
        $method = strtoupper($method);
        $urlPart = $url;
        if ($method === 'GET' && !empty($data)) {
            $sep = strpos($url, '?') !== false ? '&' : '?';
            $urlPart = $url . $sep . http_build_query($data);
        }
        $parts = ["curl -X {$method} " . $urlPart];
        foreach ($headers as $key => $value) {
            $parts[] = "  -H \"" . $key . ": " . str_replace('"', '\\"', $value) . "\"";
        }
        if ($method !== 'GET' && !empty($data)) {
            $parts[] = "  -d '" . addcslashes(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "'\\") . "'";
        }
        return implode(" \\\n", $parts);
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): \Illuminate\Http\Client\Response
    {
        $token = $this->ensureAuthenticated();
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];

        $curlString = $this->buildCurlString($method, $url, $headers, $data);
        Log::channel('apix')->info('APIX REQUEST', [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'curl' => $curlString,
        ]);

        $http = Http::withHeaders($headers);
        $response = strtoupper($method) === 'GET'
            ? $http->get($url, $data)
            : $http->post($url, $data);

        $this->lastResponse = [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
            'curl' => $curlString,
        ];
        Log::channel('apix')->info('APIX RESPONSE', $this->lastResponse);

        return $response;
    }

    /**
     * Cria cobrança PIX (depósito).
     * Endpoint APIX: POST /api/payments/deposit
     * Payload: amount, external_id, website, clientCallbackUrl, payer (name, email, document, phone), products.
     */
    public function criarCobranca(
        float $valor,
        Client $cliente,
        string $referenceId,
        ?string $dueDate = null,
        ?string $website = null,
        ?string $clientCallbackUrl = null
    ): array {
        $document = preg_replace('/\D/', '', $cliente->cpf ?? '');
        $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1 ?? $cliente->telefone_celular_2 ?? '');

        // website é obrigatório na API APIX
        $payload = [
            'amount' => $valor,
            'external_id' => $referenceId,
            'website' => !empty($website) ? $website : (config('app.url') ?: 'https://sistema.emprestimos'),
            'payer' => [
                'name' => $cliente->nome_completo ?? '',
                'document' => $document,
            ],
            'products' => [
                ['id' => 'prod_' . preg_replace('/[^a-zA-Z0-9]/', '_', substr($referenceId, 0, 32)), 'amount' => $valor],
            ],
        ];
        if (!empty($cliente->email)) {
            $payload['payer']['email'] = $cliente->email;
        }
        if (!empty($phone)) {
            $payload['payer']['phone'] = $phone;
        }
        if (!empty($clientCallbackUrl)) {
            $payload['clientCallbackUrl'] = $clientCallbackUrl;
        }

        $response = $this->makeRequest('POST', '/api/payments/deposit', $payload);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = $err['message'] ?? $err['error'] ?? 'Erro ao criar cobrança APIX';
            return [
                'success' => false,
                'error' => $msg,
                'last_response' => $this->lastResponse,
            ];
        }

        $data = $response->json();
        $body = $data['data'] ?? $data;
        return [
            'success' => true,
            'transaction_id' => $body['id'] ?? $referenceId,
            'code' => $body['pix_copy_paste'] ?? $body['qr_code'] ?? $body['pix_code'] ?? null,
            'pixCopiaECola' => $body['pix_copy_paste'] ?? $body['qr_code'] ?? $body['pix_code'] ?? null,
            'qr_code' => $body['pix_copy_paste'] ?? $body['qr_code'] ?? $body['pix_code'] ?? null,
            'status' => $body['status'] ?? 'PENDING',
            'response' => $data,
        ];
    }

    /**
     * Consulta saldo.
     * Endpoint APIX: GET /api/user/balance com Authorization Bearer.
     */
    public function consultarSaldo(): array
    {
        $response = $this->makeRequest('GET', '/api/user/balance', []);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = $err['message'] ?? $err['error'] ?? 'Erro ao consultar saldo APIX';
            return [
                'success' => false,
                'error' => $msg,
                'last_response' => $this->lastResponse,
            ];
        }

        $data = $response->json();
        $body = $data['data'] ?? $data;
        $saldo = $body['balance'] ?? $body['totalAmount'] ?? $body['amount'] ?? 0;

        return [
            'success' => true,
            'balance' => (float) $saldo,
            'response' => $data,
            'last_response' => $this->lastResponse,
        ];
    }

    /**
     * Realiza saque (withdraw) PIX.
     * Endpoint APIX: POST /api/withdrawals/withdraw
     * Payload: amount, external_id, pix_key, key_type, key_document, clientCallbackUrl.
     * key_type: email, cpf, cnpj, phone, evp
     */
    public function realizarSaque(
        float $valor,
        string $pixKey,
        string $keyType,
        string $keyDocument,
        string $externalId,
        ?string $clientCallbackUrl = null
    ): array {
        $payload = [
            'amount' => $valor,
            'external_id' => $externalId,
            'pix_key' => $pixKey,
            'key_type' => $keyType,
            'key_document' => preg_replace('/\D/', '', $keyDocument),
        ];
        if (!empty($clientCallbackUrl)) {
            $payload['clientCallbackUrl'] = $clientCallbackUrl;
        }

        $response = $this->makeRequest('POST', '/api/withdrawals/withdraw', $payload);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = $err['message'] ?? $err['error'] ?? 'Erro ao realizar saque APIX';
            return [
                'success' => false,
                'error' => $msg,
                'last_response' => $this->lastResponse,
            ];
        }

        $data = $response->json();
        $body = $data['data'] ?? $data;
        return [
            'success' => true,
            'transaction_id' => $body['id'] ?? $externalId,
            'response' => $data,
            'last_response' => $this->lastResponse,
        ];
    }

    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }
}
