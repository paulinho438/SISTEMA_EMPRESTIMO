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

            WebhookApix::create([
                'payload' => $payload,
                'raw_body' => $rawBody,
                'headers' => $headers,
                'ip' => $ip,
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
