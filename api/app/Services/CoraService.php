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
            $addressData = [
                'street' => $endereco->address ?? 'N/A',
                'number' => $endereco->number ?? 'N/A',
                'district' => $endereco->neighborhood ?? 'N/A',
                'city' => $endereco->city ?? 'N/A',
                'state' => 'SP', // Default, pode ser ajustado
                'complement' => $endereco->complement ?? 'N/A',
                'zip_code' => preg_replace('/\D/', '', $endereco->cep ?? '00000000')
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

            // Configurar cliente HTTP com autenticação mTLS se tiver certificado
            $httpClient = Http::withHeaders($headers);

            if ($this->certificatePath && $this->privateKeyPath &&
                file_exists($this->certificatePath) && file_exists($this->privateKeyPath)) {
                // Autenticação mTLS com certificado e chave privada
                $httpClient = $httpClient->withOptions([
                    'cert' => $this->certificatePath,
                    'ssl_key' => $this->privateKeyPath,
                    'verify' => true // Verificar certificado do servidor
                ]);
                Log::info('Autenticação mTLS Cora configurada com certificado e chave privada');
            } elseif ($this->certificatePath && file_exists($this->certificatePath)) {
                Log::warning('Certificado Cora encontrado mas chave privada não configurada');
            }

            $inicioAtualizacao = microtime(true);

            $response = $httpClient->post($url, $data);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA CORA - Tempo para chamar: {$duracaoAtualizacao}s");

            if (!$response->successful()) {
                Log::error('Erro ao criar cobrança Cora: ' . $response->body());
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao criar cobrança Cora: ' . $e->getMessage());
            throw $e;
        }
    }
}

