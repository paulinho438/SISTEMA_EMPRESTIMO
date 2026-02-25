<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class CoraTestController extends Controller
{
    /**
     * Testa transferência Cora (stage) conforme cURL informado.
     *
     * Endpoint alvo:
     * POST https://api.stage.cora.com.br/transfers/initiate
     *
     * Requisitos:
     * - bearer_token: token Bearer válido
     * - payload: corpo JSON da transferência
     */
    public function testarTransferencia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bearer_token' => 'required|string',
                'payload' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $token = trim((string) $request->bearer_token);
            if (stripos($token, 'bearer ') === 0) {
                $token = trim(substr($token, 7));
            }

            $url = 'https://api.stage.cora.com.br/transfers/initiate';
            $idempotencyKey = (string) Str::uuid();

            $headers = [
                'Idempotency-Key' => $idempotencyKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ];

            $response = Http::withHeaders($headers)
                ->withOptions(['http_errors' => false])
                ->post($url, $request->payload);

            $body = null;
            try {
                $body = $response->json();
            } catch (\Throwable $e) {
                $body = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Transferência iniciada com sucesso' : 'Erro ao iniciar transferência',
                'request_url' => $url,
                'headers_sent' => [
                    'Idempotency-Key' => $idempotencyKey,
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer [redacted]'
                ],
                'payload_sent' => $request->payload,
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $body,
                    'successful' => $response->successful(),
                    'headers' => $response->headers(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao testar transferência Cora: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao testar transferência: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

