<?php

namespace App\Jobs;

use App\Models\Emprestimo;
use App\Services\XGateService;
use App\Services\WAPIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessarPixXgateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;
    public $backoff = 60;

    protected $emprestimo;
    /** @var array Dados do comprovante (dados para a view, já com is_xgate etc.) */
    protected $comprovante;
    protected $wapiService;

    public function __construct(Emprestimo $emprestimo, array $comprovante = [])
    {
        $this->emprestimo = $emprestimo;
        $this->comprovante = $comprovante;
        $this->wapiService = new WAPIService();
    }

    public function handle()
    {
        try {
            // 1. Comprovante XGate: gerar PNG e enviar por WhatsApp (controller já fez movimentação e saldo)
            if (!empty($this->comprovante['dados'])) {
                $html = view('comprovante-template', $this->comprovante['dados'])->render();
                $htmlFilePath = storage_path('app/public/comprovante.html');
                file_put_contents($htmlFilePath, $html);

                $pngPath = storage_path('app/public/comprovante.png');
                $width = 800;
                $height = 1600;
                $quality = 85;
                $zoom = 1.5;
                $command = "timeout 120 xvfb-run wkhtmltoimage --width {$width} --height {$height} --quality {$quality} --zoom {$zoom} {$htmlFilePath} {$pngPath}";
                shell_exec($command);

                if (file_exists($pngPath)) {
                    $conteudo = File::get($pngPath);
                    $base64 = 'data:image/png;base64,' . base64_encode($conteudo);
                    $company = $this->emprestimo->company;
                    $telefone = preg_replace('/\D/', '', $this->emprestimo->client->telefone_celular_1);
                    $numeroCliente = '55' . $telefone;
                    $this->wapiService->enviarMensagemImagem($company->token_api_wtz, $company->instance_id, ['delayMessage' => 1, 'phone' => $numeroCliente, 'image' => $base64]);
                }
            }

            // 2. Cobranças via API XGate (parcelas, quitação, pag. mínimo, saldo pendente)
            $this->processarCobrancasXgate();

            // 3. Vídeo (link YouTube)
            $this->envioMensagemVideoYoutube($this->emprestimo->parcelas[0]);

            // 4. Mensagem com link das parcelas
            $this->envioMensagem($this->emprestimo->parcelas[0]);

            // 5. Áudio de boas-vindas (msginicio.ogg)
            if (!empty($this->comprovante)) {
                $nomeArquivo = 'msginicio.ogg';
                $caminhoArquivo = storage_path('app/public/audios/' . $nomeArquivo);
                if (File::exists($caminhoArquivo)) {
                    $conteudo = File::get($caminhoArquivo);
                    $base64 = 'data:audio/ogg;base64,' . base64_encode($conteudo);
                    $company = $this->emprestimo->company;
                    $telefone = preg_replace('/\D/', '', $this->emprestimo->client->telefone_celular_1);
                    $numeroCliente = '55' . $telefone;
                    $this->wapiService->enviarMensagemAudio($company->token_api_wtz, $company->instance_id, ['delayMessage' => 1, 'phone' => $numeroCliente, 'audio' => $base64]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('xgate')->error('ProcessarPixXgateJob: ' . $e->getMessage(), [
                'emprestimo_id' => $this->emprestimo->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Gera cobranças PIX via API XGate para parcelas, quitação, pagamento mínimo e saldo pendente.
     */
    private function processarCobrancasXgate(): void
    {
        $banco = $this->emprestimo->banco;
        if (($banco->bank_type ?? 'normal') !== 'xgate') {
            return;
        }

        $cliente = $this->emprestimo->client;
        try {
            $xgateService = new XGateService($banco);
        } catch (\Exception $e) {
            Log::channel('xgate')->error('ProcessarPixXgateJob: não foi possível inicializar XGateService - ' . $e->getMessage());
            return;
        }

        foreach ($this->emprestimo->parcelas as $parcela) {
            $this->criarCobrancaXgate($xgateService, $parcela, $parcela->saldo ?? $parcela->valor, 'parcela_' . $parcela->id, $parcela->venc_real ? date('Y-m-d', strtotime($parcela->venc_real)) : null, function ($resp) use ($parcela) {
                $parcela->identificador = $resp['transaction_id'] ?? $parcela->identificador;
                $parcela->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? $parcela->chave_pix;
                $parcela->save();
            });
        }

        if ($this->emprestimo->quitacao) {
            $q = $this->emprestimo->quitacao;
            $valorQuitacao = $q->saldo ?? 0;
            if ($valorQuitacao > 0) {
                $this->criarCobrancaXgate($xgateService, $q, $valorQuitacao, 'quitacao_' . $q->id, null, function ($resp) use ($q) {
                    $q->identificador = $resp['transaction_id'] ?? $q->identificador;
                    $q->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? $q->chave_pix;
                    $q->save();
                });
            }
        }

        if ($this->emprestimo->pagamentominimo) {
            $pm = $this->emprestimo->pagamentominimo;
            $this->criarCobrancaXgate($xgateService, $pm, $pm->valor, 'pagamento_minimo_' . $pm->id, null, function ($resp) use ($pm) {
                $pm->identificador = $resp['transaction_id'] ?? $pm->identificador;
                $pm->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? $pm->chave_pix;
                $pm->save();
            });
        }

        if ($this->emprestimo->pagamentosaldopendente) {
            $psp = $this->emprestimo->pagamentosaldopendente;
            $primeiraParcela = $this->emprestimo->parcelas->first();
            $psp->valor = $primeiraParcela && method_exists($primeiraParcela, 'totalPendenteHoje')
                ? $primeiraParcela->totalPendenteHoje()
                : $psp->valor;
            if ($psp->valor > 0) {
                $psp->save();
                $this->criarCobrancaXgate($xgateService, $psp, $psp->valor, 'saldo_' . $psp->id, null, function ($resp) use ($psp) {
                    $psp->identificador = $resp['transaction_id'] ?? $psp->identificador;
                    $psp->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? $psp->chave_pix;
                    $psp->save();
                });
            }
        }
    }

    /**
     * Chama XGate criarCobranca e, em sucesso, aplica o callback para atualizar a entidade.
     */
    private function criarCobrancaXgate(XGateService $xgateService, $entidade, float $valor, string $referenceId, ?string $dueDate, callable $onSuccess): void
    {
        $cliente = $this->emprestimo->client;
        $referenceId = $referenceId . '_' . time();
        $tentativas = 0;
        $maxTentativas = 5;

        while ($tentativas < $maxTentativas) {
            try {
                $response = $xgateService->criarCobranca($valor, $cliente, $referenceId, $dueDate);
                if (isset($response['success']) && $response['success']) {
                    $onSuccess($response);
                    return;
                }
                $tentativas++;
                if ($tentativas < $maxTentativas) {
                    sleep(min($tentativas * 2, 10));
                }
            } catch (\Exception $e) {
                Log::channel('xgate')->error('ProcessarPixXgateJob cobrança: ' . $e->getMessage(), [
                    'reference_id' => $referenceId,
                    'entidade_id' => $entidade->id ?? null,
                ]);
                $tentativas++;
                if ($tentativas < $maxTentativas) {
                    sleep(min($tentativas * 2, 10));
                }
            }
        }
    }

    private function obterSaudacao(): string
    {
        $hora = (int) date('H');
        $saudacoesManha = ['🌤️ Bom dia', '👋 Olá, bom dia', '🌤️ Tenha um excelente dia'];
        $saudacoesTarde = ['🌤️ Boa tarde', '👋 Olá, boa tarde', '🌤️ Espero que sua tarde esteja ótima'];
        $saudacoesNoite = ['🌤️ Boa noite', '👋 Olá, boa noite', '🌤️ Espero que sua noite esteja ótima'];
        if ($hora < 12) {
            return $saudacoesManha[array_rand($saudacoesManha)];
        }
        if ($hora < 18) {
            return $saudacoesTarde[array_rand($saudacoesTarde)];
        }
        return $saudacoesNoite[array_rand($saudacoesNoite)];
    }

    public function envioMensagem($parcela): void
    {
        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $saudacao = $this->obterSaudacao();
        $saudacaoTexto = $saudacao . ', ' . $parcela->emprestimo->client->nome_completo . '!';
        $fraseInicial = "

Relatório de Parcelas Pendentes:

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$parcela->emprestimo->company->numero_contato}
";
        $frase = $saudacaoTexto . $fraseInicial;
        $telefoneCliente = '55' . $telefone;
        $company = $parcela->emprestimo->company;
        if ($company->token_api_wtz && $company->instance_id) {
            $this->wapiService->enviarMensagem($company->token_api_wtz, $company->instance_id, ['delayMessage' => 1, 'phone' => $telefoneCliente, 'message' => $frase]);
        }
    }

    public function envioMensagemVideoYoutube($parcela): void
    {
        try {
            $company = $parcela->emprestimo->company;
            if (!$company->token_api_wtz || !$company->instance_id) {
                return;
            }
            $telefoneCliente = '55' . preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
            $video = count($parcela->emprestimo->parcelas) === 1
                ? 'https://api.agecontrole.com.br/storage/audios/umaParcela.mp4'
                : 'https://api.agecontrole.com.br/storage/audios/variasParcelas.mp4';
            $this->wapiService->enviarMensagemVideo($company->token_api_wtz, $company->instance_id, ['delayMessage' => 1, 'phone' => $telefoneCliente, 'video' => $video]);
        } catch (\Throwable $th) {
            Log::channel('xgate')->warning('ProcessarPixXgateJob envio vídeo: ' . $th->getMessage());
        }
    }

    public function failed(\Exception $exception): void
    {
        Log::channel('xgate')->error('ProcessarPixXgateJob falhou: ' . $exception->getMessage());
    }
}
