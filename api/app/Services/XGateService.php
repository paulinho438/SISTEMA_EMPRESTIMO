<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Models\Banco;
use Exception;

// O pacote XGate deve ser autoloaded pelo Composer
// Se não estiver funcionando, vamos incluir manualmente o index.php
// mas precisamos garantir que o vendor/autoload.php seja encontrado
if (!class_exists('XGate\Integration\XGate')) {
    $xgateIndexPath = base_path('vendor/xgate/xgate-integration/src/index.php');
    if (file_exists($xgateIndexPath)) {
        // Definir o caminho correto do vendor/autoload.php antes de incluir
        // O index.php do pacote pode estar tentando incluir vendor/autoload.php
        // Vamos definir uma constante ou variável de ambiente para ajudar
        $vendorAutoloadPath = base_path('vendor/autoload.php');
        
        // Salvar o diretório atual
        $originalDir = getcwd();
        // Mudar para o diretório raiz do projeto Laravel
        chdir(base_path());
        
        // Incluir o arquivo (agora os caminhos relativos devem funcionar)
        require_once $xgateIndexPath;
        
        // Restaurar o diretório original
        chdir($originalDir);
    } else {
        // Tentar caminho alternativo
        $xgateIndexPath = __DIR__ . '/../../vendor/xgate/xgate-integration/src/index.php';
        if (file_exists($xgateIndexPath)) {
            $originalDir = getcwd();
            chdir(base_path());
            require_once $xgateIndexPath;
            chdir($originalDir);
        } else {
            throw new \Exception('Pacote XGate não encontrado. Execute: composer dump-autoload');
        }
    }
}

// Classes do pacote XGate
use XGate\Integration\XGate;
use XGate\Integration\Account;
use XGate\Integration\Customer;
use XGate\Integration\MethodCurrency;
use XGate\Integration\PixKeyParam;
use XGate\Integration\PixKeyParamType;

class XGateService
{
    protected $xgate;
    protected $banco;

    public function __construct(?Banco $banco = null)
    {
        $this->banco = $banco;

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

                $account = new Account($banco->xgate_email, $password);
                $this->xgate = new XGate($account);
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
            $customer = new Customer(
                $cliente->nome_completo,
                $document
            );

            // Adicionar email se disponível
            if ($cliente->email) {
                $customer->email = $cliente->email;
            }

            // Adicionar telefone se disponível
            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $customer->phone = $phone;
                }
            }

            // Criar depósito PIX
            $response = $this->xgate->depositFiat(
                $valor,
                $customer,
                MethodCurrency::PIX
            );

            $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
            Log::info("CHAMADA XGATE COBRANÇA - Tempo para chamar: {$duracaoAtualizacao}s", [
                'reference_id' => $referenceId,
                'valor' => $valor
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
            Log::error('Erro ao criar cobrança XGate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
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
            
            $pixKeyParam = new PixKeyParam($pixKey, $pixKeyType);

            // Para transferência, precisamos criar ou buscar um cliente
            // Como não temos o cliente completo, vamos usar um cliente temporário
            // Na prática, você pode querer criar o cliente primeiro ou usar um ID existente
            $customer = new Customer('Cliente Transferência', $pixKey); // Nome temporário

            // Realizar saque (transferência) PIX
            $response = $this->xgate->withdrawFiat(
                $valor,
                $customer,
                MethodCurrency::PIX,
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
            $pixKeyParam = new PixKeyParam($pixKey, $pixKeyType);

            // Criar objeto Customer do XGate
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $customer = new Customer($cliente->nome_completo, $document);

            if ($cliente->email) {
                $customer->email = $cliente->email;
            }

            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $customer->phone = $phone;
                }
            }

            $inicioAtualizacao = microtime(true);

            $response = $this->xgate->withdrawFiat(
                $valor,
                $customer,
                MethodCurrency::PIX,
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
            
            $customer = new Customer($cliente->nome_completo, $document);

            if ($cliente->email) {
                $customer->email = $cliente->email;
            }

            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $customer->phone = $phone;
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
    protected function determinarTipoChavePix(string $pixKey): PixKeyParamType
    {
        // Verificar email primeiro (antes de remover caracteres)
        if (strpos($pixKey, '@') !== false) {
            return PixKeyParamType::EMAIL;
        }

        // Remover caracteres especiais para análise numérica
        $pixKeyClean = preg_replace('/\D/', '', $pixKey);

        // CPF (11 dígitos)
        if (strlen($pixKeyClean) === 11) {
            return PixKeyParamType::CPF;
        }

        // CNPJ (14 dígitos)
        if (strlen($pixKeyClean) === 14) {
            return PixKeyParamType::CNPJ;
        }

        // Telefone (10 ou 11 dígitos)
        if (strlen($pixKeyClean) === 10 || strlen($pixKeyClean) === 11) {
            return PixKeyParamType::PHONE;
        }

        // Chave aleatória (UUID - 32 ou 36 caracteres com hífens)
        $pixKeyOriginal = $pixKey;
        if (strlen($pixKeyOriginal) === 32 || strlen($pixKeyOriginal) === 36) {
            return PixKeyParamType::RANDOM;
        }

        // Padrão: CPF
        return PixKeyParamType::CPF;
    }
}
