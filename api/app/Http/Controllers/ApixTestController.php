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

    public function testarSaque(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'valor' => 'required|numeric|min:0.01',
                'pix_key' => 'required|string',
                'key_type' => 'required|in:email,cpf,cnpj,phone,evp',
                'key_document' => 'required|string|max:20',
                'external_id' => 'nullable|string|max:100',
                'client_callback_url' => 'nullable|url|max:500'
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

            $externalId = $request->external_id ?: ('saque_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
            $apixService = new ApixService($banco);
            $response = $apixService->realizarSaque(
                (float) $request->valor,
                $request->pix_key,
                $request->key_type,
                $request->key_document,
                $externalId,
                $request->client_callback_url
            );

            return response()->json([
                'success' => $response['success'] ?? false,
                'message' => $response['success'] ? 'Saque solicitado com sucesso' : ($response['error'] ?? 'Erro ao realizar saque'),
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                    'valor' => $request->valor,
                    'pix_key' => $request->pix_key,
                    'key_type' => $request->key_type,
                    'key_document' => $request->key_document,
                    'external_id' => $externalId
                ],
                'response' => $response,
                'last_response' => $response['last_response'] ?? $apixService->getLastResponse()
            ]);
        } catch (\Exception $e) {
            Log::channel('apix')->error('Erro ao testar saque APIX: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar saque: ' . $e->getMessage(),
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
