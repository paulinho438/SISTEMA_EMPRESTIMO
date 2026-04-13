<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Client;
use App\Services\GoldPixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GoldPixTestController extends Controller
{
    public function testarCobranca(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'valor' => 'required|numeric|min:0.01',
                'cliente_id' => 'required|exists:clients,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            if (($banco->bank_type ?? 'normal') !== 'goldpix') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo GoldPix',
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::with('address')->find($request->cliente_id);
            $valor = (float) $request->valor;
            $referenceId = 'TEST_GOLDPIX_' . time() . '_' . rand(1000, 9999);

            $goldPix = new GoldPixService($banco);
            $response = $goldPix->criarCobranca($valor, $cliente, $referenceId, null);

            return response()->json([
                'success' => $response['success'] ?? false,
                'message' => $response['success'] ? 'Cobrança criada com sucesso' : ($response['error'] ?? 'Erro ao criar cobrança'),
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                    'valor' => $valor,
                    'cliente' => $cliente->nome_completo,
                    'reference_id' => $referenceId,
                ],
                'response' => $response,
                'last_response' => $response['last_response'] ?? $goldPix->getLastResponse(),
            ]);
        } catch (\Exception $e) {
            Log::channel('goldpix')->error('Erro ao testar cobrança GoldPix: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar cobrança: ' . $e->getMessage(),
                'error' => $e->getMessage(),
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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            if (($banco->bank_type ?? 'normal') !== 'goldpix') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo GoldPix',
                ], Response::HTTP_BAD_REQUEST);
            }

            $goldPix = new GoldPixService($banco);
            $tipo = $request->input('pix_key_type')
                ? strtolower((string) $request->input('pix_key_type'))
                : $goldPix->mapearPixKeyTypeParaGoldPix($request->pix_key);

            $allowed = ['cpf', 'cnpj', 'email', 'phone', 'random'];
            if (!in_array($tipo, $allowed, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'pix_key_type inválido. Use: ' . implode(', ', $allowed),
                ], Response::HTTP_BAD_REQUEST);
            }

            $docDestino = preg_replace('/\D/', '', (string) $request->input('cpf_cnpj_destino', ''));
            $keyInfo = $goldPix->obterPixKeyTypeParaSaque($request->pix_key, $docDestino !== '' ? $docDestino : null);
            if (!$keyInfo['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $keyInfo['error'],
                ], Response::HTTP_BAD_REQUEST);
            }
            $tipo = $keyInfo['pix_key_type'];

            $pixKeyEnvio = $goldPix->normalizarChavePixParaEnvio($request->pix_key, $tipo);
            $externalId = $request->input('external_id') ?: ('saque_goldpix_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 10));
            $postback = config('services.goldpix.callback_url');

            $response = $goldPix->solicitarSaque(
                (float) $request->valor,
                $pixKeyEnvio,
                $tipo,
                $externalId,
                $postback
            );

            return response()->json([
                'success' => $response['success'] ?? false,
                'message' => $response['success'] ? 'Saque solicitado com sucesso' : ($response['error'] ?? 'Erro ao realizar saque'),
                'request' => [
                    'banco_id' => $banco->id,
                    'valor' => $request->valor,
                    'pix_key' => $request->pix_key,
                    'pix_key_type' => $tipo,
                    'external_id' => $externalId,
                ],
                'response' => $response,
                'last_response' => $response['last_response'] ?? $goldPix->getLastResponse(),
            ]);
        } catch (\Exception $e) {
            Log::channel('goldpix')->error('Erro ao testar saque GoldPix: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar saque: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function consultarSaldo(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            if (($banco->bank_type ?? 'normal') !== 'goldpix') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo GoldPix',
                ], Response::HTTP_BAD_REQUEST);
            }

            $goldPix = new GoldPixService($banco);
            $response = $goldPix->consultarSaldoDisponivel();

            return response()->json([
                'success' => $response['success'] ?? false,
                'message' => $response['success'] ? 'Saldo consultado com sucesso' : ($response['error'] ?? 'Erro ao consultar saldo'),
                'request' => [
                    'banco_id' => $banco->id,
                    'banco_nome' => $banco->name,
                ],
                'response' => $response,
                'last_response' => $response['last_response'] ?? $goldPix->getLastResponse(),
            ]);
        } catch (\Exception $e) {
            Log::channel('goldpix')->error('Erro ao consultar saldo GoldPix: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar saldo: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
