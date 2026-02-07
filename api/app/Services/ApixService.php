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
     * Payload: amount, external_id (aleatório), website, clientCallbackUrl, payer (name, document, email, phone), products (id, name, price, quantity).
     * Resposta: qrCodeResponse.transactionId (salvo como identificador), qrCodeResponse.qrcode (PIX copia e cola).
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

        $defaultWebsite = config('services.apix.website') ?: config('app.url') ?: 'https://api.agecontrole.com.br';
        $defaultCallback = config('services.apix.callback_url') ?: 'https://api.agecontrole.com.br/api/webhook/apix';

        // Usar referenceId (entidade_id_timestamp) para permitir matching no webhook
        $externalId = preg_match('/^\d+_\d+$/', $referenceId) ? $referenceId : ('apix_' . bin2hex(random_bytes(8)) . '_' . time());
        $dueDate = !empty($dueDate) ? $dueDate : date('Y-m-d', strtotime('+6 months'));

        $payload = [
            'amount' => $valor,
            'external_id' => $externalId,
            'website' => !empty($website) ? $website : $defaultWebsite,
            'clientCallbackUrl' => !empty($clientCallbackUrl) ? $clientCallbackUrl : $defaultCallback,
            'payer' => [
                'name' => $cliente->nome_completo ?? '',
                'document' => $document,
            ],
            'products' => [
                [
                    'id' => '1',
                    'name' => 'Produto',
                    'price' => $valor,
                    'quantity' => 1,
                ],
            ],
        ];
        if (!empty($cliente->email)) {
            $payload['payer']['email'] = $cliente->email;
        }
        if (!empty($phone)) {
            $payload['payer']['phone'] = $phone;
        }
        $payload['due_date'] = is_object($dueDate) && method_exists($dueDate, 'format') ? $dueDate->format('Y-m-d') : (string) $dueDate;

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
        $qrCodeResponse = $data['qrCodeResponse'] ?? [];
        $transactionId = $qrCodeResponse['transactionId'] ?? $data['transactionId'] ?? $referenceId;
        $qrcode = $qrCodeResponse['qrcode'] ?? $qrCodeResponse['qr_code'] ?? $data['qrcode'] ?? null;

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'txid' => $transactionId,
            'code' => $qrcode,
            'pixCopiaECola' => $qrcode,
            'qr_code' => $qrcode,
            'external_id' => $externalId,
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
     * Obtém key_type e key_document para saque PIX.
     * Para chaves EMAIL, PHONENUMBER ou RANDOM_KEY_CODE, a APIX exige CPF/CNPJ do destinatário.
     *
     * @param string $pixKey Chave PIX
     * @param string|null $cpfCnpjDestino CPF/CNPJ do destinatário (fornecedor ou cliente)
     * @return array ['ok' => bool, 'key_type' => string, 'key_document' => string] ou ['ok' => false, 'error' => string]
     */
    public function obterKeyTypeEDocumentParaSaque(string $pixKey, ?string $cpfCnpjDestino): array
    {
        $tipo = $this->determinarTipoChavePix($pixKey);
        $keyDocument = preg_replace('/\D/', '', $cpfCnpjDestino ?? '');

        if (in_array($tipo, ['EMAIL', 'PHONENUMBER', 'RANDOM_KEY_CODE'], true)) {
            if (empty($keyDocument)) {
                return [
                    'ok' => false,
                    'error' => 'Cadastre o CPF/CNPJ do destinatário para realizar transferências PIX para chaves do tipo email, telefone ou chave aleatória.'
                ];
            }
            return ['ok' => true, 'key_type' => $tipo, 'key_document' => $keyDocument];
        }

        if (!empty($keyDocument)) {
            return ['ok' => true, 'key_type' => $tipo, 'key_document' => $keyDocument];
        }
        $digitsFromKey = preg_replace('/\D/', '', $pixKey);
        if (strlen($digitsFromKey) === 11 || strlen($digitsFromKey) === 14) {
            return ['ok' => true, 'key_type' => $tipo, 'key_document' => $digitsFromKey];
        }
        return ['ok' => false, 'error' => 'Documento (CPF/CNPJ) do destinatário não identificado.'];
    }

    /**
     * Realiza saque (withdraw) PIX.
     * Endpoint APIX: POST /api/withdrawals/withdraw
     * Payload: amount, external_id, pix_key, key_type, key_document, clientCallbackUrl.
     * key_type: APIX aceita "cpf"|"cnpj"|"email"|"phonenumber"|"random_key_code" ou maiúsculas (PHONENUMBER para celular).
     * O tipo da chave é determinado pela mesma lógica da XGate (celular → PHONENUMBER).
     */
    /**
     * Mapeia nosso tipo de chave para o formato esperado pela API APIX.
     * APIX aceita: email, cpf, cnpj, phone, evp (chave aleatória).
     */
    protected function mapearKeyTypeParaApix(string $tipo): string
    {
        $map = [
            'EMAIL' => 'email',
            'CPF' => 'cpf',
            'CNPJ' => 'cnpj',
            'PHONENUMBER' => 'phone',
            'RANDOM_KEY_CODE' => 'evp',
        ];
        return $map[$tipo] ?? strtolower($tipo);
    }

    public function realizarSaque(
        float $valor,
        string $pixKey,
        string $keyType,
        string $keyDocument,
        string $externalId,
        ?string $clientCallbackUrl = null
    ): array {
        $keyTypeDetectado = $this->determinarTipoChavePix($pixKey);
        $keyDocumentLimpo = preg_replace('/\D/', '', $keyDocument ?? '');

        if (in_array($keyTypeDetectado, ['EMAIL', 'PHONENUMBER', 'RANDOM_KEY_CODE'], true) && empty($keyDocumentLimpo)) {
            return [
                'success' => false,
                'error' => 'Cadastre o CPF/CNPJ do destinatário para realizar transferências PIX para chaves do tipo email, telefone ou chave aleatória.',
                'last_response' => $this->lastResponse,
            ];
        }

        $pixKeyValor = in_array($keyTypeDetectado, ['PHONENUMBER'], true) ? $this->formatarChavePixTelefone($pixKey) : $pixKey;

        $documentoFinal = $keyDocumentLimpo;
        if (empty($documentoFinal) && in_array($keyTypeDetectado, ['CPF', 'CNPJ'], true)) {
            $documentoFinal = preg_replace('/\D/', '', $pixKey);
        }
        if (empty($documentoFinal)) {
            return [
                'success' => false,
                'error' => 'Cadastre o CPF/CNPJ do destinatário para realizar transferências PIX para chaves do tipo email, telefone ou chave aleatória.',
                'last_response' => $this->lastResponse,
            ];
        }

        $payload = [
            'amount' => $valor,
            'external_id' => $externalId,
            'pix_key' => $pixKeyValor,
            'key_type' => $this->mapearKeyTypeParaApix($keyTypeDetectado),
            'key_document' => $documentoFinal,
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

    /**
     * Determina o tipo da chave PIX para a API APIX (mesma lógica da XGate).
     * APIX aceita: cpf|cnpj|email|phonenumber|random_key_code ou maiúsculas (PHONENUMBER para celular).
     *
     * @param string $pixKey Chave PIX
     * @return string EMAIL, CPF, CNPJ, PHONENUMBER ou RANDOM_KEY_CODE
     */
    protected function determinarTipoChavePix(string $pixKey): string
    {
        if (strpos($pixKey, '@') !== false) {
            return 'EMAIL';
        }

        $pixKeyClean = preg_replace('/\D/', '', $pixKey);
        $len = strlen($pixKeyClean);

        if (($len === 12 || $len === 13) && strpos($pixKeyClean, '55') === 0) {
            return 'PHONENUMBER';
        }

        if ($len === 11) {
            return $this->validarCPF($pixKeyClean) ? 'CPF' : 'PHONENUMBER';
        }

        if ($len === 10) {
            return 'PHONENUMBER';
        }

        if ($len === 14) {
            return 'CNPJ';
        }

        if (strlen($pixKey) === 32 || strlen($pixKey) === 36) {
            return 'RANDOM_KEY_CODE';
        }

        return 'CPF';
    }

    /**
     * Formata chave PIX do tipo telefone com DDI 55 (ex.: +5561999999999).
     */
    protected function formatarChavePixTelefone(string $pixKey): string
    {
        $digits = preg_replace('/\D/', '', $pixKey);
        if (strlen($digits) >= 12 && strpos($digits, '55') === 0) {
            return '+' . $digits;
        }
        if (strlen($digits) === 11 || strlen($digits) === 10) {
            return '+55' . $digits;
        }
        return '+' . $digits;
    }

    /**
     * Valida CPF por dígitos verificadores (mesma lógica da XGate).
     */
    protected function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$t] != $digito) {
                return false;
            }
        }

        return true;
    }
}
