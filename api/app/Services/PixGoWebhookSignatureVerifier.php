<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Verificação HMAC do webhook PixGo — algoritmo conforme documentação oficial.
 *
 * Passos (PHP na doc):
 * 1. $timestamp = header X-Webhook-Timestamp
 * 2. $signature = header X-Webhook-Signature
 * 3. $payload = corpo bruto (file_get_contents('php://input') / Request::getContent())
 * 4. $signaturePayload = $timestamp . '.' . $payload;
 * 5. $expectedSignature = hash_hmac('sha256', $signaturePayload, $webhookSecret);
 * 6. hash_equals($expectedSignature, $signature)
 *
 * A PixGo pode enviar o header no formato `sha256=<hex>` (71 caracteres); o exemplo da doc mostra só o hex (64).
 * Antes do hash_equals normalizamos o header (trim, opcional `sha256=`, hex em minúsculas).
 *
 * @see https://pixgo.org/api/v1/docs#webhooks
 */
class PixGoWebhookSignatureVerifier
{
    /**
     * Valor de X-Webhook-Signature como string hex de 64 caracteres para comparar com hash_hmac (saída hex minúscula).
     */
    public static function normalizarAssinaturaHeader(string $signature): string
    {
        $s = strtolower(trim(preg_replace('/\s+/', '', $signature)));
        if (str_starts_with($s, 'sha256=')) {
            $s = substr($s, 7);
        }

        return $s;
    }

    /**
     * Valor do segredo no cadastro do banco (texto plano ou legado criptografado com Crypt).
     */
    public static function resolverSegredo(?string $armazenado): string
    {
        if ($armazenado === null || $armazenado === '') {
            return '';
        }
        try {
            return trim(Crypt::decryptString($armazenado));
        } catch (\Exception $e) {
            return trim($armazenado);
        }
    }

    /**
     * Igual ao exemplo da documentação PixGo (hash_hmac + hash_equals).
     * Não altere o corpo: deve ser o JSON bruto exatamente como recebido na requisição.
     */
    public static function assinaturaConfere(
        string $timestamp,
        string $rawBody,
        string $webhookSecret,
        string $signature
    ): bool {
        $webhookSecret = trim($webhookSecret);
        if ($webhookSecret === '' || $timestamp === '' || $signature === '') {
            return false;
        }

        $signaturePayload = $timestamp . '.' . $rawBody;
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $webhookSecret);
        $signatureNorm = self::normalizarAssinaturaHeader($signature);
        if (strlen($signatureNorm) !== 64 || !ctype_xdigit($signatureNorm)) {
            return false;
        }

        return hash_equals($expectedSignature, $signatureNorm);
    }
}
