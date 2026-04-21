<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Client;
use App\Services\PixGoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PixGoTestController extends Controller
{
    public function testarCobranca(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'valor' => 'required|numeric|min:10',
                'cliente_id' => 'required|exists:clients,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos (PixGo exige valor mínimo R$ 10,00)',
                    'errors' => $validator->errors(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            if (($banco->bank_type ?? 'normal') !== 'pixgo') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo PixGo',
                ], Response::HTTP_BAD_REQUEST);
            }

            $cliente = Client::with('address')->find($request->cliente_id);
            $valor = (float) $request->valor;
            $referenceId = 'TEST_PIXGO_' . time() . '_' . rand(1000, 9999);

            $pixGo = new PixGoService($banco);
            $response = $pixGo->criarCobranca($valor, $cliente, $referenceId, null, 'Teste integração');

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
                'last_response' => $response['last_response'] ?? $pixGo->getLastResponse(),
            ]);
        } catch (\Exception $e) {
            Log::channel('pixgo')->error('Erro ao testar cobrança PixGo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar cobrança: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
