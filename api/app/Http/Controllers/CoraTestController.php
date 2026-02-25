<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Banco;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class CoraTestController extends Controller
{
    /**
     * Gera token Cora via mTLS (client_credentials).
     * Por padrão usa stage, mas aceita environment=stage|production.
     */
    public function gerarToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'banco_id' => 'required|exists:bancos,id',
                'environment' => 'nullable|in:stage,production',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $banco = Banco::find($request->banco_id);
            $bankType = $banco->bank_type ?? ($banco->wallet ? 'bcodex' : 'normal');
            if ($bankType !== 'cora') {
                return response()->json([
                    'success' => false,
                    'message' => 'O banco selecionado não é do tipo Cora',
                    'bank_type' => $bankType
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!$banco->client_id || !$banco->certificate_path || !$banco->private_key_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banco Cora não está configurado corretamente',
                    'missing' => [
                        'client_id' => !$banco->client_id,
                        'certificate_path' => !$banco->certificate_path,
                        'private_key_path' => !$banco->private_key_path,
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!file_exists($banco->certificate_path) || !file_exists($banco->private_key_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Arquivos de certificado não encontrados',
                    'certificate_info' => [
                        'certificate_path' => $banco->certificate_path,
                        'private_key_path' => $banco->private_key_path,
                        'cert_exists' => $banco->certificate_path ? file_exists($banco->certificate_path) : false,
                        'key_exists' => $banco->private_key_path ? file_exists($banco->private_key_path) : false,
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

            $env = $request->environment ?: 'stage';
            $tokenUrl = $env === 'stage'
                ? 'https://matls-clients.api.stage.cora.com.br/token'
                : 'https://matls-clients.api.cora.com.br/token';

            $httpClient = Http::asForm()
                ->withHeaders([
                    'accept' => 'application/json',
                    'X-Client-Id' => $banco->client_id,
                ])
                ->withOptions([
                    'cert' => $banco->certificate_path,
                    'ssl_key' => $banco->private_key_path,
                    'verify' => env('CORA_VERIFY_SSL', true),
                    'http_errors' => false,
                ]);

            $response = $httpClient->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $banco->client_id,
            ]);

            $data = null;
            try {
                $data = $response->json();
            } catch (\Throwable $e) {
                $data = ['raw_body' => $response->body()];
            }

            return response()->json([
                'success' => $response->successful() && !empty($data['access_token']),
                'message' => $response->successful() ? 'Token gerado' : 'Falha ao gerar token',
                'environment' => $env,
                'token_url' => $tokenUrl,
                'response' => [
                    'status_code' => $response->status(),
                    'data' => $data,
                    'successful' => $response->successful(),
                    'headers' => $response->headers(),
                ],
            ], $response->successful() ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar token Cora: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar token: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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

