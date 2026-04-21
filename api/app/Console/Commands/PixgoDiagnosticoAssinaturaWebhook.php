<?php

namespace App\Console\Commands;

use App\Models\Banco;
use App\Services\PixGoWebhookSignatureVerifier;
use Illuminate\Console\Command;

/**
 * Mesma verificação do PixGoWebhookController / documentação PixGo.
 *
 * @see https://pixgo.org/api/v1/docs#webhooks
 */
class PixgoDiagnosticoAssinaturaWebhook extends Command
{
    protected $signature = 'pixgo:diagnostico-assinatura-webhook
                            {banco_id : ID do banco (bank_type=pixgo)}
                            {--timestamp= : Valor do header X-Webhook-Timestamp}
                            {--signature= : Valor do header X-Webhook-Signature}
                            {--body-file= : Arquivo com o JSON bruto exatamente como recebido (php://input)}';

    protected $description = 'Diagnóstico: hash_hmac(sha256, timestamp.payload, secret) === assinatura (doc PixGo)';

    public function handle(): int
    {
        $bancoId = (int) $this->argument('banco_id');
        $timestamp = trim((string) $this->option('timestamp'));
        $signature = trim((string) $this->option('signature'));
        $bodyFile = (string) $this->option('body-file');

        if ($timestamp === '' || $signature === '' || $bodyFile === '') {
            $this->error('Informe --timestamp=, --signature= e --body-file=.');

            return self::FAILURE;
        }
        if (!is_readable($bodyFile)) {
            $this->error('Arquivo do corpo não encontrado ou sem leitura: ' . $bodyFile);

            return self::FAILURE;
        }

        $rawBody = (string) file_get_contents($bodyFile);
        $banco = Banco::where('id', $bancoId)->where('bank_type', 'pixgo')->first();
        if (!$banco || empty($banco->pixgo_webhook_secret)) {
            $this->error('Banco não encontrado ou não é PixGo / sem pixgo_webhook_secret.');

            return self::FAILURE;
        }

        $secret = PixGoWebhookSignatureVerifier::resolverSegredo($banco->pixgo_webhook_secret);
        if ($secret === '') {
            $this->error('Não foi possível obter o segredo (descriptografia ou valor vazio).');

            return self::FAILURE;
        }

        $this->line('Corpo (bytes): ' . strlen($rawBody));
        $sigNorm = PixGoWebhookSignatureVerifier::normalizarAssinaturaHeader($signature);
        $this->line('Assinatura header len: ' . strlen($signature) . ' | hex normalizado len: ' . strlen($sigNorm));
        $signaturePayload = $timestamp . '.' . $rawBody;
        $this->line('signaturePayload len: ' . strlen($signaturePayload));
        $expectedSignature = hash_hmac('sha256', $signaturePayload, trim($secret));
        $match = strlen($sigNorm) === 64 && ctype_xdigit($sigNorm) && hash_equals($expectedSignature, $sigNorm);

        $this->line('hash_equals: ' . ($match ? 'sim' : 'não'));

        if ($match) {
            $this->info('Assinatura válida (conforme doc PixGo).');

            return self::SUCCESS;
        }

        $this->warn('Não confere. Confira Webhook Secret (Checkouts), mesmo checkout da API Key, e corpo bruto idêntico ao POST.');

        return self::FAILURE;
    }
}
