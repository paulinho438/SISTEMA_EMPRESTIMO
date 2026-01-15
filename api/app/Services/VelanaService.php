<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Banco;
use Illuminate\Support\Facades\Crypt;

class VelanaService
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct(?Banco $banco = null)
    {
        $this->baseUrl = 'https://api.velana.com.br/v1';

        if ($banco && $banco->velana_secret_key) {
            // Descriptografar a chave secreta
            try {
                $this->secretKey = Crypt::decryptString($banco->velana_secret_key);
            } catch (\Exception $e) {
                Log::error('Erro ao descriptografar chave secreta Velana: ' . $e->getMessage());
                throw new \Exception('Erro ao descriptografar chave secreta Velana');
            }
        }
    }

    /**
     * Obtém o token de autenticação Basic
     *
     * @return string Token de autenticação Basic
     */
    protected function getAuthHeader(): string
    {
        if (!$this->secretKey) {
            throw new \Exception('Chave secreta Velana não configurada');
        }

        // Formato: Basic base64("{SECRET_KEY}:x")
        $credentials = $this->secretKey . ':x';
        return 'Basic ' . base64_encode($credentials);
    }

    /**
     * Cria um checkout na API Velana
     *
     * @param float $valor Valor do checkout
     * @param \App\Models\Client $cliente Cliente para quem será gerado o checkout
     * @param string $referenceId ID de referência único (geralmente ID do empréstimo)
     * @param string|null $description Descrição do checkout
     * @return \Illuminate\Http\Client\Response
     */
    public function criarCheckout(
        float $valor,
        $cliente,
        string $referenceId,
        ?string $description = null
    ) {
        try {
            // Converter valor para centavos (a API Velana espera em centavos)
            $valorCentavos = (int)($valor * 100);

            // Preparar dados do cliente
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $documentType = strlen($document) === 11 ? 'CPF' : 'CNPJ';

            // Montar payload
            $data = [
                'amount' => $valorCentavos,
                'payment_method' => 'PIX',
                'customer' => [
                    'name' => $cliente->nome_completo,
                    'email' => $cliente->email ?? '',
                    'document' => [
                        'type' => $documentType,
                        'number' => $document
                    ]
                ],
                'reference_id' => $referenceId,
                'description' => $description ?? 'Empréstimo - ' . $cliente->nome_completo
            ];

            // Adicionar telefone se disponível
            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $data['customer']['phone'] = [
                        'country_code' => '55',
                        'area_code' => substr($phone, 0, 2),
                        'number' => substr($phone, 2)
                    ];
                }
            }

            $url = $this->baseUrl . '/checkouts';
            $authHeader = $this->getAuthHeader();

            $inicioAtualizacao = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])->post($url, $data);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA VELANA CHECKOUT - Tempo para chamar: {$duracaoAtualizacao}s", [
                'status' => $response->status(),
                'url' => $url
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorJson = null;
                
                try {
                    $errorJson = $response->json();
                } catch (\Exception $e) {
                    // Ignorar
                }
                
                Log::error('Erro ao criar checkout Velana', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'url' => $url
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao criar checkout Velana: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cria uma transação (cobrança) na API Velana
     *
     * @param float $valor Valor da cobrança
     * @param \App\Models\Client $cliente Cliente para quem será gerada a cobrança
     * @param string $referenceId ID de referência único (geralmente ID da parcela)
     * @param string|null $dueDate Data de vencimento no formato Y-m-d
     * @return \Illuminate\Http\Client\Response
     */
    public function criarCobranca(
        float $valor,
        $cliente,
        string $referenceId,
        ?string $dueDate = null
    ) {
        try {
            // Converter valor para centavos
            $valorCentavos = (int)($valor * 100);

            // Preparar dados do cliente
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $documentType = strlen($document) === 11 ? 'CPF' : 'CNPJ';

            // Data de vencimento padrão (30 dias a partir de hoje)
            if (!$dueDate) {
                $dueDate = date('Y-m-d', strtotime('+30 days'));
            }

            // Montar payload
            $data = [
                'amount' => $valorCentavos,
                'payment_method' => 'PIX',
                'customer' => [
                    'name' => $cliente->nome_completo,
                    'email' => $cliente->email ?? '',
                    'document' => [
                        'type' => $documentType,
                        'number' => $document
                    ]
                ],
                'reference_id' => $referenceId,
                'description' => 'Parcela de Empréstimo - ' . $cliente->nome_completo,
                'due_date' => $dueDate
            ];

            // Adicionar telefone se disponível
            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $data['customer']['phone'] = [
                        'country_code' => '55',
                        'area_code' => substr($phone, 0, 2),
                        'number' => substr($phone, 2)
                    ];
                }
            }

            $url = $this->baseUrl . '/transactions';
            $authHeader = $this->getAuthHeader();

            $inicioAtualizacao = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])->post($url, $data);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA VELANA COBRANÇA - Tempo para chamar: {$duracaoAtualizacao}s", [
                'status' => $response->status(),
                'url' => $url
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorJson = null;
                
                try {
                    $errorJson = $response->json();
                } catch (\Exception $e) {
                    // Ignorar
                }
                
                Log::error('Erro ao criar cobrança Velana', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'url' => $url
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao criar cobrança Velana: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca um checkout por ID
     *
     * @param string $checkoutId ID do checkout
     * @return \Illuminate\Http\Client\Response
     */
    public function buscarCheckout(string $checkoutId)
    {
        try {
            $url = $this->baseUrl . '/checkouts/' . $checkoutId;
            $authHeader = $this->getAuthHeader();

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'accept' => 'application/json'
            ])->get($url);

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar checkout Velana: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca uma transação por ID
     *
     * @param string $transactionId ID da transação
     * @return \Illuminate\Http\Client\Response
     */
    public function buscarTransacao(string $transactionId)
    {
        try {
            $url = $this->baseUrl . '/transactions/' . $transactionId;
            $authHeader = $this->getAuthHeader();

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'accept' => 'application/json'
            ])->get($url);

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar transação Velana: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Realiza uma transferência PIX para o cliente
     *
     * @param float $valor Valor da transferência em reais
     * @param string $pixKey Chave PIX do destinatário
     * @param string $description Descrição da transferência
     * @return \Illuminate\Http\Client\Response
     */
    public function realizarTransferenciaPix(
        float $valor,
        string $pixKey,
        string $description = 'Transferência PIX'
    ) {
        try {
            // Converter valor para centavos
            $valorCentavos = (int)($valor * 100);

            // Montar payload
            $data = [
                'amount' => $valorCentavos,
                'payment_method' => 'PIX',
                'pix_key' => $pixKey,
                'description' => $description
            ];

            $url = $this->baseUrl . '/transfers';
            $authHeader = $this->getAuthHeader();

            $inicioAtualizacao = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ])->post($url, $data);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA VELANA TRANSFERÊNCIA PIX - Tempo para chamar: {$duracaoAtualizacao}s", [
                'status' => $response->status(),
                'url' => $url
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorJson = null;
                
                try {
                    $errorJson = $response->json();
                } catch (\Exception $e) {
                    // Ignorar
                }
                
                Log::error('Erro ao realizar transferência PIX Velana', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'json' => $errorJson,
                    'url' => $url
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Erro ao realizar transferência PIX Velana: ' . $e->getMessage());
            throw $e;
        }
    }
}

