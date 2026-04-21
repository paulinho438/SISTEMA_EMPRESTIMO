<?php

namespace App\Console\Commands;

use App\Models\Banco;
use App\Services\PixGoWebhookSignatureVerifier;
use Illuminate\Console\Command;

/**
 * Compara o HMAC esperado com o recebido da PixGo (mesma lógica do PixGoWebhookController).
 */
class PixgoDiagnosticoAssinaturaWebhook extends Command
{
    protected $signature = 'pixgo:diagnostico-assinatura-webhook
                            {banco_id : ID do banco (bank_type=pixgo)}
                            {--timestamp= : Valor do header X-Webhook-Timestamp}
                            {--signature= : Valor do header X-Webhook-Signature (hex)}
                            {--body-file= : Arquivo com o JSON bruto exatamente como recebido}';

    protected $description = 'Diagnóstico: verifica se timestamp+corpo+segredo do banco reproduzem a assinatura PixGo';

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

        $sigNorm = PixGoWebhookSignatureVerifier::normalizarAssinatura($signature);
        $this->line('Corpo (bytes): ' . strlen($rawBody));
        $this->line('Assinatura normalizada (len): ' . strlen($sigNorm));

        $ok = false;
        foreach (PixGoWebhookSignatureVerifier::candidatosPayloadAssinatura($timestamp, $rawBody) as $i => $payload) {
            $expected = hash_hmac('sha256', $payload, $secret);
            $match = hash_equals($expected, $sigNorm);
            $this->line(sprintf('Variante %d (payload len %d): %s', $i + 1, strlen($payload), $match ? 'OK' : 'não confere'));
            if ($match) {
                $ok = true;
                break;
            }
        }

        if ($ok) {
            $this->info('Assinatura válida para este banco e corpo.');

            return self::SUCCESS;
        }

        $this->warn('Nenhuma variante confere. No painel PixGo (Checkouts), copie de novo o Webhook Secret (whsec_…) da mesma conta da API Key e salve no banco ' . $bancoId . '.');

        return self::FAILURE;
    }
}
