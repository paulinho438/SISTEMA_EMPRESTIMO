<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Models\Banco;
use Exception;

class XGateService
{
    protected $xgate;
    protected $banco;
    protected $lastCurlCommand = null;

    public function __construct(?Banco $banco = null)
    {
        $this->banco = $banco;

        // Garantir que as classes do XGate estejam carregadas
        $this->ensureXGateLoaded();

        if ($banco && $banco->xgate_email && $banco->xgate_password) {
            try {
                // Descriptografar senha se necessário
                $password = $banco->xgate_password;
                try {
                    $password = Crypt::decryptString($banco->xgate_password);
                } catch (\Exception $e) {
                    // Se não conseguir descriptografar, usar o valor direto (pode já estar descriptografado)
                    $password = $banco->xgate_password;
                }

                $account = new \Account($banco->xgate_email, $password);
                $this->xgate = new \XGate($account);
            } catch (\Exception $e) {
                Log::error('Erro ao inicializar XGate: ' . $e->getMessage());
                throw new \Exception('Erro ao inicializar XGate: ' . $e->getMessage());
            }
        } else {
            throw new \Exception('Credenciais XGate não configuradas');
        }
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
            
            // Criar objeto Customer do XGate
            $customer = new \Customer(
                $cliente->nome_completo,
                $document
            );

            // Adicionar email se disponível
            if ($cliente->email) {
                $customer->email = $cliente->email;
            }

            // Adicionar telefone se disponível
            if ($cliente->telefone_celular_1) {
                $phoneObj = $this->criarObjetoPhone($cliente->telefone_celular_1);
                if ($phoneObj) {
                    $customer->phone = $phoneObj;
                }
            }

            // Preparar dados para logging
            $requestData = [
                'amount' => $valor,
                'customer' => [
                    'name' => $customer->name ?? null,
                    'document' => $customer->document ?? null,
                    'email' => $customer->email ?? null,
                    'phone' => $customer->phone ? [
                        'type' => $customer->phone->type->value ?? null,
                        'number' => $customer->phone->number ?? null,
                        'areaCode' => $customer->phone->areaCode ?? null,
                        'countryCode' => $customer->phone->countryCode ?? null,
                    ] : null,
                ],
                'methodCurrency' => \MethodCurrency::PIX->value ?? 'PIX'
            ];

            // Log da requisição antes de enviar
            $this->logRequestDetails('POST', '/deposit', $requestData, 'Criar Cobrança PIX');

            // Tentar criar o cliente primeiro para ter mais controle sobre erros
            // Se o cliente já existir, o XGate retornará o ID existente
            $customerId = null;
            try {
                $customerCreateResponse = $this->xgate->customerCreate($customer);
                if ($customerCreateResponse && isset($customerCreateResponse->customer) && isset($customerCreateResponse->customer->_id)) {
                    $customerId = $customerCreateResponse->customer->_id;
                    Log::info('Cliente XGate criado/encontrado', ['customer_id' => $customerId]);
                }
            } catch (\Exception $customerError) {
                // Se falhar ao criar cliente, tentar usar o depósito direto mesmo assim
                // O XGate pode criar automaticamente
                Log::warning('Erro ao criar cliente XGate, tentando depósito direto: ' . $customerError->getMessage());
            }

            // Criar depósito PIX
            // Se temos o customerId, podemos passar como string ao invés do objeto
            if ($customerId) {
                $response = $this->xgate->depositFiat(
                    $valor,
                    $customerId, // Passar ID do cliente ao invés do objeto
                    \MethodCurrency::PIX
                );
            } else {
                // Usar objeto Customer (XGate criará automaticamente)
                $response = $this->xgate->depositFiat(
                    $valor,
                    $customer,
                    \MethodCurrency::PIX
                );
            }

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA XGATE COBRANÇA - Tempo para chamar: {$duracaoAtualizacao}s", [
                'reference_id' => $referenceId,
                'valor' => $valor,
                'response' => $response
            ]);

            // Processar resposta - depositFiat retorna um objeto Deposit
            if ($response && isset($response->data)) {
                $data = $response->data;
                
                // Retornar no formato esperado pelo sistema
                return [
                    'success' => true,
                    'transaction_id' => $data->id ?? $data->code ?? $referenceId,
                    'code' => $data->code ?? null,
                    'pixCopiaECola' => null, // XGate não retorna PIX copia e cola diretamente no depósito
                    'qr_code' => null,
                    'status' => $data->status ?? 'PENDING',
                    'customerId' => $data->customerId ?? null,
                    'message' => $response->message ?? null,
                    'response' => $response
                ];
            }

            Log::error('Resposta XGate inválida', ['response' => $response]);
            return [
                'success' => false,
                'error' => 'Resposta inválida da API XGate',
                'response' => $response
            ];

        } catch (\Exception $e) {
            // Extrair mensagem real do erro
            $errorMessage = $e->getMessage();
            
            // Se for XGateError, tentar extrair mais informações
            if (class_exists('XGateError') && $e instanceof \XGateError) {
                // XGateError pode ter propriedades message, status, originalError
                if (isset($e->message) && $e->message !== 'Erro no servidor, tente novamente') {
                    $errorMessage = $e->message;
                }
                
                if (isset($e->originalError)) {
                    $originalError = $e->originalError;
                    if (is_object($originalError)) {
                        // Tentar extrair mensagem do erro original
                        if (isset($originalError->message)) {
                            $errorMessage = $originalError->message;
                        } elseif (is_string($originalError)) {
                            $errorMessage = $originalError;
                        }
                    } elseif (is_string($originalError)) {
                        $errorMessage = $originalError;
                    }
                }
                
                // Se tiver status HTTP, incluir na mensagem
                if (isset($e->status)) {
                    $errorMessage .= " (HTTP {$e->status})";
                }
            }
            
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'error_class' => get_class($e)
            ];
            
            // Adicionar propriedades do erro se disponíveis
            if (is_object($e)) {
                try {
                    $reflection = new \ReflectionClass($e);
                    foreach ($reflection->getProperties() as $property) {
                        $property->setAccessible(true);
                        $value = $property->getValue($e);
                        if ($value !== null) {
                            $errorDetails['error_' . $property->getName()] = $value;
                        }
                    }
                } catch (\Exception $reflectionError) {
                    // Ignorar erros de reflexão
                }
            }
            
            $curlCommand = $this->getLastCurlCommand();
            
            Log::error('Erro ao criar cobrança XGate: ' . $errorMessage, array_merge($errorDetails, [
                'curl_command' => $curlCommand,
                'customer_data' => [
                    'name' => $customer->name ?? null,
                    'document' => $customer->document ?? null,
                    'email' => $customer->email ?? null,
                    'phone' => $customer->phone ? [
                        'type' => $customer->phone->type->value ?? null,
                        'number' => $customer->phone->number ?? null,
                        'areaCode' => $customer->phone->areaCode ?? null,
                        'countryCode' => $customer->phone->countryCode ?? null,
                    ] : null,
                ]
            ]));
            
            // Adicionar curl_command à exceção para ser capturado no controller
            $e->curl_command = $curlCommand;
            
            return [
                'success' => false,
                'error' => $errorMessage ?: 'Erro no servidor, tente novamente',
                'error_details' => $errorDetails,
                'curl_command' => $curlCommand
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
            
            $pixKeyParam = new \PixKeyParam($pixKey, $pixKeyType);
            
            // Para transferência, precisamos criar ou buscar um cliente
            // Como não temos o cliente completo, vamos usar um cliente temporário
            // Na prática, você pode querer criar o cliente primeiro ou usar um ID existente
            $customer = new \Customer('Cliente Transferência', $pixKey); // Nome temporário

            // Realizar saque (transferência) PIX
            $response = $this->xgate->withdrawFiat(
                $valor,
                $customer,
                \MethodCurrency::PIX,
                $pixKeyParam
            );

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA XGATE TRANSFERÊNCIA PIX - Tempo para chamar: {$duracaoAtualizacao}s", [
                'valor' => $valor,
                'pix_key' => $pixKey
            ]);

            // Processar resposta - withdrawFiat retorna um objeto Withdraw
            if ($response && (isset($response->_id) || isset($response->status))) {
                return [
                    'success' => true,
                    'transaction_id' => $response->_id ?? null,
                    'status' => $response->status ?? 'PENDING',
                    'message' => $response->message ?? 'Transferência iniciada',
                    'response' => $response
                ];
            }

            Log::error('Resposta XGate inválida na transferência', ['response' => $response]);
            return [
                'success' => false,
                'error' => 'Resposta inválida da API XGate',
                'response' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao realizar transferência PIX XGate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
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
            $pixKeyParam = new \PixKeyParam($pixKey, $pixKeyType);

            // Criar objeto Customer do XGate
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $customer = new \Customer($cliente->nome_completo, $document);

            if ($cliente->email) {
                $customer->email = $cliente->email;
            }

            if ($cliente->telefone_celular_1) {
                $phoneObj = $this->criarObjetoPhone($cliente->telefone_celular_1);
                if ($phoneObj) {
                    $customer->phone = $phoneObj;
                }
            }

            $inicioAtualizacao = microtime(true);

            $response = $this->xgate->withdrawFiat(
                $valor,
                $customer,
                \MethodCurrency::PIX,
                $pixKeyParam
            );

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA XGATE TRANSFERÊNCIA PIX COM CLIENTE - Tempo para chamar: {$duracaoAtualizacao}s", [
                'valor' => $valor,
                'cliente_id' => $cliente->id
            ]);

            // Processar resposta - withdrawFiat retorna um objeto Withdraw
            if ($response && (isset($response->_id) || isset($response->status))) {
                return [
                    'success' => true,
                    'transaction_id' => $response->_id ?? null,
                    'status' => $response->status ?? 'PENDING',
                    'message' => $response->message ?? 'Transferência iniciada',
                    'response' => $response
                ];
            }

            return [
                'success' => false,
                'error' => 'Resposta inválida da API XGate',
                'response' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao realizar transferência PIX XGate com cliente: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
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

            $response = $this->xgate->getBalance();

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA XGATE CONSULTAR SALDO - Tempo para chamar: {$duracaoAtualizacao}s");

            // getBalance retorna um array de saldos
            return [
                'success' => true,
                'saldo' => is_array($response) ? $response : [$response],
                'response' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao consultar saldo XGate: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
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
            
            $customer = new \Customer($cliente->nome_completo, $document);

            if ($cliente->email) {
                $customer->email = $cliente->email;
            }

            if ($cliente->telefone_celular_1) {
                $phoneObj = $this->criarObjetoPhone($cliente->telefone_celular_1);
                if ($phoneObj) {
                    $customer->phone = $phoneObj;
                }
            }

            // Tentar criar cliente - customerCreate retorna um objeto CreateCustomer
            $response = $this->xgate->customerCreate($customer);

            if ($response && isset($response->customer) && isset($response->customer->_id)) {
                return [
                    'success' => true,
                    'customer_id' => $response->customer->_id,
                    'message' => $response->message ?? null,
                    'response' => $response
                ];
            }

            return [
                'success' => false,
                'error' => 'Erro ao criar cliente',
                'response' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao criar/atualizar cliente XGate: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Determina o tipo de chave PIX baseado no formato
     *
     * @param string $pixKey Chave PIX
     * @return PixKeyParamType Tipo da chave
     */
    protected function determinarTipoChavePix(string $pixKey): \PixKeyParamType
    {
        // Verificar email primeiro (antes de remover caracteres)
        if (strpos($pixKey, '@') !== false) {
            return \PixKeyParamType::EMAIL;
        }

        // Remover caracteres especiais para análise numérica
        $pixKeyClean = preg_replace('/\D/', '', $pixKey);

        // CPF (11 dígitos)
        if (strlen($pixKeyClean) === 11) {
            return \PixKeyParamType::CPF;
        }

        // CNPJ (14 dígitos)
        if (strlen($pixKeyClean) === 14) {
            return \PixKeyParamType::CNPJ;
        }

        // Telefone (10 ou 11 dígitos)
        if (strlen($pixKeyClean) === 10 || strlen($pixKeyClean) === 11) {
            return \PixKeyParamType::PHONE;
        }

        // Chave aleatória (UUID - 32 ou 36 caracteres com hífens)
        $pixKeyOriginal = $pixKey;
        if (strlen($pixKeyOriginal) === 32 || strlen($pixKeyOriginal) === 36) {
            return \PixKeyParamType::RANDOM;
        }

        // Padrão: CPF
        return \PixKeyParamType::CPF;
    }

    /**
     * Cria um objeto Phone do XGate a partir de um número de telefone brasileiro
     *
     * @param string $telefone Número de telefone (pode conter formatação)
     * @return \Phone|null Objeto Phone ou null se não for possível criar
     */
    protected function criarObjetoPhone(string $telefone): ?\Phone
    {
        try {
            // Remover todos os caracteres não numéricos
            $telefoneLimpo = preg_replace('/\D/', '', $telefone);
            
            // Validar tamanho mínimo (10 dígitos para telefone brasileiro)
            if (strlen($telefoneLimpo) < 10) {
                return null;
            }

            // Extrair código de área (DDD) e número
            // Formato brasileiro: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
            // Pode ter 10 ou 11 dígitos (com ou sem o 9 inicial)
            
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

            // Criar objeto Phone conforme documentação XGate
            // new Phone(PhoneType::mobile, "900000000", "11", "55")
            return new \Phone(
                \PhoneType::mobile,  // Tipo: mobile (celular)
                $numero,              // Número do telefone (sem DDD)
                $codigoArea,          // Código de área (DDD)
                "55"                  // Código do país (Brasil)
            );
        } catch (\Exception $e) {
            Log::warning('Erro ao criar objeto Phone do XGate: ' . $e->getMessage(), [
                'telefone' => $telefone
            ]);
            return null;
        }
    }

    /**
     * Loga os detalhes da requisição e gera comando curl equivalente
     *
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $endpoint Endpoint da API
     * @param array $data Dados da requisição
     * @param string $description Descrição da operação
     * @return void
     */
    protected function logRequestDetails(string $method, string $endpoint, array $data, string $description = '')
    {
        $baseUrl = 'https://api.xgateglobal.com';
        $url = $baseUrl . $endpoint;
        
        // Obter token de autenticação (se disponível)
        $token = $this->getAuthToken();
        
        // Preparar headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        // Gerar comando curl
        $curlCommand = $this->generateCurlCommand($method, $url, $headers, $data);
        
        // Log detalhado
        Log::info("XGATE REQUEST - {$description}", [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'data' => $data,
            'curl_command' => $curlCommand
        ]);
        
        // Armazenar último curl para retorno em caso de erro
        $this->lastCurlCommand = $curlCommand;
    }

    /**
     * Gera comando curl equivalente
     *
     * @param string $method Método HTTP
     * @param string $url URL completa
     * @param array $headers Headers HTTP
     * @param array $data Dados do body
     * @return string Comando curl
     */
    protected function generateCurlCommand(string $method, string $url, array $headers, array $data): string
    {
        $curl = "curl -X {$method} '{$url}' \\\n";
        
        // Adicionar headers
        foreach ($headers as $header) {
            $curl .= "  -H '{$header}' \\\n";
        }
        
        // Adicionar body se houver dados
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $jsonDataEscaped = addcslashes($jsonData, "'");
            $curl .= "  -d '{$jsonDataEscaped}'";
        }
        
        return $curl;
    }

    /**
     * Tenta obter o token de autenticação do XGate
     * Nota: Isso pode não funcionar se o token estiver privado no objeto XGate
     *
     * @return string|null Token ou null se não disponível
     */
    protected function getAuthToken(): ?string
    {
        try {
            // Tentar acessar o token através de reflexão (se disponível)
            if ($this->xgate && is_object($this->xgate)) {
                $reflection = new \ReflectionClass($this->xgate);
                
                // Tentar acessar propriedade access (onde o token geralmente fica)
                if ($reflection->hasProperty('access')) {
                    $accessProperty = $reflection->getProperty('access');
                    $accessProperty->setAccessible(true);
                    $access = $accessProperty->getValue($this->xgate);
                    
                    if (is_object($access) && isset($access->token)) {
                        return $access->token;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignorar erros de reflexão
        }
        
        return null;
    }

    /**
     * Retorna o último comando curl gerado
     *
     * @return string|null
     */
    protected function getLastCurlCommand(): ?string
    {
        return $this->lastCurlCommand ?? null;
    }


    /**
     * Garante que as classes do XGate estejam carregadas
     *
     * @return void
     */
    protected function ensureXGateLoaded()
    {
        // Se a classe Account já existe, não precisa carregar novamente
        if (class_exists('Account')) {
            return;
        }

        // Tentar carregar o arquivo index.php do XGate
        $xgateIndexPath = base_path('vendor/xgate/xgate-integration/src/index.php');
        
        if (!file_exists($xgateIndexPath)) {
            $xgateIndexPath = __DIR__ . '/../../vendor/xgate/xgate-integration/src/index.php';
        }

        if (file_exists($xgateIndexPath)) {
            // Salvar o diretório atual
            $originalDir = getcwd();
            // Mudar para o diretório raiz do projeto Laravel
            chdir(base_path());
            
            // Incluir o arquivo
            require_once $xgateIndexPath;
            
            // Restaurar o diretório original
            chdir($originalDir);
        } else {
            throw new \Exception('Pacote XGate não encontrado. Execute: composer require xgate/xgate-integration:dev-production && composer dump-autoload');
        }

        // Verificar se as classes foram carregadas
        if (!class_exists('Account')) {
            throw new \Exception('Falha ao carregar classes do pacote XGate. Verifique se o pacote está instalado corretamente.');
        }
    }
}
