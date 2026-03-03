<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class D4SignService
{
    private string $tokenApi;
    private string $cryptKey;
    private string $baseUrl;
    private ?string $uuidSafe;

    public function __construct()
    {
        $config = config('services.d4sign');
        $this->tokenApi = (string) ($config['token_api'] ?? '');
        $this->cryptKey = (string) ($config['crypt_key'] ?? '');
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://sandbox.d4sign.com.br/api/v1'), '/');
        $this->uuidSafe = !empty($config['uuid_safe']) ? (string) $config['uuid_safe'] : null;
    }

    public function isConfigured(): bool
    {
        return $this->tokenApi !== '' && $this->cryptKey !== '';
    }

    public function getUuidSafe(): ?string
    {
        return $this->uuidSafe;
    }

    private function authParams(): array
    {
        return [
            'tokenAPI' => $this->tokenApi,
            'cryptKey' => $this->cryptKey,
        ];
    }

    private function buildUrl(string $path): string
    {
        $sep = str_contains($path, '?') ? '&' : '?';
        return $this->baseUrl . $path . $sep . http_build_query($this->authParams());
    }

    /**
     * Lista todos os cofres da conta.
     */
    public function listarCofres(): array
    {
        $url = $this->buildUrl('/safes');
        $resp = Http::acceptJson()->get($url);
        if (!$resp->successful()) {
            Log::warning('D4Sign listarCofres falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return [];
        }
        $data = $resp->json();
        return is_array($data) ? $data : [];
    }

    /**
     * Upload do documento principal para o cofre.
     * Retorna o UUID do documento ou null em caso de erro.
     */
    public function uploadDocumento(string $uuidSafe, UploadedFile|string $file, ?string $filename = null): ?string
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if (!is_file($path)) {
            return null;
        }
        $name = $filename ?? ($file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path));

        $url = $this->buildUrl("/documents/{$uuidSafe}/upload");
        $resp = Http::attach(
            'file',
            file_get_contents($path),
            $name
        )->post($url);

        if (!$resp->successful()) {
            Log::warning('D4Sign uploadDocumento falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }
        $data = $resp->json();
        return $data['uuid'] ?? null;
    }

    /**
     * Obtém o status do documento via /list.
     * Retorna ['statusId' => '1', 'statusName' => 'Processando'] ou null se falhar.
     * statusId: 1=Processando, 2=Aguardando Signatários, 3=Aguardando Assinaturas, 4=Finalizado.
     */
    public function obterStatusDocumento(string $uuidDoc): ?array
    {
        $url = $this->buildUrl("/documents/{$uuidDoc}/list");
        $resp = Http::acceptJson()->get($url);
        if (!$resp->successful()) {
            return null;
        }
        $data = $resp->json();
        $statusId = $data['statusId'] ?? $data['status_id'] ?? null;
        $statusName = $data['statusName'] ?? $data['status_name'] ?? null;
        if ($statusId === null) {
            return null;
        }
        return [
            'statusId' => (string) $statusId,
            'statusName' => (string) ($statusName ?? ''),
        ];
    }

    /**
     * Aguarda o documento sair de "Processando" antes de cadastrar signatários.
     * Polling a cada 2s, máximo 30s (15 tentativas).
     */
    public function aguardarDocumentoPronto(string $uuidDoc, int $maxTentativas = 15, int $intervaloSegundos = 2): bool
    {
        for ($i = 0; $i < $maxTentativas; $i++) {
            $status = $this->obterStatusDocumento($uuidDoc);
            if ($status === null) {
                sleep($intervaloSegundos);
                continue;
            }
            $statusId = $status['statusId'];
            if ($statusId !== '1') {
                Log::info('D4Sign documento pronto', ['uuid' => $uuidDoc, 'statusId' => $statusId, 'statusName' => $status['statusName'] ?? '']);
                return true;
            }
            Log::debug('D4Sign documento ainda processando', ['uuid' => $uuidDoc, 'tentativa' => $i + 1]);
            sleep($intervaloSegundos);
        }
        Log::warning('D4Sign documento não ficou pronto a tempo', ['uuid' => $uuidDoc]);
        return false;
    }

    /**
     * Cadastra signatário no documento.
     * skipEmail=1 para usar embed/link sem enviar email.
     */
    public function cadastrarSignatario(
        string $uuidDoc,
        string $email,
        string $nome,
        string $cpf,
        bool $skipEmail = true
    ): bool {
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        $foreign = strlen($cpfLimpo) >= 11 ? '0' : '1';

        $url = $this->buildUrl("/documents/{$uuidDoc}/createlist");
        $body = [
            'signers' => [
                [
                    'email' => $email,
                    'act' => '1',
                    'foreign' => $foreign,
                    'certificadoicpbr' => '0',
                    'assinatura_presencial' => '0',
                    'docauth' => '0',
                    'docauthandselfie' => '0',
                    'embed_methodauth' => 'email',
                    'upload_allow' => '0',
                    'skipemail' => $skipEmail ? '1' : '0',
                ],
            ],
        ];
        $resp = Http::acceptJson()->contentType('application/json')->post($url, $body);

        if (!$resp->successful()) {
            Log::warning('D4Sign cadastrarSignatario falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        }
        return true;
    }

    /**
     * Envia documento para assinatura.
     * skip_email=1 quando usar embed ou assinatura presencial.
     */
    public function enviarParaAssinatura(
        string $uuidDoc,
        string $message = '',
        bool $skipEmail = true,
        bool $workflow = false
    ): bool {
        $url = $this->buildUrl("/documents/{$uuidDoc}/sendtosigner");
        $body = [
            'message' => $message,
            'skip_email' => $skipEmail ? '1' : '0',
            'workflow' => $workflow ? '1' : '0',
            'tokenAPI' => $this->tokenApi,
        ];
        $resp = Http::acceptJson()->contentType('application/json')->post($url, $body);

        if (!$resp->successful()) {
            Log::warning('D4Sign enviarParaAssinatura falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        }
        return true;
    }

    /**
     * Cadastra webhook para o documento.
     */
    public function cadastrarWebhook(string $uuidDoc, string $webhookUrl): bool
    {
        $url = $this->buildUrl("/documents/{$uuidDoc}/webhooks");
        $resp = Http::acceptJson()->contentType('application/json')->post($url, ['url' => $webhookUrl]);

        if (!$resp->successful()) {
            Log::warning('D4Sign cadastrarWebhook falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return false;
        }
        return true;
    }

    /**
     * Gera URL para download do documento assinado.
     * Retorna ['url' => ..., 'name' => ...] ou null.
     */
    public function downloadDocumento(string $uuidDoc, string $type = 'pdf'): ?array
    {
        $url = $this->buildUrl("/documents/{$uuidDoc}/download");
        $body = ['type' => $type, 'language' => 'pt'];
        $resp = Http::acceptJson()->contentType('application/json')->post($url, $body);

        if (!$resp->successful()) {
            Log::warning('D4Sign downloadDocumento falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }
        $data = $resp->json();
        return isset($data['url']) ? $data : null;
    }

    /**
     * Obtém o link de assinatura do primeiro signatário via API.
     * Funciona sem EMBED habilitado - usa a página padrão da D4Sign.
     * O link só está disponível após enviar o documento para assinatura.
     */
    public function obterLinkAssinatura(string $uuidDoc): ?string
    {
        $url = $this->buildUrl("/documents/{$uuidDoc}/list");
        $resp = Http::acceptJson()->get($url);
        if (!$resp->successful()) {
            Log::warning('D4Sign listarSignatarios falhou', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }
        $data = $resp->json();
        $list = $data['list'] ?? null;
        if (!$list) {
            return null;
        }
        $signers = is_array($list) && isset($list[0]) ? $list : [$list];
        $first = $signers[0] ?? null;
        if (!$first) {
            return null;
        }
        if (!empty($first['assinatura_presencial_link'])) {
            return (string) $first['assinatura_presencial_link'];
        }
        $keySigner = $first['key_signer'] ?? null;
        if (!$keySigner) {
            return null;
        }
        $linkUrl = $this->buildUrl("/documents/{$uuidDoc}/signaturelink/" . rawurlencode($keySigner));
        $linkResp = Http::acceptJson()->get($linkUrl);
        if (!$linkResp->successful()) {
            Log::warning('D4Sign obterLinkAssinatura falhou', ['status' => $linkResp->status(), 'body' => $linkResp->body()]);
            return null;
        }
        $linkData = $linkResp->json();
        return $linkData['link'] ?? null;
    }

    /**
     * Monta URL do embed para o cliente assinar.
     * Requer EMBED habilitado na conta D4Sign (contatar suporte@d4sign.com.br).
     * Base embed: https://sandbox.d4sign.com.br/embed/viewblob/{uuid}
     */
    public function getEmbedUrl(
        string $uuidDoc,
        string $email,
        string $nome = '',
        string $cpf = '',
        string $dataNascimento = ''
    ): string {
        $embedBase = str_replace('/api/v1', '', $this->baseUrl);
        $embedBase = rtrim($embedBase, '/');
        $url = "{$embedBase}/embed/viewblob/{$uuidDoc}";
        $params = ['email' => $email];
        if ($nome !== '') {
            $params['display_name'] = $nome;
        }
        if ($cpf !== '') {
            $params['documentation'] = preg_replace('/\D/', '', $cpf);
        }
        if ($dataNascimento !== '') {
            $params['birthday'] = $dataNascimento;
        }
        return $url . '?' . http_build_query($params);
    }
}
