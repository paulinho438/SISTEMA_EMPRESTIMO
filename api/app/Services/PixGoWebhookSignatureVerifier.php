<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Verificação HMAC dos webhooks PixGo (documentação: timestamp + "." + corpo bruto).
 */
class PixGoWebhookSignatureVerifier
{
    public static function normalizarAssinatura(string $signature): string
    {
        $s = strtolower(trim(preg_replace('/\s+/', '', $signature)));
        if (str_starts_with($s, 'sha256=')) {
            $s = substr($s, 7);
        }

        return $s;
    }

    /**
     * @return list<string> Mensagens a assinar (cada uma: timestamp . '.' . corpo ou variante).
     */
    public static function candidatosPayloadAssinatura(string $timestamp, string $rawBody): array
    {
        $candidatos = [];
        $candidatos[] = $timestamp . '.' . $rawBody;

        $trimmed = rtrim($rawBody, "\r\n");
        if ($trimmed !== $rawBody) {
            $candidatos[] = $timestamp . '.' . $trimmed;
        }

        $decoded = json_decode($rawBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $compact = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($compact !== false && $compact !== $rawBody) {
                $candidatos[] = $timestamp . '.' . $compact;
            }
        }

        return array_values(array_unique($candidatos));
    }

    public static function resolverSegredo(?string $armazenado): string
    {
        if ($armazenado === null || $armazenado === '') {
            return '';
        }
        try {
            return trim(Crypt::decryptString($armazenado));
        } catch (\Exception $e) {
            // Valor em texto plano no banco (ou legado inválido para APP_KEY atual).
            return trim($armazenado);
        }
    }

    /**
     * @return bool true se alguma variante bater com a assinatura recebida
     */
    public static function assinaturaConfere(string $timestamp, string $rawBody, string $secret, string $signatureHeader): bool
    {
        $signature = self::normalizarAssinatura($signatureHeader);
        if ($signature === '') {
            return false;
        }
        foreach (self::candidatosPayloadAssinatura($timestamp, $rawBody) as $payload) {
            $expected = hash_hmac('sha256', $payload, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
