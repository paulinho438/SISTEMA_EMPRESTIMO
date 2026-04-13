<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Banco;
use App\Models\Client;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integração GoldPix (https://api.goldpix.tech) — autenticação X-API-Key.
 */
class GoldPixService
{
    protected string $baseUrl = 'https://api.goldpix.tech';

    protected ?Banco $banco = null;

    protected ?string $apiKey = null;

    /** @var array|null */
    protected $lastResponse = null;

    public function __construct(?Banco $banco = null)
    {
        $this->banco = $banco;

        if (!$banco || ($banco->bank_type ?? '') !== 'goldpix') {
            throw new \Exception('Banco não é do tipo GoldPix.');
        }

        $key = $banco->goldpix_api_key ?? null;
        if ($key) {
            try {
                $key = Crypt::decryptString($key);
            } catch (\Exception $e) {
                // já em texto plano
            }
        }
        if (empty($key)) {
            throw new \Exception('API Key GoldPix não configurada para este banco.');
        }

        $this->apiKey = $key;
        $this->baseUrl = rtrim($banco->goldpix_base_url ?: $this->baseUrl, '/');
    }

    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    protected function makeRequest(string $method, string $path, array $body = []): \Illuminate\Http\Client\Response
    {
        $url = $this->baseUrl . $path;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->apiKey,
        ];

        Log::channel('goldpix')->info('GoldPix REQUEST', ['method' => $method, 'url' => $url, 'body' => $body]);

        $http = Http::withHeaders($headers)->timeout(60);
        $response = strtoupper($method) === 'GET'
            ? $http->get($url, $body)
            : $http->{strtolower($method)}($url, $body);

        $this->lastResponse = [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
        Log::channel('goldpix')->info('GoldPix RESPONSE', $this->lastResponse);

        return $response;
    }

    /**
     * Monta endereço mínimo obrigatório pela API (CustomerDto).
     */
    protected function montarEnderecoCliente(Client $cliente): array
    {
        /** @var Address|null $addr */
        $addr = $cliente->relationLoaded('address')
            ? $cliente->address->first()
            : $cliente->address()->first();

        if ($addr) {
            return [
                'street' => $addr->address ?: 'Não informado',
                'number' => $addr->number ?: 'S/N',
                'complement' => $addr->complement ?: '',
                'neighborhood' => $addr->neighborhood ?: 'Centro',
                'city' => $addr->city ?: 'Brasília',
                'state' => 'DF',
                'zipCode' => preg_replace('/\D/', '', $addr->cep ?: '70000000') ?: '70000000',
            ];
        }

        return [
            'street' => 'Não informado',
            'number' => 'S/N',
            'complement' => '',
            'neighborhood' => 'Centro',
            'city' => 'Brasília',
            'state' => 'DF',
            'zipCode' => '70000000',
        ];
    }

    protected function montarTelefoneCliente(Client $cliente): string
    {
        $digits = preg_replace('/\D/', '', $cliente->telefone_celular_1 ?? $cliente->telefone_celular_2 ?? '');
        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            return '+' . $digits;
        }
        if (strlen($digits) === 11 || strlen($digits) === 10) {
            return '+55' . $digits;
        }

        return '+5511999999999';
    }

    /**
     * Cria transação PIX (POST /api/v1/transactions).
     *
     * @return array{success:bool,transaction_id?:string,txid?:string,pixCopiaECola?:string,error?:string,response?:mixed,last_response?:array}
     */
    public function criarCobrancaPix(
        float $valor,
        Client $cliente,
        string $externalId,
        ?string $postbackUrl = null,
        int $expiresInDays = 7,
        ?string $description = null
    ): array {
        $doc = preg_replace('/\D/', '', $cliente->cnpj ?? $cliente->cpf ?? '');
        $documentType = strlen($doc) >= 14 ? 'CNPJ' : 'CPF';

        $payload = [
            'externalId' => $externalId,
            'amount' => round($valor, 2),
            'description' => $description ?: ('Cobrança PIX ' . $externalId),
            'paymentMethod' => 'pix',
            'customer' => [
                'document' => $doc,
                'documentType' => $documentType,
                'name' => $cliente->nome_completo ?: 'Cliente',
                'email' => $cliente->email ?: 'nao-informado@exemplo.com',
                'phone' => $this->montarTelefoneCliente($cliente),
                'address' => $this->montarEnderecoCliente($cliente),
            ],
            'cart' => [
                'items' => [[
                    'productId' => 'COB_' . substr(preg_replace('/\W/', '', $externalId), 0, 20),
                    'name' => 'Cobrança empréstimo',
                    'description' => 'Pagamento',
                    'quantity' => 1,
                    'price' => round($valor, 2),
                    'tangible' => false,
                ]],
            ],
            'expiresInDays' => min(30, max(1, $expiresInDays)),
        ];

        if (!empty($postbackUrl)) {
            $payload['postbackUrl'] = $postbackUrl;
        }

        $response = $this->makeRequest('POST', '/api/v1/transactions', $payload);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = is_array($err) ? ($err['message'] ?? $err['error'] ?? json_encode($err)) : (string) $response->body();

            return [
                'success' => false,
                'error' => is_string($msg) ? $msg : 'Erro ao criar transação GoldPix',
                'last_response' => $this->lastResponse,
            ];
        }

        $data = $response->json();
        $id = $data['id'] ?? null;
        $pixKey = $data['pix']['pixKey'] ?? $data['pixKey'] ?? null;

        return [
            'success' => true,
            'transaction_id' => $id,
            'txid' => $id,
            'pixCopiaECola' => $pixKey,
            'qr_code' => $pixKey,
            'external_id' => $externalId,
            'response' => $data,
            'last_response' => $this->lastResponse,
        ];
    }

    /**
     * Alias compatível com EmprestimoController (mesma assinatura conceitual que APIX para cobrança).
     */
    public function criarCobranca(float $valor, Client $cliente, string $referenceId, ?string $dueDate = null): array
    {
        $postback = config('services.goldpix.callback_url')
            ?: (config('app.url') ? rtrim(config('app.url'), '/') . '/api/webhook/goldpix' : null);

        $expires = 7;
        if (!empty($dueDate)) {
            try {
                $diff = (new \DateTime($dueDate))->diff(new \DateTime('today'))->days;
                $expires = max(1, min(30, $diff ?: 7));
            } catch (\Throwable $e) {
                $expires = 7;
            }
        }

        return $this->criarCobrancaPix(
            $valor,
            $cliente,
            $referenceId,
            $postback,
            $expires,
            'Parcela / cobrança empréstimo'
        );
    }

    /**
     * POST /api/seller/withdrawals — pixKeyType: cpf|cnpj|email|phone|random
     */
    public function solicitarSaque(
        float $valor,
        string $pixKey,
        string $pixKeyTypeGoldpix,
        ?string $externalId = null,
        ?string $postbackUrl = null
    ): array {
        $body = [
            'amount' => round($valor, 2),
            'pixKey' => $pixKey,
            'pixKeyType' => $pixKeyTypeGoldpix,
        ];
        if ($externalId) {
            $body['externalId'] = $externalId;
        }
        if ($postbackUrl) {
            $body['postbackUrl'] = $postbackUrl;
        }

        $response = $this->makeRequest('POST', '/api/seller/withdrawals', $body);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = is_array($err) ? ($err['message'] ?? $err['error'] ?? json_encode($err)) : (string) $response->body();

            return [
                'success' => false,
                'error' => is_string($msg) ? $msg : 'Erro ao solicitar saque GoldPix',
                'last_response' => $this->lastResponse,
            ];
        }

        $data = $response->json();
        $id = $data['id'] ?? $data['withdrawalId'] ?? $data['readableId'] ?? $externalId;

        return [
            'success' => true,
            'transaction_id' => $id,
            'response' => $data,
            'last_response' => $this->lastResponse,
        ];
    }

    public function consultarSaldoDisponivel(): array
    {
        $response = $this->makeRequest('GET', '/api/seller/withdrawals/balance', []);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = is_array($err) ? ($err['message'] ?? $err['error'] ?? 'Erro') : (string) $response->body();

            return [
                'success' => false,
                'error' => $msg,
                'last_response' => $this->lastResponse,
            ];
        }

        $data = $response->json();
        $balance = $data['availableBalance'] ?? $data['data']['availableBalance'] ?? 0;

        return [
            'success' => true,
            'balance' => (float) $balance,
            'response' => $data,
            'last_response' => $this->lastResponse,
        ];
    }

    /**
     * Mapeia tipo de chave (lógica similar à APIX) para enum GoldPix.
     */
    public function mapearPixKeyTypeParaGoldPix(string $pixKey): string
    {
        if (strpos($pixKey, '@') !== false) {
            return 'email';
        }
        $clean = preg_replace('/\D/', '', $pixKey);
        $len = strlen($clean);
        if (($len === 12 || $len === 13) && str_starts_with($clean, '55')) {
            return 'phone';
        }
        if ($len === 11 && $this->validarCpf($clean)) {
            return 'cpf';
        }
        if ($len === 10 || $len === 11) {
            return 'phone';
        }
        if ($len === 14) {
            return 'cnpj';
        }
        if (strlen($pixKey) === 32 || strlen($pixKey) === 36) {
            return 'random';
        }

        return 'cpf';
    }

    /**
     * @return array{ok:bool,pix_key_type?:string,error?:string}
     */
    public function obterPixKeyTypeParaSaque(string $pixKey, ?string $cpfCnpjDestino): array
    {
        $tipo = $this->mapearPixKeyTypeParaGoldPix($pixKey);
        $doc = preg_replace('/\D/', '', $cpfCnpjDestino ?? '');

        if (in_array($tipo, ['email', 'phone', 'random'], true) && $doc === '') {
            return [
                'ok' => false,
                'error' => 'Cadastre o CPF/CNPJ do destinatário para chaves do tipo e-mail, telefone ou aleatória.',
            ];
        }

        return ['ok' => true, 'pix_key_type' => $tipo];
    }

    protected function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
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

    protected function formatarChavePixTelefone(string $pixKey): string
    {
        $digits = preg_replace('/\D/', '', $pixKey);
        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            return '+' . $digits;
        }
        if (strlen($digits) === 11 || strlen($digits) === 10) {
            return '+55' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Chave PIX formatada para o payload (telefone com +55).
     */
    public function normalizarChavePixParaEnvio(string $pixKey, string $tipo): string
    {
        if ($tipo === 'phone') {
            return $this->formatarChavePixTelefone($pixKey);
        }

        return $pixKey;
    }
}
