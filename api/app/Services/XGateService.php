<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Banco;
use Exception;

class XGateService
{
    protected $baseUrl = 'https://api.xgateglobal.com';
    protected $banco;
    protected $email;
    protected $password;
    protected $token = null;
    protected $tokenExpiresAt = null;
    /** @var array|null Última resposta da API (status + body) para log em caso de erro */
    protected $lastResponse = null;

    public function __construct(?Banco $banco = null)
    {
        $this->banco = $banco;

        if ($banco && $banco->xgate_email && $banco->xgate_password) {
            try {
                // Descriptografar senha se necessário
                $this->email = $banco->xgate_email;
                $this->password = $banco->xgate_password;
                
                try {
                    $this->password = Crypt::decryptString($banco->xgate_password);
                } catch (\Exception $e) {
                    // Se não conseguir descriptografar, usar o valor direto (pode já estar descriptografado)
                    $this->password = $banco->xgate_password;
                }

                // Fazer login para obter token
                $this->authenticate();
            } catch (\Exception $e) {
                Log::channel('xgate')->error('Erro ao inicializar XGate: ' . $e->getMessage());
                throw new \Exception('Erro ao inicializar XGate: ' . $e->getMessage());
            }
        } else {
            throw new \Exception('Credenciais XGate não configuradas');
        }
    }

    /**
     * Autentica e obtém token de acesso
     *
     * @return string Token de acesso
     */
    protected function authenticate(): string
    {
        // Verificar se temos um token válido em cache
        $cacheKey = 'xgate_token_' . md5($this->email);
        
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $this->token = $cached['token'];
            $this->tokenExpiresAt = $cached['expires_at'];
            
            // Se o token ainda é válido (com margem de 5 minutos), usar
            if ($this->tokenExpiresAt > (time() + 300)) {
                return $this->token;
            }
        }

        // Fazer login
        $url = $this->baseUrl . '/auth/token';
        
        $response = Http::asJson()->post($url, [
            'email' => $this->email,
            'password' => $this->password
        ]);

        if (!$response->successful()) {
            $errorMessage = $response->json()['message'] ?? 'Erro ao autenticar na API XGate';
            throw new \Exception('Erro ao autenticar: ' . $errorMessage);
        }

        $data = $response->json();
        $this->token = $data['token'];
        
        // Token JWT geralmente expira em 48 horas (conforme documentação)
        // Vamos cachear por 47 horas para garantir
        $this->tokenExpiresAt = time() + (47 * 60 * 60);
        
        Cache::put($cacheKey, [
            'token' => $this->token,
            'expires_at' => $this->tokenExpiresAt
        ], now()->addHours(47));

