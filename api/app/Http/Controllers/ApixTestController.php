<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banco;
use App\Models\Client;
use App\Services\ApixService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApixTestController extends Controller
{
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
            if (($banco->bank_type ?? 'normal') !== 'apix') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo APIX'
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::find($request->cliente_id);
            $valor = (float) $request->valor;
            $referenceId = 'TEST_APIX_' . time() . '_' . rand(1000, 9999);
            $dueDate = $request->due_date ? date('Y-m-d', strtotime($request->due_date)) : date('Y-m-d', strtotime('+30 days'));

            $apixService = new ApixService($banco);
            $response = $apixService->criarCobranca($valor, $cliente, $referenceId, $dueDate);

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
                'last_response' => $response['last_response'] ?? $apixService->getLastResponse()
            ]);
        } catch (\Exception $e) {
            Log::channel('apix')->error('Erro ao testar cobrança APIX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar cobrança: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
            if (($banco->bank_type ?? 'normal') !== 'apix') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo APIX'
                ], Response::HTTP_BAD_REQUEST);
            }

            $apixService = new ApixService($banco);
            $response = $apixService->realizarTransferenciaPix(
                (float) $request->valor,
                $request->pix_key,
                $request->description ?? 'Teste de Transferência PIX'
            );

            return response()->json([
                'success' => $response['success'] ?? false,
                'message' => $response['success'] ? 'Transferência realizada com sucesso' : ($response['error'] ?? 'Erro ao realizar transferência'),
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                    'valor' => $request->valor,
                    'pix_key' => $request->pix_key,
                    'description' => $request->description
                ],
                'response' => $response,
                'last_response' => $response['last_response'] ?? $apixService->getLastResponse()
            ]);
        } catch (\Exception $e) {
            Log::channel('apix')->error('Erro ao testar transferência APIX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar transferência: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
            if (($banco->bank_type ?? 'normal') !== 'apix') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo APIX'
                ], Response::HTTP_BAD_REQUEST);
            }

            $apixService = new ApixService($banco);
            $response = $apixService->consultarSaldo();

            return response()->json([
                'success' => $response['success'] ?? false,
                'message' => $response['success'] ? 'Saldo consultado com sucesso' : ($response['error'] ?? 'Erro ao consultar saldo'),
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name
                ],
                'response' => $response,
                'last_response' => $response['last_response'] ?? $apixService->getLastResponse()
            ]);
        } catch (\Exception $e) {
            Log::channel('apix')->error('Erro ao consultar saldo APIX: ' . $e->getMessage());
            $lastResponse = isset($apixService) ? $apixService->getLastResponse() : null;
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar saldo: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'last_response' => $lastResponse
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
