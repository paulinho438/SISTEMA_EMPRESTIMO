<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banco;
use App\Models\Client;
use App\Services\VelanaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class VelanaTestController extends Controller
{
    /**
     * Gera comando CURL para requisição à API Velana
     */
    private function gerarCurl($method, $url, $headers, $body = null)
    {
        $curl = "curl -X {$method} \"{$url}\"";
        
        foreach ($headers as $key => $value) {
            $curl .= " \\\n  -H \"{$key}: {$value}\"";
        }
        
        if ($body !== null) {
            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            // Escapar aspas simples e quebras de linha para o shell
            $jsonBody = str_replace(["\n", "\r"], " ", $jsonBody);
            $curl .= " \\\n  -d '" . str_replace("'", "'\"'\"'", $jsonBody) . "'";
        }
        
        return $curl;
    }

    /**
     * Testa criação de checkout
     */
    public function testarCheckout(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'valor' => 'required|numeric|min:0.01',
                'cliente_id' => 'required|exists:clients,id',
                'description' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'velana') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo Velana'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::find($request->cliente_id);
            $valor = $request->valor;
            $referenceId = 'TEST_' . time() . '_' . rand(1000, 9999);
            $description = $request->description ?? 'Teste de Checkout - ' . $cliente->nome_completo;

            // Preparar dados para o CURL
            $valorCentavos = (int)($valor * 100);
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $documentType = strlen($document) === 11 ? 'CPF' : 'CNPJ';
            
            $curlBody = [
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
                'description' => $description
            ];
            
            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $curlBody['customer']['phone'] = [
                        'country_code' => '55',
                        'area_code' => substr($phone, 0, 2),
                        'number' => substr($phone, 2)
                    ];
                }
            }

            // Obter secret key para o header
            $secretKey = null;
            if ($banco->velana_secret_key) {
                try {
                    $secretKey = Crypt::decryptString($banco->velana_secret_key);
                } catch (\Exception $e) {
                    // Ignorar
                }
            }

            $url = 'https://api.velana.com.br/v1/checkouts';
            $credentials = $secretKey . ':x';
            $authHeader = 'Basic ' . base64_encode($credentials);
            
            $curlHeaders = [
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ];

            $velanaService = new VelanaService($banco);
            $response = $velanaService->criarCheckout($valor, $cliente, $referenceId, $description);

            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $e) {
                $responseData = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Checkout criado com sucesso' : 'Erro ao criar checkout',
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                    'valor' => $valor,
                    'cliente' => $cliente->nome_completo,
                    'reference_id' => $referenceId,
                    'description' => $description
                ],
                'curl' => $this->gerarCurl('POST', $url, $curlHeaders, $curlBody),
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $responseData,
                    'successful' => $response->successful()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao testar checkout Velana: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar checkout: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Testa criação de cobrança
     */
    public function testarCobranca(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'valor' => 'required|numeric|min:0.01',
                'cliente_id' => 'required|exists:clients,id',
                'due_date' => 'nullable|date|after:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'velana') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo Velana'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::find($request->cliente_id);
            $valor = $request->valor;
            $referenceId = 'TEST_COB_' . time() . '_' . rand(1000, 9999);
            $dueDate = $request->due_date ?? date('Y-m-d', strtotime('+30 days'));

            // Preparar dados para o CURL
            $valorCentavos = (int)($valor * 100);
            $document = preg_replace('/\D/', '', $cliente->cpf);
            $documentType = strlen($document) === 11 ? 'CPF' : 'CNPJ';
            
            $curlBody = [
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
            
            if ($cliente->telefone_celular_1) {
                $phone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                if (strlen($phone) >= 10) {
                    $curlBody['customer']['phone'] = [
                        'country_code' => '55',
                        'area_code' => substr($phone, 0, 2),
                        'number' => substr($phone, 2)
                    ];
                }
            }

            // Obter secret key para o header
            $secretKey = null;
            if ($banco->velana_secret_key) {
                try {
                    $secretKey = Crypt::decryptString($banco->velana_secret_key);
                } catch (\Exception $e) {
                    // Ignorar
                }
            }

            $url = 'https://api.velana.com.br/v1/transactions';
            $credentials = $secretKey . ':x';
            $authHeader = 'Basic ' . base64_encode($credentials);
            
            $curlHeaders = [
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ];

            $velanaService = new VelanaService($banco);
            $response = $velanaService->criarCobranca($valor, $cliente, $referenceId, $dueDate);

            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $e) {
                $responseData = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Cobrança criada com sucesso' : 'Erro ao criar cobrança',
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                    'valor' => $valor,
                    'cliente' => $cliente->nome_completo,
                    'reference_id' => $referenceId,
                    'due_date' => $dueDate
                ],
                'curl' => $this->gerarCurl('POST', $url, $curlHeaders, $curlBody),
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $responseData,
                    'successful' => $response->successful()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao testar cobrança Velana: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar cobrança: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Testa transferência PIX
     */
    public function testarTransferencia(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'valor' => 'required|numeric|min:0.01',
                'pix_key' => 'required|string',
                'description' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'velana') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo Velana'
                ], Response::HTTP_BAD_REQUEST);
            }

            $valor = $request->valor;
            $pixKey = $request->pix_key;
            $description = $request->description ?? 'Teste de Transferência PIX';

            // Preparar dados para o CURL
            $valorCentavos = (int)($valor * 100);
            $curlBody = [
                'amount' => $valorCentavos,
                'payment_method' => 'PIX',
                'pix_key' => $pixKey,
                'description' => $description
            ];

            // Obter secret key para o header
            $secretKey = null;
            if ($banco->velana_secret_key) {
                try {
                    $secretKey = Crypt::decryptString($banco->velana_secret_key);
                } catch (\Exception $e) {
                    // Ignorar
                }
            }

            $url = 'https://api.velana.com.br/v1/transfers';
            $credentials = $secretKey . ':x';
            $authHeader = 'Basic ' . base64_encode($credentials);
            
            $curlHeaders = [
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ];

            $velanaService = new VelanaService($banco);
            $response = $velanaService->realizarTransferenciaPix($valor, $pixKey, $description);

            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $e) {
                $responseData = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Transferência realizada com sucesso' : 'Erro ao realizar transferência',
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                    'valor' => $valor,
                    'pix_key' => $pixKey,
                    'description' => $description
                ],
                'curl' => $this->gerarCurl('POST', $url, $curlHeaders, $curlBody),
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $responseData,
                    'successful' => $response->successful()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao testar transferência Velana: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar transferência: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Busca detalhes de um checkout
     */
    public function buscarCheckout(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'checkout_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'velana') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo Velana'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Preparar dados para o CURL
            $secretKey = null;
            if ($banco->velana_secret_key) {
                try {
                    $secretKey = Crypt::decryptString($banco->velana_secret_key);
                } catch (\Exception $e) {
                    // Ignorar
                }
            }

            $url = 'https://api.velana.com.br/v1/checkouts/' . $request->checkout_id;
            $credentials = $secretKey . ':x';
            $authHeader = 'Basic ' . base64_encode($credentials);
            
            $curlHeaders = [
                'Authorization' => $authHeader,
                'accept' => 'application/json'
            ];

            $velanaService = new VelanaService($banco);
            $response = $velanaService->buscarCheckout($request->checkout_id);

            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $e) {
                $responseData = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Checkout encontrado' : 'Erro ao buscar checkout',
                'request' => [
                    'banco_id' => $banco->id,
                    'checkout_id' => $request->checkout_id
                ],
                'curl' => $this->gerarCurl('GET', $url, $curlHeaders),
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $responseData,
                    'successful' => $response->successful()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar checkout Velana: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar checkout: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Busca detalhes de uma transação
     */
    public function buscarTransacao(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'transaction_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'velana') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo Velana'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Preparar dados para o CURL
            $secretKey = null;
            if ($banco->velana_secret_key) {
                try {
                    $secretKey = Crypt::decryptString($banco->velana_secret_key);
                } catch (\Exception $e) {
                    // Ignorar
                }
            }

            $url = 'https://api.velana.com.br/v1/transactions/' . $request->transaction_id;
            $credentials = $secretKey . ':x';
            $authHeader = 'Basic ' . base64_encode($credentials);
            
            $curlHeaders = [
                'Authorization' => $authHeader,
                'accept' => 'application/json'
            ];

            $velanaService = new VelanaService($banco);
            $response = $velanaService->buscarTransacao($request->transaction_id);

            $responseData = null;
            try {
                $responseData = $response->json();
            } catch (\Exception $e) {
                $responseData = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Transação encontrada' : 'Erro ao buscar transação',
                'request' => [
                    'banco_id' => $banco->id,
                    'transaction_id' => $request->transaction_id
                ],
                'curl' => $this->gerarCurl('GET', $url, $curlHeaders),
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $responseData,
                    'successful' => $response->successful()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar transação Velana: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar transação: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