        return $this->token;
    }

    /**
     * Garante que temos um token válido
     *
     * @return string Token de acesso
     */
    protected function ensureAuthenticated(): string
    {
        if (!$this->token || ($this->tokenExpiresAt && $this->tokenExpiresAt <= time())) {
            return $this->authenticate();
        }
        
        return $this->token;
    }

    /**
     * Faz uma requisição HTTP autenticada
     *
     * @param string $method Método HTTP
     * @param string $endpoint Endpoint da API
     * @param array $data Dados da requisição
     * @return \Illuminate\Http\Client\Response
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $token = $this->ensureAuthenticated();
        $url = $this->baseUrl . $endpoint;

        // Preparar headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ];

        // Log da requisição (sem curl)
        $this->logRequestDetails($method, $endpoint, $data, "XGate API Request");

        // Fazer requisição
        $httpClient = Http::withHeaders($headers);
        
        switch (strtoupper($method)) {
            case 'GET':
                $response = $httpClient->get($url, $data);
                break;
            case 'POST':
                $response = $httpClient->post($url, $data);
                break;
            case 'PUT':
                $response = $httpClient->put($url, $data);
                break;
            case 'DELETE':
                $response = $httpClient->delete($url, $data);
                break;
            default:
                $response = $httpClient->post($url, $data);
        }

        // Sempre registrar no log o retorno da chamada
        $this->lastResponse = [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
        Log::channel('xgate')->info('XGATE RESPONSE', $this->lastResponse);

        return $response;
    }

    /**
     * Cria uma cobrança PIX (depósito FIAT)
     *
     * @param float $valor Valor da cobrança
     * @param \App\Models\Client $cliente Cliente para quem será gerada a cobrança
     * @param string $referenceId ID de referência único (geralmente ID da parcela)
     * @param string|null $dueDate Data de vencimento no formato Y-m-d
     * @return array
     */
    public function criarCobranca(
        float $valor,
        $cliente,
        string $referenceId,
        ?string $dueDate = null
    ) {
        try {
            $inicioAtualizacao = microtime(true);

            // Preparar dados do cliente
            $document = preg_replace('/\D/', '', $cliente->cpf);
            
            // Preparar dados do customer - apenas campos obrigatórios
            $customerData = [
                'name' => $cliente->nome_completo,
                'document' => $document
            ];

            // Adicionar email apenas se disponível e não vazio
            if (!empty($cliente->email)) {
                $customerData['email'] = $cliente->email;
            }

            // Adicionar telefone apenas se disponível e válido
            if (!empty($cliente->telefone_celular_1)) {
                $phoneObj = $this->criarObjetoPhone($cliente->telefone_celular_1);
                if ($phoneObj) {
                    $customerData['phone'] = $phoneObj;
                }
            }

            // Criar cliente primeiro (ou obter ID se já existir)
            $customerId = $this->criarOuObterCliente($customerData);

            // Preparar dados do depósito
            $depositData = [
                'amount' => $valor,
                'customerId' => $customerId
            ];

            // Obter currency (PIX)
            $currencies = $this->getCurrenciesDeposit();
            $pixCurrency = null;
            foreach ($currencies as $currency) {
                if (isset($currency['type']) && $currency['type'] === 'PIX') {
                    $pixCurrency = $currency;
                    break;
                }
            }

            if (!$pixCurrency) {
                throw new \Exception('Moeda PIX não encontrada na conta XGate');
            }

            $depositData['currency'] = $pixCurrency;

            // Fazer requisição de depósito
            $response = $this->makeRequest('POST', '/deposit', $depositData);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::channel('xgate')->info("CHAMADA XGATE COBRANÇA - Tempo para chamar: {$duracaoAtualizacao}s", [
                'reference_id' => $referenceId,
                'valor' => $valor,
                'status' => $response->status()
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Erro ao criar cobrança na API XGate';
                
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();

            // Processar resposta (API retorna data.id = tx id, data.code = PIX copia e cola)
            if (isset($responseData['data'])) {
                $data = $responseData['data'];
                $pixCopiaECola = $data['code'] ?? null;
                return [
                    'success' => true,
                    'transaction_id' => $data['id'] ?? $referenceId,
                    'code' => $data['code'] ?? null,
                    'pixCopiaECola' => $pixCopiaECola,
                    'qr_code' => $pixCopiaECola,
                    'status' => $data['status'] ?? 'PENDING',
                    'customerId' => $data['customerId'] ?? null,
                    'message' => $responseData['message'] ?? null,
                    'response' => $responseData
                ];
            }

            Log::channel('xgate')->error('Resposta XGate inválida', ['response' => $responseData]);
            return [
                'success' => false,
                'error' => 'Resposta inválida da API XGate',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            Log::channel('xgate')->error('Erro ao criar cobrança XGate: ' . $e->getMessage(), array_merge($errorDetails, [
                'last_response' => $this->lastResponse
            ]));
            return [
                'success' => false,
                'error' => $e->getMessage() ?: 'Erro no servidor, tente novamente',
                'error_details' => $errorDetails,
                'last_response' => $this->lastResponse
            ];
        }
    }

    /**
     * Cria ou obtém cliente no XGate
     *
     * @param array $customerData Dados do cliente
     * @return string ID do cliente
     */
    protected function criarOuObterCliente(array $customerData): string
    {
        try {
            // Garantir que temos pelo menos name e document (obrigatórios)
            if (empty($customerData['name']) || empty($customerData['document'])) {
                throw new \Exception('Nome e documento são obrigatórios para criar cliente');
            }

            // Preparar payload com apenas os campos necessários
            $payload = [
                'name' => $customerData['name'],
                'document' => $customerData['document']
            ];

            // Adicionar email apenas se presente
            if (isset($customerData['email']) && !empty($customerData['email'])) {
                $payload['email'] = $customerData['email'];
            }

            // Adicionar phone apenas se presente
            if (isset($customerData['phone']) && !empty($customerData['phone'])) {
                $payload['phone'] = $customerData['phone'];
            }

            // Tentar criar cliente
            $response = $this->makeRequest('POST', '/customer', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['customer']['_id'])) {
                    return $data['customer']['_id'];
                }
            }

            // Se falhar, pode ser que o cliente já exista
            // Por enquanto, vamos lançar o erro
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Erro ao criar cliente na API XGate';
            
            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao criar cliente XGate: ' . $e->getMessage(), [
                'customer_data' => $customerData,
                'last_response' => $this->lastResponse
            ]);
            throw $e;
        }
    }

    /**
     * Obtém lista de moedas disponíveis para depósito
     *
     * @return array
     */
    protected function getCurrenciesDeposit(): array
    {
        $response = $this->makeRequest('GET', '/deposit/company/currencies');
        
        if (!$response->successful()) {
            throw new \Exception('Erro ao buscar moedas de depósito');
        }

        return $response->json();
    }

    /**
     * Cria cobrança PIX para depósito no Fechamento de Caixa (sem cliente específico).
     * Gera um PIX para a empresa depositar valor na carteira XGate.
     *
     * @param float $valor Valor do depósito em reais
     * @param string $referenceId Identificador único (ex: dep-caixa-{banco_id}-{timestamp})
     * @return array ['success' => bool, 'pixCopiaECola' => string, 'transaction_id' => string, ...]
     */
    public function criarDepositoCaixa(float $valor, string $referenceId): array
    {
        try {
            $doc = preg_replace('/\D/', '', 'DEP-CAIXA-' . $referenceId);
            $customerData = [
                'name' => 'Depósito Fechamento Caixa',
                'document' => str_pad(substr($doc, -11), 11, '0', STR_PAD_LEFT),
            ];

            $customerId = $this->criarOuObterCliente($customerData);

            $depositData = [
                'amount' => $valor,
                'customerId' => $customerId,
            ];

            $currencies = $this->getCurrenciesDeposit();
            $pixCurrency = null;
            foreach ($currencies as $currency) {
                if (isset($currency['type']) && $currency['type'] === 'PIX') {
                    $pixCurrency = $currency;
                    break;
                }
            }

            if (!$pixCurrency) {
                throw new \Exception('Moeda PIX não encontrada na conta XGate');
            }

            $depositData['currency'] = $pixCurrency;

            $response = $this->makeRequest('POST', '/deposit', $depositData);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Erro ao criar depósito na API XGate';
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();
            if (isset($responseData['data'])) {
                $data = $responseData['data'];
                $pixCopiaECola = $data['code'] ?? null;
                $txId = $data['id'] ?? $referenceId;

                Log::channel('xgate')->info('XGate depósito fechamento caixa criado', [
                    'reference_id' => $referenceId,
                    'valor' => $valor,
                    'transaction_id' => $txId,
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $txId,
                    'pixCopiaECola' => $pixCopiaECola,
                    'code' => $pixCopiaECola,
                    'status' => $data['status'] ?? 'PENDING',
                ];
            }

            return [
                'success' => false,
                'error' => 'Resposta inválida da API XGate',
                'response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao criar depósito caixa XGate: ' . $e->getMessage(), [
                'last_response' => $this->lastResponse,
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'last_response' => $this->lastResponse,
            ];
        }
    }

    /**
     * Realiza uma transferência PIX para o cliente
     *
     * @param float $valor Valor da transferência em reais
     * @param string $pixKey Chave PIX do destinatário
     * @param string $description Descrição da transferência
     * @return array
     */
    public function realizarTransferenciaPix(
        float $valor,
        string $pixKey,
        string $description = 'Transferência PIX'
    ) {
        try {
            $inicioAtualizacao = microtime(true);

            // Determinar tipo de chave PIX
            $pixKeyType = $this->determinarTipoChavePix($pixKey);

            // Criar cliente temporário com a chave PIX
            $customerData = [
                'name' => 'Cliente Transferência',
                'document' => $pixKey // Usar a chave PIX como documento temporário
            ];

            $customerId = $this->criarOuObterCliente($customerData);

            // Criar chave PIX para o cliente
            $pixKeyData = [
                'key' => $pixKey,
                'type' => $pixKeyType
            ];

            $pixResponse = $this->makeRequest('POST', "/pix/customer/{$customerId}/key", $pixKeyData);
            
            if (!$pixResponse->successful()) {
                // Se a chave já existe, continuar
                $pixData = $pixResponse->json();
                if (!isset($pixData['key'])) {
                    throw new \Exception('Erro ao criar chave PIX: ' . ($pixData['message'] ?? 'Erro desconhecido'));
                }
            }

            // Obter currency (PIX) para saque
            $currencies = $this->getCurrenciesWithdraw();
            $pixCurrency = null;
            foreach ($currencies as $currency) {
                if (isset($currency['type']) && $currency['type'] === 'PIX') {
                    $pixCurrency = $currency;
                    break;
                }
            }

            if (!$pixCurrency) {
                throw new \Exception('Moeda PIX não encontrada para saque na conta XGate');
            }

            // Obter chaves PIX do cliente
            $pixKeysResponse = $this->makeRequest('GET', "/pix/customer/{$customerId}/key");
            $pixKeys = $pixKeysResponse->json();
            
            $pixKeyToUse = null;
            foreach ($pixKeys as $key) {
                if ($key['key'] === $pixKey) {
                    $pixKeyToUse = $key;
                    break;
                }
            }

            if (!$pixKeyToUse) {
                throw new \Exception('Chave PIX não encontrada para o cliente');
            }

            // Preparar dados do saque
            $withdrawData = [
                'amount' => $valor,
                'customerId' => $customerId,
                'currency' => $pixCurrency,
                'pixKey' => $pixKeyToUse
            ];

            // Fazer requisição de saque
            $response = $this->makeRequest('POST', '/withdraw', $withdrawData);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::channel('xgate')->info("CHAMADA XGATE TRANSFERÊNCIA PIX - Tempo para chamar: {$duracaoAtualizacao}s", [
                'valor' => $valor,
                'pix_key' => $pixKey
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Erro ao realizar transferência na API XGate';
                
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();

            return [
                'success' => true,
                'transaction_id' => $responseData['_id'] ?? null,
                'status' => $responseData['status'] ?? 'PENDING',
                'message' => $responseData['message'] ?? 'Transferência iniciada',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao realizar transferência PIX XGate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'last_response' => $this->lastResponse
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'last_response' => $this->lastResponse
            ];
        }
    }

    /**
     * Realiza transferência PIX com cliente completo
     *
     * @param float $valor Valor da transferência
     * @param \App\Models\Client $cliente Cliente destinatário
     * @param string $description Descrição da transferência
     * @return array
     */
    public function realizarTransferenciaPixComCliente(
        float $valor,
        $cliente,
        string $description = 'Transferência PIX'
    ) {
        try {
            if (!$cliente->pix_cliente) {
                throw new \Exception('Cliente não possui chave PIX cadastrada');
            }

            $pixKey = $cliente->pix_cliente;
            $pixKeyType = $this->determinarTipoChavePix($pixKey);

            // Preparar dados do cliente
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $customerData = [
                'name' => $cliente->nome_completo,
                'document' => $document
            ];

            // Adicionar email apenas se disponível e não vazio
            if (!empty($cliente->email)) {
                $customerData['email'] = $cliente->email;
            }

            // Adicionar telefone apenas se disponível e válido
            if (!empty($cliente->telefone_celular_1)) {
                $phoneObj = $this->criarObjetoPhone($cliente->telefone_celular_1);
                if ($phoneObj) {
                    $customerData['phone'] = $phoneObj;
                }
            }

            $customerId = $this->criarOuObterCliente($customerData);

            // Criar chave PIX
            $pixKeyData = [
                'key' => $pixKey,
                'type' => $pixKeyType
            ];

            $pixResponse = $this->makeRequest('POST', "/pix/customer/{$customerId}/key", $pixKeyData);
            
            // Se a chave já existe, continuar
            if (!$pixResponse->successful()) {
                $pixData = $pixResponse->json();
                if (!isset($pixData['key'])) {
                    // Tentar buscar chaves existentes
                    $pixKeysResponse = $this->makeRequest('GET', "/pix/customer/{$customerId}/key");
                    if ($pixKeysResponse->successful()) {
                        $pixKeys = $pixKeysResponse->json();
                        foreach ($pixKeys as $key) {
                            if ($key['key'] === $pixKey) {
                                // Chave já existe, continuar
                                break;
                            }
                        }
                    }
                }
            }

            // Obter currency (PIX) para saque
            $currencies = $this->getCurrenciesWithdraw();
            $pixCurrency = null;
            foreach ($currencies as $currency) {
                if (isset($currency['type']) && $currency['type'] === 'PIX') {
                    $pixCurrency = $currency;
                    break;
                }
            }

            if (!$pixCurrency) {
                throw new \Exception('Moeda PIX não encontrada para saque na conta XGate');
            }

            // Obter chaves PIX do cliente
            $pixKeysResponse = $this->makeRequest('GET', "/pix/customer/{$customerId}/key");
            $pixKeys = $pixKeysResponse->json();
            
            $pixKeyToUse = null;
            foreach ($pixKeys as $key) {
                if ($key['key'] === $pixKey) {
                    $pixKeyToUse = $key;
                    break;
                }
            }

            if (!$pixKeyToUse) {
                throw new \Exception('Chave PIX não encontrada para o cliente');
            }

            // Preparar dados do saque
            $withdrawData = [
                'amount' => $valor,
                'customerId' => $customerId,
                'currency' => $pixCurrency,
                'pixKey' => $pixKeyToUse
            ];

            $inicioAtualizacao = microtime(true);

            $response = $this->makeRequest('POST', '/withdraw', $withdrawData);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::channel('xgate')->info("CHAMADA XGATE TRANSFERÊNCIA PIX COM CLIENTE - Tempo para chamar: {$duracaoAtualizacao}s", [
                'valor' => $valor,
                'cliente_id' => $cliente->id
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Erro ao realizar transferência na API XGate';
                
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();

            return [
                'success' => true,
                'transaction_id' => $responseData['_id'] ?? null,
                'status' => $responseData['status'] ?? 'PENDING',
                'message' => $responseData['message'] ?? 'Transferência iniciada',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao realizar transferência PIX XGate com cliente: ' . $e->getMessage(), [
                'last_response' => $this->lastResponse
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'last_response' => $this->lastResponse
            ];
        }
    }

    /**
     * Consulta saldo da conta
     *
     * @return array
     */
    public function consultarSaldo()
    {
        try {
            $inicioAtualizacao = microtime(true);

            $response = $this->makeRequest('POST', '/balance/company', []);

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::channel('xgate')->info("CHAMADA XGATE CONSULTAR SALDO - Tempo para chamar: {$duracaoAtualizacao}s");

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Erro ao consultar saldo na API XGate';
                
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();

            return [
                'success' => true,
                'saldo' => is_array($responseData) ? $responseData : [$responseData],
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao consultar saldo XGate: ' . $e->getMessage(), [
                'last_response' => $this->lastResponse
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'last_response' => $this->lastResponse
            ];
        }
    }

    /**
     * Cria ou atualiza cliente no XGate
     *
     * @param \App\Models\Client $cliente Cliente do sistema
     * @return array
     */
    public function criarOuAtualizarCliente($cliente)
    {
        try {
            $document = preg_replace('/\D/', '', $cliente->cpf);
            
            $customerData = [
                'name' => $cliente->nome_completo,
                'document' => $document
            ];

            // Adicionar email apenas se disponível e não vazio
            if (!empty($cliente->email)) {
                $customerData['email'] = $cliente->email;
            }

            // Adicionar telefone apenas se disponível e válido
            if (!empty($cliente->telefone_celular_1)) {
                $phoneObj = $this->criarObjetoPhone($cliente->telefone_celular_1);
                if ($phoneObj) {
                    $customerData['phone'] = $phoneObj;
                }
            }

            $customerId = $this->criarOuObterCliente($customerData);

            return [
                'success' => true,
                'customer_id' => $customerId,
                'message' => 'Cliente criado/atualizado com sucesso',
                'response' => ['customer' => ['_id' => $customerId]]
            ];

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao criar/atualizar cliente XGate: ' . $e->getMessage(), [
                'last_response' => $this->lastResponse
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'last_response' => $this->lastResponse
            ];
        }
    }

    /**
     * Obtém lista de moedas disponíveis para saque
     *
     * @return array
     */
    protected function getCurrenciesWithdraw(): array
    {
        $response = $this->makeRequest('GET', '/withdraw/company/currencies');
        
        if (!$response->successful()) {
            throw new \Exception('Erro ao buscar moedas de saque');
        }

        return $response->json();
    }

    /**
     * Determina o tipo de chave PIX baseado no formato
     *
     * @param string $pixKey Chave PIX
     * @return string Tipo da chave (PHONE, CPF, CNPJ, EMAIL, RANDOM)
     */
    protected function determinarTipoChavePix(string $pixKey): string
    {
        // Verificar email primeiro (antes de remover caracteres)
        if (strpos($pixKey, '@') !== false) {
            return 'EMAIL';
        }

        // Remover caracteres especiais para análise numérica
        $pixKeyClean = preg_replace('/\D/', '', $pixKey);

        // CPF (11 dígitos)
        if (strlen($pixKeyClean) === 11) {
            return 'CPF';
        }

        // CNPJ (14 dígitos)
        if (strlen($pixKeyClean) === 14) {
            return 'CNPJ';
        }

        // Telefone (10 ou 11 dígitos)
        if (strlen($pixKeyClean) === 10 || strlen($pixKeyClean) === 11) {
            return 'PHONE';
        }

        // Chave aleatória (UUID - 32 ou 36 caracteres com hífens)
        $pixKeyOriginal = $pixKey;
        if (strlen($pixKeyOriginal) === 32 || strlen($pixKeyOriginal) === 36) {
            return 'RANDOM';
        }

        // Padrão: CPF
        return 'CPF';
    }

    /**
     * Cria um objeto Phone do XGate a partir de um número de telefone brasileiro
     *
     * @param string $telefone Número de telefone (pode conter formatação)
     * @return array|null Array com dados do telefone ou null se não for possível criar
     */
    protected function criarObjetoPhone(string $telefone): ?array
    {
        try {
            // Remover todos os caracteres não numéricos
            $telefoneLimpo = preg_replace('/\D/', '', $telefone);
            
            // Validar tamanho mínimo (10 dígitos para telefone brasileiro)
            if (strlen($telefoneLimpo) < 10) {
                return null;
            }

            // Extrair código de área (DDD) e número
            $codigoArea = '';
            $numero = '';
            
            if (strlen($telefoneLimpo) == 10) {
                // Telefone fixo: (XX) XXXX-XXXX
                $codigoArea = substr($telefoneLimpo, 0, 2);
                $numero = substr($telefoneLimpo, 2);
            } elseif (strlen($telefoneLimpo) == 11) {
                // Celular: (XX) 9XXXX-XXXX
                $codigoArea = substr($telefoneLimpo, 0, 2);
                $numero = substr($telefoneLimpo, 2);
            } else {
                // Formato inválido
                return null;
            }

            // Retornar array conforme esperado pela API
            return [
                'type' => 'mobile',
                'number' => $numero,
                'areaCode' => $codigoArea,
                'countryCode' => '55'
            ];
        } catch (\Exception $e) {
            Log::channel('xgate')->warning('Erro ao criar objeto Phone do XGate: ' . $e->getMessage(), [
                'telefone' => $telefone
            ]);
            return null;
        }
    }

    /**
     * Loga os detalhes da requisição (sem curl)
     *
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $endpoint Endpoint da API
     * @param array $data Dados da requisição
     * @param string $description Descrição da operação
     * @return void
     */
    protected function logRequestDetails(string $method, string $endpoint, array $data, string $description = '')
    {
        $url = $this->baseUrl . $endpoint;
        Log::channel('xgate')->info("XGATE REQUEST - {$description}", [
            'method' => $method,
            'url' => $url,
            'data' => $data,
        ]);
    }

    /**
     * Retorna a última resposta da API (status + body) para uso em erros/debug
     *
     * @return array|null
     */
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }
}
