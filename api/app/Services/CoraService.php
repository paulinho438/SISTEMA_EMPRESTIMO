<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Banco;

class CoraService
{
    protected $baseUrl;
    protected $clientId;
    protected $certificatePath;
    protected $privateKeyPath;

    public function __construct(?Banco $banco = null)
    {
        // Para Integração Direta (usando certificado e chave privada):
        // Stage: https://api.stage.cora.com.br
        // Produção: https://api.cora.com.br
        // Configure no .env: CORA_API_URL=https://api.cora.com.br (produção)
        // ou CORA_API_URL=https://api.stage.cora.com.br (stage)
        $this->baseUrl = env('CORA_API_URL', 'https://api.stage.cora.com.br');

        if ($banco) {
            $this->clientId = $banco->client_id;
            $this->certificatePath = $banco->certificate_path;
            $this->privateKeyPath = $banco->private_key_path;
        }
    }

    /**
     * Retorna a URL base da API Cora
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Obtém o token de acesso usando Client Credentials (mTLS)
     *
     * @return string Token de acesso
     * @throws \Exception Se falhar ao obter o token
     */
    protected function getAccessToken(): string
    {
        // Verificar se já temos um token válido em cache
        $cacheKey = 'cora_access_token_' . md5($this->clientId . $this->certificatePath);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // URL do servidor de autorização (diferente da API principal)
        // Para Integração Direta:
        // Stage: https://matls-clients.api.stage.cora.com.br/token
        // Produção: https://matls-clients.api.cora.com.br/token
        $isStage = strpos($this->baseUrl, 'stage') !== false || strpos($this->baseUrl, 'matls-clients.api.stage') !== false;
        
        if ($isStage) {
            $tokenUrl = 'https://matls-clients.api.stage.cora.com.br/token';
        } else {
            // Produção
            $tokenUrl = 'https://matls-clients.api.cora.com.br/token';
        }

        // Verificar se temos certificados configurados
        if (!$this->certificatePath || !$this->privateKeyPath ||
            !file_exists($this->certificatePath) || !file_exists($this->privateKeyPath)) {
            throw new \Exception('Certificados Cora não configurados para obter token de acesso.');
        }

        // Verificar se os arquivos são legíveis
        if (!is_readable($this->certificatePath) || !is_readable($this->privateKeyPath)) {
            throw new \Exception('Certificados Cora não são legíveis. Verifique as permissões dos arquivos.');
        }

        // Configurar cliente HTTP com autenticação mTLS
        $httpClient = Http::asForm()->withOptions([
            'cert' => $this->certificatePath,
            'ssl_key' => $this->privateKeyPath,
            'verify' => env('CORA_VERIFY_SSL', true),
            'http_errors' => false,
        ]);

        // Fazer requisição para obter o token
        // O asForm() já foi aplicado acima, então não precisa chamar novamente
        Log::info('Solicitando token de acesso Cora', [
            'token_url' => $tokenUrl,
            'client_id' => $this->clientId,
            'grant_type' => 'client_credentials'
        ]);

        $response = $httpClient->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId
        ]);

        if (!$response->successful()) {
            $errorBody = $response->body();
            $errorJson = null;
            
            try {
                $errorJson = $response->json();
            } catch (\Exception $e) {
                // Ignorar
            }

            Log::error('Erro ao obter token de acesso Cora', [
                'status' => $response->status(),
                'body' => $errorBody,
                'json' => $errorJson,
                'token_url' => $tokenUrl,
                'client_id' => $this->clientId,
                'certificate_path' => $this->certificatePath,
                'private_key_path' => $this->privateKeyPath,
                'cert_exists' => file_exists($this->certificatePath),
                'key_exists' => file_exists($this->privateKeyPath),
                'cert_readable' => is_readable($this->certificatePath),
                'key_readable' => is_readable($this->privateKeyPath),
                'headers' => $response->headers()
            ]);

            $errorMessage = 'Falha ao obter token de acesso Cora';
            if ($errorJson) {
                $errorMessage .= ': ' . json_encode($errorJson);
                if (isset($errorJson['error_description'])) {
                    $errorMessage .= ' - ' . $errorJson['error_description'];
                }
            } else {
                $errorMessage .= ': ' . $errorBody;
            }

            throw new \Exception($errorMessage);
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 86400; // Default 24 horas

        if (!$accessToken) {
            throw new \Exception('Token de acesso não retornado pela API Cora');
        }

        // Armazenar token em cache (com margem de segurança de 60 segundos antes de expirar)
        Cache::put($cacheKey, $accessToken, $expiresIn - 60);

        Log::info('Token de acesso Cora obtido com sucesso', [
            'expires_in' => $expiresIn,
            'token_url' => $tokenUrl
        ]);

        return $accessToken;
    }

    /**
     * Cria uma cobrança (invoice) na API Cora
     *
     * @param float $valor Valor da cobrança em centavos
     * @param \App\Models\Client $cliente Cliente para quem será gerada a cobrança
     * @param string $code Código único da cobrança (geralmente ID da parcela)
     * @param string|null $dueDate Data de vencimento no formato Y-m-d
     * @param array $services Array de serviços (opcional, padrão usa o valor principal)
     * @return \Illuminate\Http\Client\Response
     */
    public function criarCobranca(
        float $valor,
        $cliente,
        string $code,
        ?string $dueDate = null,
        ?array $services = null
    ) {
        try {
            // Converter valor para centavos (a API Cora espera em centavos)
            $valorCentavos = (int)($valor * 100);

            // Obter endereço do cliente
            $endereco = $cliente->address()->first();

            // Preparar dados do cliente
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $documentType = strlen($document) === 11 ? 'CPF' : 'CNPJ';

            // Preparar dados do endereço
            // Se o cliente não tiver endereço, usar valores padrão
            $addressData = [
                'street' => $endereco ? ($endereco->address ?? 'N/A') : 'N/A',
                'number' => $endereco ? ($endereco->number ?? 'N/A') : 'N/A',
                'district' => $endereco ? ($endereco->neighborhood ?? 'N/A') : 'N/A',
                'city' => $endereco ? ($endereco->city ?? 'N/A') : 'N/A',
                'state' => $endereco ? ($endereco->state ?? 'SP') : 'SP', // Default SP
                'complement' => $endereco ? ($endereco->complement ?? 'N/A') : 'N/A',
                'zip_code' => $endereco ? preg_replace('/\D/', '', $endereco->cep ?? '00000000') : '00000000'
            ];

            // Preparar serviços
            if (!$services) {
                $services = [
                    [
                        'name' => 'Parcela de Empréstimo',
                        'amount' => $valorCentavos
                    ]
                ];
            }

            // Data de vencimento padrão (30 dias a partir de hoje)
            if (!$dueDate) {
                $dueDate = date('Y-m-d', strtotime('+30 days'));
            }

            // Gerar Idempotency-Key único
            $idempotencyKey = bin2hex(random_bytes(16));

            // Montar payload
            $data = [
                'code' => $code,
                'customer' => [
                    'name' => $cliente->nome_completo,
                    'email' => $cliente->email,
                    'document' => [
                        'identity' => $document,
                        'type' => $documentType
                    ],
                    'address' => $addressData
                ],
                'services' => $services,
                'payment_terms' => [
                    'due_date' => $dueDate,
                    'fine' => [
                        'Amount' => 0
                    ],
                    'discount' => [
                        'type' => 'PERCENT',
                        'value' => 0
                    ]
                ],
                'notification' => [
                    'name' => $cliente->nome_completo,
                    'channels' => [
                        [
                            'channel' => 'EMAIL',
                            'contact' => $cliente->email,
                            'rules' => [
                                'NOTIFY_TWO_DAYS_BEFORE_DUE_DATE',
                                'NOTIFY_WHEN_PAID'
                            ]
                        ]
                    ]
                ],
                'payment_forms' => [
                    'PIX'
                ]
            ];

            // Adicionar SMS se tiver telefone
            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $data['notification']['channels'][] = [
                        'channel' => 'SMS',
                        'contact' => '+' . $phone,
                        'rules' => [
                            'NOTIFY_TWO_DAYS_BEFORE_DUE_DATE',
                            'NOTIFY_WHEN_PAID'
                        ]
                    ];
                }
            }

            $url = "{$this->baseUrl}/v2/invoices/";

            // Obter token de acesso (usando Client Credentials)
            $accessToken = $this->getAccessToken();

            // Preparar headers
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Idempotency-Key' => $idempotencyKey,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ];

            // Configurar cliente HTTP (não precisa mais de mTLS para a API, apenas para obter o token)
            $httpClient = Http::withHeaders($headers)->withOptions([
                'http_errors' => false, // Não lançar exceções para erros HTTP
            ]);

            $inicioAtualizacao = microtime(true);

            $response = $httpClient->post($url, $data);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA CORA - Tempo para chamar: {$duracaoAtualizacao}s", [
                'status' => $response->status(),
                'url' => $url
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorJson = null;
                
                try {
                    $errorJson = $response->json();
                } catch (\Exception $e) {
                    // Se não conseguir converter para JSON, mantém o body como string
                }
                
                Log::error('Erro ao criar cobrança Cora', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'headers' => $response->headers(),
                    'url' => $url,
                    'certificate_path' => $this->certificatePath,
                    'private_key_path' => $this->privateKeyPath,
                    'client_id' => $this->clientId,
                    'base_url' => $this->baseUrl,
                    'request_headers' => $headers
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao criar cobrança Cora: ' . $e->getMessage());
            throw $e;
        }
    }
}

