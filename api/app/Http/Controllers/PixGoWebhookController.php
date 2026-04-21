<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\WebhookPixgo;
use App\Services\PixGoWebhookSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PixGoWebhookController extends Controller
{
    /**
     * POST /api/webhook/pixgo — eventos payment.* conforme documentação PixGo.
     *
     * @see https://pixgo.org/api/v1/docs#endpoints
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

            Log::channel('pixgo')->info('Webhook PixGo recebido', [
                'ip' => $ip,
                'event' => $payload['event'] ?? null,
            ]);

            if (!$this->validarAssinatura($request, $rawBody)) {
                return response()->json(['message' => 'Assinatura inválida'], Response::HTTP_UNAUTHORIZED);
            }

            $event = strtolower((string) ($payload['event'] ?? ''));
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $paymentId = $data['payment_id'] ?? null;
            $status = strtolower((string) ($data['status'] ?? ''));
            $valor = isset($data['amounts']['gross']) ? (float) $data['amounts']['gross'] : (float) ($data['amount'] ?? 0);

            if ($event !== 'payment.completed' || $status !== 'completed') {
                Log::channel('pixgo')->info('Webhook PixGo ignorado (não é pagamento concluído)', [
                    'event' => $event,
                    'status' => $status,
                ]);

                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            if (!$paymentId) {
                Log::channel('pixgo')->warning('Webhook PixGo concluído sem payment_id');

                return response()->json(['message' => 'Webhook recebido'], Response::HTTP_OK);
            }

            $dup = WebhookPixgo::where('identificador', $paymentId)->where('processado', true)->first();
            if ($dup) {
                return response()->json(['message' => 'Webhook já processado'], Response::HTTP_OK);
            }

            WebhookPixgo::create([
                'payload' => $payload,
                'raw_body' => $rawBody,
                'headers' => $headers,
                'ip' => $ip,
                'identificador' => $paymentId,
                'valor' => $valor,
                'tipo_evento' => $event,
                'status' => $status,
                'processado' => false,
            ]);

            return response()->json(['message' => 'Webhook recebido com sucesso'], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::channel('pixgo')->error('Erro ao processar webhook PixGo: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar webhook',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * HMAC-SHA256: timestamp + "." + corpo bruto, comparar com X-Webhook-Signature (hex).
     *
     * @see https://pixgo.org/api/v1/docs#webhooks
     */
    protected function validarAssinatura(Request $request, string $rawBody): bool
    {
        $timestamp = $this->primeiroHeaderLinha($request, 'X-Webhook-Timestamp');
        $signatureHeader = $this->primeiroHeaderLinha($request, 'X-Webhook-Signature');

        if ($timestamp === '' || $signatureHeader === '') {
            Log::channel('pixgo')->warning('Webhook PixGo sem headers de assinatura');

            return false;
        }

        $bancos = Banco::where('bank_type', 'pixgo')->whereNotNull('pixgo_webhook_secret')->get();
        if ($bancos->isEmpty()) {
            Log::channel('pixgo')->warning('Webhook PixGo: nenhum banco com bank_type=pixgo e pixgo_webhook_secret preenchido');

            return false;
        }

        foreach ($bancos as $banco) {
            $secret = PixGoWebhookSignatureVerifier::resolverSegredo($banco->pixgo_webhook_secret);
            if ($secret === '') {
                continue;
            }

            if (PixGoWebhookSignatureVerifier::assinaturaConfere($timestamp, $rawBody, $secret, $signatureHeader)) {
                if (abs(time() - (int) $timestamp) > 300) {
                    Log::channel('pixgo')->warning('Webhook PixGo timestamp fora da janela de 5 minutos');

                    return false;
                }

                return true;
            }
        }

        Log::channel('pixgo')->warning('Webhook PixGo: assinatura não confere com nenhum banco PixGo', [
            'bancos_pixgo_com_segredo' => $bancos->count(),
            'banco_ids' => $bancos->pluck('id')->all(),
            'raw_body_bytes' => strlen($rawBody),
            'signature_hex_len' => strlen(PixGoWebhookSignatureVerifier::normalizarAssinatura($signatureHeader)),
        ]);

        return false;
    }

    /**
     * Primeiro valor do header (Laravel pode devolver array se repetido no request).
     */
    protected function primeiroHeaderLinha(Request $request, string $name): string
    {
        $v = $request->header($name);
        if (is_array($v)) {
            $v = $v[0] ?? '';
        }

        return trim((string) $v);
    }
}
