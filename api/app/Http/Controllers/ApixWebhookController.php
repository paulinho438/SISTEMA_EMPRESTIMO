<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebhookApix;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApixWebhookController extends Controller
{
    /**
     * Recebe webhook da APIX e salva tudo em banco para análise posterior.
     * URL: https://api.agecontrole.com.br/api/webhook/apix
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function receber(Request $request)
    {
        try {
            $payload = null;
            $rawBody = null;

            if ($request->isJson() || $request->header('Content-Type') && str_contains($request->header('Content-Type'), 'application/json')) {
                $payload = $request->json()->all();
            }

            if (empty($payload)) {
                $rawBody = $request->getContent();
            }

            $headers = $request->headers->all();
            $ip = $request->ip();

            Log::channel('apix')->info('Webhook APIX recebido', [
                'ip' => $ip,
                'payload' => $payload ?? $rawBody,
            ]);

            $identificador = null;
            $valor = null;
            $tipoEvento = null;
            $status = null;

            if (is_array($payload)) {
                $tipoEvento = $payload['type'] ?? null;
                $status = $payload['status'] ?? null;
                $identificador = $payload['transaction_id'] ?? $payload['external_id'] ?? null;
                $valor = isset($payload['amount']) ? (float) $payload['amount'] : null;
            }

            // Só salvar e processar quando for Deposit + COMPLETED (pagamento confirmado)
            if (strtoupper((string) $tipoEvento) !== 'DEPOSIT' || strtoupper((string) $status) !== 'COMPLETED') {
                Log::channel('apix')->info('Webhook APIX ignorado (não é Deposit/COMPLETED)', [
                    'type' => $tipoEvento,
                    'status' => $status,
                ]);
                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            if ($identificador) {
                $webhookExistente = WebhookApix::where('identificador', $identificador)->first();
                if ($webhookExistente) {
                    if ($webhookExistente->processado) {
                        Log::channel('apix')->info('Webhook APIX já processado', ['identificador' => $identificador]);
                        return response()->json(['message' => 'Webhook já processado'], Response::HTTP_OK);
                    }
                    $webhookExistente->update([
                        'payload' => $payload,
                        'status' => $status,
                        'valor' => $valor,
                        'tipo_evento' => $tipoEvento,
                    ]);
                    Log::channel('apix')->info('Webhook APIX atualizado', ['identificador' => $identificador]);
                    return response()->json(['message' => 'Webhook atualizado com sucesso'], Response::HTTP_OK);
                }
            }

            WebhookApix::create([
                'payload' => $payload,
                'raw_body' => $rawBody,
                'headers' => $headers,
                'ip' => $ip,
                'identificador' => $identificador,
                'valor' => $valor,
                'tipo_evento' => $tipoEvento,
                'status' => $status,
                'processado' => false,
            ]);

            return response()->json(['message' => 'Webhook recebido com sucesso'], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::channel('apix')->error('Erro ao processar webhook APIX: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar webhook',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
