<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Banco;
use App\Models\Client;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integração PixGo (https://pixgo.org/api/v1) — autenticação X-API-Key.
 *
 * @see https://pixgo.org/api/v1/docs#endpoints
 */
class PixGoService
{
    protected string $baseUrl = 'https://pixgo.org/api/v1';

    protected ?Banco $banco = null;

    protected ?string $apiKey = null;

    /** @var array|null */
    protected $lastResponse = null;

    public function __construct(?Banco $banco = null)
    {
        $this->banco = $banco;

        if (!$banco || ($banco->bank_type ?? '') !== 'pixgo') {
            throw new \Exception('Banco não é do tipo PixGo.');
        }

        $key = $banco->pixgo_api_key ?? null;
        if ($key) {
            try {
                $key = Crypt::decryptString($key);
            } catch (\Exception $e) {
            }
        }
        if (empty($key)) {
            throw new \Exception('API Key PixGo não configurada para este banco.');
        }

        $this->apiKey = $key;
        $this->baseUrl = rtrim($banco->pixgo_base_url ?: $this->baseUrl, '/');
    }

    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    protected function makeRequest(string $method, string $path, array $body = []): \Illuminate\Http\Client\Response
    {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . $path;
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->apiKey,
        ];

        Log::channel('pixgo')->info('PixGo REQUEST', ['method' => $method, 'url' => $url, 'body' => $body]);

        $http = Http::withHeaders($headers)->timeout(60);
        $response = strtoupper($method) === 'GET'
            ? $http->get($url, $body)
            : $http->{strtolower($method)}($url, $body);

        $this->lastResponse = [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
        Log::channel('pixgo')->info('PixGo RESPONSE', $this->lastResponse);

        return $response;
    }

    protected function montarEnderecoTexto(Client $cliente): string
    {
        /** @var Address|null $addr */
        $addr = $cliente->relationLoaded('address')
            ? $cliente->address->first()
            : $cliente->address()->first();

        if ($addr) {
            $parts = array_filter([
                $addr->address,
                $addr->number ? 'nº ' . $addr->number : null,
                $addr->complement,
                $addr->neighborhood,
                $addr->city,
                $addr->state,
                $addr->cep,
            ]);
            $s = implode(', ', $parts);

            return mb_substr($s !== '' ? $s : 'Endereço não informado', 0, 500);
        }

        return 'Endereço não informado';
    }

    protected function montarTelefoneCliente(Client $cliente): ?string
    {
        $digits = preg_replace('/\D/', '', $cliente->telefone_celular_1 ?? $cliente->telefone_celular_2 ?? '');
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }
        if ($digits !== '') {
            return mb_substr($digits, 0, 20);
        }

        return null;
    }

    /**
     * POST /payment/create.
     *
     * @return array{success:bool,transaction_id?:string,txid?:string,pixCopiaECola?:string,qr_code?:string,error?:string,response?:mixed,last_response?:array}
     */
    public function criarCobranca(float $valor, Client $cliente, string $externalId, ?string $dueDate = null, ?string $description = null): array
    {
        $valor = round($valor, 2);
        if ($valor < 10.0) {
            return [
                'success' => false,
                'error' => 'PixGo exige valor mínimo de R$ 10,00 por cobrança.',
                'last_response' => $this->lastResponse,
            ];
        }

        $cliente->loadMissing('address');
        $doc = preg_replace('/\D/', '', $cliente->cnpj ?? $cliente->cpf ?? '');

        $webhookUrl = config('services.pixgo.callback_url')
            ?: (config('app.url') ? rtrim((string) config('app.url'), '/') . '/api/webhook/pixgo' : null);

        $payload = [
            'amount' => $valor,
            'description' => $description ?: ('Cobrança empréstimo ' . $externalId),
            'customer_name' => $cliente->nome_completo ?: 'Cliente',
            'external_id' => mb_substr((string) $externalId, 0, 50),
        ];
        if ($doc !== '') {
            $payload['customer_cpf'] = $doc;
        }
        if (!empty($cliente->email)) {
            $payload['customer_email'] = mb_substr((string) $cliente->email, 0, 255);
        }
        $phone = $this->montarTelefoneCliente($cliente);
        if ($phone) {
            $payload['customer_phone'] = mb_substr($phone, 0, 20);
        }
        $payload['customer_address'] = $this->montarEnderecoTexto($cliente);
        if (!empty($webhookUrl)) {
            $payload['webhook_url'] = $webhookUrl;
        }

        $response = $this->makeRequest('POST', '/payment/create', $payload);

        if ($response->status() !== 201) {
            $err = $response->json();
            $msg = is_array($err) ? ($err['message'] ?? $err['error'] ?? json_encode($err)) : (string) $response->body();

            return [
                'success' => false,
                'error' => is_string($msg) ? $msg : 'Erro ao criar pagamento PixGo',
                'last_response' => $this->lastResponse,
            ];
        }

        $json = $response->json();
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];
        $paymentId = $data['payment_id'] ?? null;
        $qr = $data['qr_code'] ?? null;

        return [
            'success' => true,
            'transaction_id' => $paymentId,
            'txid' => $paymentId,
            'pixCopiaECola' => $qr,
            'qr_code' => $qr,
            'external_id' => $data['external_id'] ?? $externalId,
            'response' => $json,
            'last_response' => $this->lastResponse,
        ];
    }
}
