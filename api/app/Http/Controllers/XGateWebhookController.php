<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebhookXgate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class XGateWebhookController extends Controller
{
    /**
     * Recebe webhook do XGate
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function receber(Request $request)
    {
        try {
            $data = $request->json()->all();

            Log::info('Webhook XGate recebido', ['payload' => $data]);

            // Extrair informações básicas do webhook
            $identificador = null;
            $valor = null;
            $tipoEvento = null;
            $status = null;

            // Tentar identificar o tipo de evento e extrair dados
            if (isset($data['data'])) {
                // Webhook de depósito (deposit)
                if (isset($data['data']['id'])) {
                    $identificador = $data['data']['id'];
                }
                if (isset($data['data']['code'])) {
                    $identificador = $data['data']['code'] ?? $identificador;
                }
                if (isset($data['data']['status'])) {
                    $status = $data['data']['status'];
                }
                if (isset($data['data']['amount'])) {
                    $valor = (float) $data['data']['amount'];
                }
                $tipoEvento = 'deposit';
            } elseif (isset($data['_id'])) {
                // Webhook de saque (withdraw)
                $identificador = $data['_id'];
                if (isset($data['status'])) {
                    $status = $data['status'];
                }
                if (isset($data['amount'])) {
                    $valor = (float) $data['amount'];
                }
                $tipoEvento = 'withdraw';
            } elseif (isset($data['id'])) {
                $identificador = $data['id'];
                $tipoEvento = 'other';
            }

            // Verificar se já existe um webhook com o mesmo identificador não processado
            if ($identificador) {
                $webhookExistente = WebhookXgate::where('identificador', $identificador)
                    ->where('processado', false)
                    ->first();
                
                if ($webhookExistente) {
                    Log::info('Webhook XGate já recebido anteriormente', ['identificador' => $identificador]);
                    return response()->json(['message' => 'Webhook já recebido anteriormente']);
                }
            }

            // Criar registro do webhook
            $dados = [
                'payload' => $data,
                'identificador' => $identificador,
                'valor' => $valor,
                'tipo_evento' => $tipoEvento,
                'status' => $status,
                'processado' => false
            ];

            // Se houver múltiplos itens (como array de PIX), contar
            if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 1) {
                $dados['qt_identificadores'] = count($data['data']);
            } elseif (isset($data['pix']) && is_array($data['pix'])) {
                $dados['qt_identificadores'] = count($data['pix']);
            }

            WebhookXgate::create($dados);

            Log::info('Webhook XGate salvo com sucesso', [
                'identificador' => $identificador,
                'tipo_evento' => $tipoEvento,
                'status' => $status
            ]);

            return response()->json(['message' => 'Webhook recebido com sucesso'], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook XGate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $request->json()->all()
            ]);

            return response()->json([
                'message' => 'Erro ao processar webhook',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
