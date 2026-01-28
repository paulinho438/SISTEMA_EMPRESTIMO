<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banco;
use App\Models\Client;
use App\Services\XGateService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class XGateTestController extends Controller
{
    /**
     * Testa criação de cobrança PIX
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
            
            if (($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo XGate'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::find($request->cliente_id);
            $valor = $request->valor;
            $referenceId = 'TEST_COB_' . time() . '_' . rand(1000, 9999);
            $dueDate = $request->due_date ?? date('Y-m-d', strtotime('+30 days'));

            try {
                $xgateService = new XGateService($banco);
                $response = $xgateService->criarCobranca($valor, $cliente, $referenceId, $dueDate);

                // Se a resposta contém erro, incluir curl_command se disponível
                if (isset($response['success']) && !$response['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $response['error'] ?? 'Erro ao criar cobrança',
                        'request' => [
                            'banco_id' => $banco->id,
                            'banco_nome' => $banco->name,
                            'valor' => $valor,
                            'cliente' => $cliente->nome_completo,
                            'reference_id' => $referenceId,
                            'due_date' => $dueDate
                        ],
                        'response' => $response,
                        'curl_command' => $response['curl_command'] ?? null
                    ]);
                }

                return response()->json([
                    'success' => $response['success'] ?? false,
                    'message' => $response['success'] ? 'Cobrança criada com sucesso' : ($response['error'] ?? 'Erro ao criar cobrança'),
                    'request' => [
                        'banco_id' => $banco->id,
                        'banco_nome' => $banco->name,
                        'valor' => $valor,
                        'cliente' => $cliente->nome_completo,
                        'reference_id' => $referenceId,
                        'due_date' => $dueDate
                    ],
                    'response' => $response,
                    'curl_command' => $response['curl_command'] ?? null
                ]);

            } catch (\Exception $e) {
                Log::channel('xgate')->error('Erro ao testar cobrança XGate: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                // Tentar obter curl_command da propriedade da exceção ou da resposta
                $curlCommand = null;
                if (isset($e->curl_command)) {
                    $curlCommand = $e->curl_command;
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro no servidor, tente novamente',
                    'request' => [
                        'banco_id' => $banco->id ?? null,
                        'banco_nome' => $banco->name ?? null,
                        'valor' => $valor ?? null,
                        'cliente' => $cliente->nome_completo ?? null,
                        'reference_id' => $referenceId ?? null,
                        'due_date' => $dueDate ?? null
                    ],
                    'response' => [
                        'success' => false,
                        'error' => 'Erro no servidor, tente novamente'
                    ],
                    'curl_command' => $curlCommand,
                    'error_details' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao testar cobrança XGate: ' . $e->getMessage());
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
            
            if (($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo XGate'
                ], Response::HTTP_BAD_REQUEST);
            }

            $valor = $request->valor;
            $pixKey = $request->pix_key;
            $description = $request->description ?? 'Teste de Transferência PIX';

            try {
                $xgateService = new XGateService($banco);
                $response = $xgateService->realizarTransferenciaPix($valor, $pixKey, $description);

                return response()->json([
                    'success' => $response['success'] ?? false,
                    'message' => $response['success'] ? 'Transferência realizada com sucesso' : ($response['error'] ?? 'Erro ao realizar transferência'),
                    'request' => [
                        'banco_id' => $banco->id,
                        'banco_nome' => $banco->name,
                        'valor' => $valor,
                        'pix_key' => $pixKey,
                        'description' => $description
                    ],
                    'response' => $response
                ]);

            } catch (\Exception $e) {
                Log::channel('xgate')->error('Erro ao testar transferência XGate: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao testar transferência: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao testar transferência XGate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar transferência: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Testa transferência PIX com cliente completo
     */
    public function testarTransferenciaComCliente(Request $request)
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
            
            if (($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo XGate'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::find($request->cliente_id);
            
            if (!$cliente->pix_cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não possui chave PIX cadastrada'
                ], Response::HTTP_BAD_REQUEST);
            }

            $valor = $request->valor;
            $description = $request->description ?? 'Teste de Transferência PIX';

            try {
                $xgateService = new XGateService($banco);
                $response = $xgateService->realizarTransferenciaPixComCliente($valor, $cliente, $description);

                return response()->json([
                    'success' => $response['success'] ?? false,
                    'message' => $response['success'] ? 'Transferência realizada com sucesso' : ($response['error'] ?? 'Erro ao realizar transferência'),
                    'request' => [
                        'banco_id' => $banco->id,
                        'banco_nome' => $banco->name,
                        'valor' => $valor,
                        'cliente' => $cliente->nome_completo,
                        'cliente_pix' => $cliente->pix_cliente,
                        'description' => $description
                    ],
                    'response' => $response
                ]);

            } catch (\Exception $e) {
                Log::channel('xgate')->error('Erro ao testar transferência XGate com cliente: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao testar transferência: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao testar transferência XGate com cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar transferência: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Consulta saldo da conta
     */
    public function consultarSaldo(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo XGate'
                ], Response::HTTP_BAD_REQUEST);
            }

            try {
                $xgateService = new XGateService($banco);
                $response = $xgateService->consultarSaldo();

                return response()->json([
                    'success' => $response['success'] ?? false,
                    'message' => $response['success'] ? 'Saldo consultado com sucesso' : ($response['error'] ?? 'Erro ao consultar saldo'),
                    'request' => [
                        'banco_id' => $banco->id,
                        'banco_nome' => $banco->name
                    ],
                    'response' => $response
                ]);

            } catch (\Exception $e) {
                Log::channel('xgate')->error('Erro ao consultar saldo XGate: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar saldo: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao consultar saldo XGate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar saldo: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cria ou atualiza cliente no XGate
     */
    public function criarOuAtualizarCliente(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'cliente_id' => 'required|exists:clients,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            
            if (($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo XGate'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::find($request->cliente_id);

            try {
                $xgateService = new XGateService($banco);
                $response = $xgateService->criarOuAtualizarCliente($cliente);

                return response()->json([
                    'success' => $response['success'] ?? false,
                    'message' => $response['success'] ? 'Cliente criado/atualizado com sucesso' : ($response['error'] ?? 'Erro ao criar/atualizar cliente'),
                    'request' => [
                        'banco_id' => $banco->id,
                        'banco_nome' => $banco->name,
                        'cliente' => $cliente->nome_completo,
                        'cliente_cpf' => $cliente->cpf
                    ],
                    'response' => $response
                ]);

            } catch (\Exception $e) {
                Log::channel('xgate')->error('Erro ao criar/atualizar cliente XGate: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar/atualizar cliente: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            Log::channel('xgate')->error('Erro ao criar/atualizar cliente XGate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar/atualizar cliente: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
