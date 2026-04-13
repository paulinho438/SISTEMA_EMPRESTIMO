<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\WebhookGoldpix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GoldPixWebhookController extends Controller
{
    /**
     * POST /api/webhook/goldpix — payload conforme documentação GoldPix (transaction.approved, etc.).
     */
    public function receber(Request $request)
    {
        try {
            $rawBody = $request->getContent();
            $payload = null;
            if ($request->isJson() || ($request->header('Content-Type') && str_contains((string) $request->header('Content-Type'), 'application/json'))) {
                $payload = $request->json()->all();
            }
            if (empty($payload)) {
                $payload = json_decode($rawBody, true) ?: [];
            }

            $headers = $request->headers->all();
            $ip = $request->ip();

            Log::channel('goldpix')->info('Webhook GoldPix recebido', [
                'ip' => $ip,
                'payload' => $payload,
            ]);

            $this->validarAssinaturaSeConfigurada($request, $rawBody, $payload);

            $event = strtolower((string) ($payload['event'] ?? ''));
            $tipo = strtolower((string) ($payload['type'] ?? ''));
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $status = strtolower((string) ($payload['status'] ?? $data['status'] ?? ''));

            $identificador = $payload['transaction_id'] ?? $data['id'] ?? null;
            $valor = isset($payload['amount']) ? (float) $payload['amount'] : (float) ($data['amount'] ?? $data['paidAmount'] ?? 0);

            if ($tipo === 'withdraw') {
                WebhookGoldpix::create([
                    'payload' => $payload,
                    'raw_body' => $rawBody,
                    'headers' => $headers,
                    'ip' => $ip,
                    'identificador' => $identificador ?? ($payload['objectId'] ?? null),
                    'valor' => $valor,
                    'tipo_evento' => $tipo ?: $event,
                    'status' => $status,
                    'processado' => true,
                ]);

                return response()->json(['message' => 'Webhook withdraw registrado'], Response::HTTP_OK);
            }

            if ($event !== 'transaction.approved' && $status !== 'approved') {
                Log::channel('goldpix')->info('Webhook GoldPix ignorado (não é pagamento aprovado)', [
                    'event' => $event,
                    'status' => $status,
                ]);

                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            if (!$identificador) {
                Log::channel('goldpix')->warning('Webhook GoldPix aprovado sem transaction_id');

                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            $dup = WebhookGoldpix::where('identificador', $identificador)->where('processado', true)->first();
            if ($dup) {
                return response()->json(['message' => 'Webhook já processado'], Response::HTTP_OK);
            }

            WebhookGoldpix::create([
                'payload' => $payload,
                'raw_body' => $rawBody,
                'headers' => $headers,
                'ip' => $ip,
                'identificador' => $identificador,
                'valor' => $valor,
                'tipo_evento' => $event ?: $tipo,
                'status' => $status,
                'processado' => false,
            ]);

            return response()->json(['message' => 'Webhook recebido com sucesso'], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::channel('goldpix')->error('Erro ao processar webhook GoldPix: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar webhook',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Valida HMAC conforme doc GoldPix (header legado x-unicocash-signature ou x-goldpix-signature).
     */
    protected function validarAssinaturaSeConfigurada(Request $request, string $rawBody, array $payload): void
    {
        $signature = $request->header('x-unicocash-signature')
            ?? $request->header('X-UnicoCash-Signature')
            ?? $request->header('x-goldpix-signature')
            ?? $request->header('X-Goldpix-Signature');

        if (!$signature) {
            return;
        }

        $bancos = Banco::where('bank_type', 'goldpix')->whereNotNull('goldpix_webhook_secret')->get();
        foreach ($bancos as $banco) {
            $secret = $banco->goldpix_webhook_secret;
            if (!$secret) {
                continue;
            }
            try {
                $secret = Crypt::decryptString($secret);
            } catch (\Exception $e) {
            }
            if ($secret === '') {
                continue;
            }
            $expected = hash_hmac('sha256', $rawBody !== '' ? $rawBody : json_encode($payload), $secret);
            if (hash_equals($expected, $signature) || hash_equals($expected, strtolower($signature))) {
                return;
            }
            $expected2 = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $secret);
            if (hash_equals($expected2, $signature) || hash_equals($expected2, strtolower($signature))) {
                return;
            }
        }

        Log::channel('goldpix')->warning('Webhook GoldPix com assinatura inválida ou secret não encontrado');
    }
}
