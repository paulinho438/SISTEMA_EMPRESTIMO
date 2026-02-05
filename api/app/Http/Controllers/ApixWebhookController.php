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

            $data = null;
            if (is_array($payload)) {
                $data = $payload['data'] ?? $payload;
                $tipoEvento = $data['type'] ?? $payload['type'] ?? null;
                $status = $data['status'] ?? $payload['status'] ?? null;
                $identificador = $data['transaction_id'] ?? $payload['transaction_id'] ?? $data['external_id'] ?? $payload['external_id'] ?? null;
                $valor = isset($data['amount']) ? (float) $data['amount'] : (isset($payload['amount']) ? (float) $payload['amount'] : null);
            }

            $statusUpper = strtoupper((string) $status);
            $tipoUpper = strtoupper((string) $tipoEvento);

            // SÓ salvar quando status for COMPLETED (pagamento confirmado). NUNCA salvar CREATED.
            if ($statusUpper !== 'COMPLETED') {
                Log::channel('apix')->info('Webhook APIX ignorado (só salva status COMPLETED)', [
                    'type' => $tipoEvento,
                    'status' => $status,
                ]);
                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            if ($tipoUpper !== 'DEPOSIT') {
                Log::channel('apix')->info('Webhook APIX ignorado (só processa type Deposit)', [
                    'type' => $tipoEvento,
                    'status' => $status,
                ]);
                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            if (!$identificador) {
                Log::channel('apix')->warning('Webhook APIX COMPLETED sem transaction_id/external_id');
                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

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
                Log::channel('apix')->info('Webhook APIX atualizado para COMPLETED', ['identificador' => $identificador]);
                return response()->json(['message' => 'Webhook atualizado com sucesso'], Response::HTTP_OK);
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
