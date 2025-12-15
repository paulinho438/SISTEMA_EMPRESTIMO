<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Banco;

class CoraService
{
    protected $baseUrl;
    protected $clientId;
    protected $certificatePath;
    protected $privateKeyPath;

    public function __construct(?Banco $banco = null)
    {
        $this->baseUrl = env('CORA_API_URL', 'https://api.stage.cora.com.br');

        if ($banco) {
            $this->clientId = $banco->client_id;
            $this->certificatePath = $banco->certificate_path;
            $this->privateKeyPath = $banco->private_key_path;
        }
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

            // Preparar headers
            $headers = [
                'Idempotency-Key' => $idempotencyKey,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ];

            // Adicionar Client ID no header se disponível
            // A API Cora pode precisar do Client ID em diferentes headers
            if ($this->clientId) {
                // Tentar diferentes formatos de header para Client ID
                $headers['X-Client-Id'] = $this->clientId;
                // Algumas implementações da Cora podem usar este formato
                // $headers['Client-Id'] = $this->clientId;
            }

            // Configurar cliente HTTP com autenticação mTLS se tiver certificado
            $httpClient = Http::withHeaders($headers);

            if ($this->certificatePath && $this->privateKeyPath &&
                file_exists($this->certificatePath) && file_exists($this->privateKeyPath)) {
                
                // Verificar se os arquivos são legíveis
                if (!is_readable($this->certificatePath) || !is_readable($this->privateKeyPath)) {
                    Log::error('Certificados Cora não são legíveis', [
                        'cert_readable' => is_readable($this->certificatePath),
                        'key_readable' => is_readable($this->privateKeyPath)
                    ]);
                    throw new \Exception('Certificados Cora não são legíveis. Verifique as permissões dos arquivos.');
                }
                
                // Autenticação mTLS com certificado e chave privada
                // Guzzle requer 'cert' e 'ssl_key' separados:
                // - 'cert' => caminho do certificado (ou array [cert_path, password] se tiver senha)
                // - 'ssl_key' => caminho da chave privada (ou array [key_path, password] se tiver senha)
                
                $httpClient = $httpClient->withOptions([
                    'cert' => $this->certificatePath, // Caminho do certificado
                    'ssl_key' => $this->privateKeyPath, // Caminho da chave privada (separado)
                    'verify' => env('CORA_VERIFY_SSL', true), // Pode desabilitar para debug
                    'http_errors' => false, // Não lançar exceções para erros HTTP
                ]);
                
                Log::info('Autenticação mTLS Cora configurada', [
                    'certificate_path' => $this->certificatePath,
                    'private_key_path' => $this->privateKeyPath,
                    'client_id' => $this->clientId,
                    'cert_exists' => file_exists($this->certificatePath),
                    'key_exists' => file_exists($this->privateKeyPath),
                    'cert_readable' => is_readable($this->certificatePath),
                    'key_readable' => is_readable($this->privateKeyPath),
                    'cert_size' => filesize($this->certificatePath),
                    'key_size' => filesize($this->privateKeyPath)
                ]);
            } elseif ($this->certificatePath && file_exists($this->certificatePath)) {
                Log::warning('Certificado Cora encontrado mas chave privada não configurada', [
                    'certificate_path' => $this->certificatePath
                ]);
            } else {
                Log::error('Certificados Cora não configurados ou não encontrados', [
                    'certificate_path' => $this->certificatePath,
                    'private_key_path' => $this->privateKeyPath,
                    'client_id' => $this->clientId,
                    'cert_exists' => $this->certificatePath ? file_exists($this->certificatePath) : false,
                    'key_exists' => $this->privateKeyPath ? file_exists($this->privateKeyPath) : false
                ]);
                throw new \Exception('Certificados Cora não configurados. Verifique os caminhos no banco de dados.');
            }

            $inicioAtualizacao = microtime(true);

            $response = $httpClient->post($url, $data);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA CORA - Tempo para chamar: {$duracaoAtualizacao}s", [
                'status' => $response->status(),
                'url' => $url
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorJson = $response->json();
                
                Log::error('Erro ao criar cobrança Cora', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'headers' => $response->headers(),
                    'certificate_path' => $this->certificatePath,
                    'private_key_path' => $this->privateKeyPath,
                    'client_id' => $this->clientId
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao criar cobrança Cora: ' . $e->getMessage());
            throw $e;
        }
    }
}

