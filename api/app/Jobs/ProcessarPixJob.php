<?php

namespace App\Jobs;

use App\Models\Emprestimo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ApixService;
use App\Services\BcodexService;
use App\Services\GoldPixService;
use App\Services\XGateService;
use App\Models\Parcela;
use App\Models\Quitacao;
use App\Models\PagamentoMinimo;
use App\Models\PagamentoSaldoPendente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CustomLog;
use App\Models\Movimentacaofinanceira;
use App\Models\CobrancaPixIdentificadorHistorico;

use App\Services\WAPIService;
use Illuminate\Support\Facades\File;


class ProcessarPixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutos

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    protected $emprestimo;
    protected $bcodexService;

    protected $comprovante;
    protected $wapiService;

    /**
     * Quando true (ex.: migração de banco), não gera movimentação "Refinanciamento" nem altera saldo do banco;
     * apenas regenera cobranças PIX e mensagens como no fluxo normal.
     */
    public bool $somenteRegenerarCobrancas;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Emprestimo $emprestimo, BcodexService $bcodexService, ?array $comprovante = [], bool $somenteRegenerarCobrancas = false)
    {
        $this->emprestimo = $emprestimo;
        $this->bcodexService = $bcodexService;
        $this->comprovante = $comprovante;
        $this->somenteRegenerarCobrancas = $somenteRegenerarCobrancas;
        $this->wapiService = new WAPIService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->emprestimo->loadMissing([
                'banco',
                'client',
                'company',
                'parcelas',
                'pagamentominimo',
                'quitacao',
                'pagamentosaldopendente',
            ]);

            if ($this->comprovante) {
                // Renderizar o HTML da view
                $html = view('comprovante-template', $this->comprovante['dados'])->render();

                // Salvar o HTML em um arquivo temporário
                $htmlFilePath = storage_path('app/public/comprovante.html');
                file_put_contents($htmlFilePath, $html);

                // Caminho para o arquivo PNG de saída
                $pngPath = storage_path('app/public/comprovante.png');

                // Configurações de tamanho, qualidade e zoom
                $width = 800;    // Largura em pixels
                $height = 1600;  // Altura em pixels
                $quality = 85;  // Qualidade máxima
                $zoom = 1.5;     // Zoom de 2x

                // Executar o comando wkhtmltoimage com ajustes e timeout
                $command = "timeout 120 xvfb-run wkhtmltoimage --width {$width} --height {$height} --quality {$quality} --zoom {$zoom} {$htmlFilePath} {$pngPath}";
                shell_exec($command);

                // Verificar se o PNG foi gerado
                if (file_exists($pngPath)) {
                    $conteudo = File::get($pngPath);
                    $base64 = 'data:image/png;base64,' . base64_encode($conteudo);
                    $company = $this->emprestimo->company;
                    $telefone = preg_replace('/\D/', '', $this->emprestimo->client->telefone_celular_1);
                    $numeroCliente = "55" . $telefone;
                    if (1 == 0) {
                        try {
                            $telefone = preg_replace('/\D/', '', $this->emprestimo->client->telefone_celular_1);
                            // Enviar o PNG gerado para o endpoint
                            $response = Http::timeout(30)->attach(
                                'arquivo', // Nome do campo no formulário
                                fopen($pngPath, 'rb'), // Conteúdo do arquivo
                                'comprovante.png' // Nome do arquivo enviado
                            )->post($this->emprestimo->company->whatsapp . '/enviar-pdf', [
                                'numero' => "55" . $telefone,
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('Erro ao enviar comprovante via WhatsApp: ' . $e->getMessage());
                        }
                        
                    } else {
                        $this->wapiService->enviarMensagemImagem($company->token_api_wtz, $company->instance_id, ["delayMessage" => 1, "phone" => $numeroCliente, "image" => $base64]);
                    }


                } else {
                }
            }

            if (!$this->somenteRegenerarCobrancas) {
                $valor = $this->emprestimo->valor;
                if ($this->emprestimo->valor_deposito > 0) {
                    $valor = $this->emprestimo->valor_deposito;
                }

                $movimentacaoFinanceira = [];
                $movimentacaoFinanceira['banco_id'] = $this->emprestimo->banco->id;
                $movimentacaoFinanceira['company_id'] = $this->emprestimo->company_id;
                if ($this->comprovante) {
                    if ($this->emprestimo->valor_deposito > 0) {
                        $movimentacaoFinanceira['descricao'] = 'Renovação 80% Empréstimo Nº ' . $this->emprestimo->id . ' para ' . $this->emprestimo->client->nome_completo;
                    } else {
                        $movimentacaoFinanceira['descricao'] = 'Empréstimo Nº ' . $this->emprestimo->id . ' para ' . $this->emprestimo->client->nome_completo;
                    }
                } else {
                    $movimentacaoFinanceira['descricao'] = 'Refinanciamento Empréstimo Nº ' . $this->emprestimo->id . ' para ' . $this->emprestimo->client->nome_completo;
                }
                $movimentacaoFinanceira['tipomov'] = 'S';
                $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                $movimentacaoFinanceira['valor'] = $valor;

                Movimentacaofinanceira::create($movimentacaoFinanceira);

                if ($this->comprovante) {
                    $this->emprestimo->banco->saldo -= $valor;
                    $this->emprestimo->banco->save();
                }
            }

            foreach ($this->emprestimo->parcelas as $parcela) {
                $this->processarCobrancaComTentativas($parcela, $parcela['valor']);
            }

            if ($this->emprestimo->pagamentominimo) {
                $this->processarCobrancaComTentativas($this->emprestimo->pagamentominimo, $this->emprestimo->pagamentominimo->valor);
            }

            if ($this->emprestimo->quitacao) {
                $this->processarCobrancaComTentativas($this->emprestimo->quitacao, $this->emprestimo->quitacao->saldo);
            }

            if ($this->emprestimo->pagamentosaldopendente) {
                $this->processarCobrancaComTentativas($this->emprestimo->pagamentosaldopendente, $this->emprestimo->pagamentosaldopendente->valor);
            }

            $this->emprestimo->refresh();
            $this->emprestimo->load(['parcelas', 'pagamentominimo', 'quitacao', 'pagamentosaldopendente', 'client', 'company']);

            $primeiraParcela = $this->emprestimo->parcelas->first();
            if ($primeiraParcela) {
                $this->envioMensagemVideoYoutube($primeiraParcela);
                $this->envioMensagem($primeiraParcela);
            } else {
                Log::warning('ProcessarPixJob: sem parcelas para enviar vídeo/mensagem', [
                    'emprestimo_id' => $this->emprestimo->id,
                ]);
            }


            if ($this->comprovante && $primeiraParcela) {
                $nomeArquivo = 'msginicio.ogg';
                $caminhoArquivo = storage_path('app/public/audios/' . $nomeArquivo);

                if (File::exists($caminhoArquivo)) {
                    $conteudo = File::get($caminhoArquivo);
                    $base64 = 'data:audio/ogg;base64,' . base64_encode($conteudo);

                    $company = $this->emprestimo->company;
                    $telefone = preg_replace('/\D/', '', $this->emprestimo->client->telefone_celular_1);
                    $numeroCliente = "55" . $telefone;

                    $this->wapiService->enviarMensagemAudio($company->token_api_wtz, $company->instance_id, ["delayMessage" => 1, "phone" => $numeroCliente, "audio" => $base64]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao processar PIX: ' . $e->getMessage(), [
                'emprestimo_id' => $this->emprestimo->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Processar cobrança com tentativas (B.Codex ou XGate conforme o banco).
     *
     * @param \App\Models\Parcela|\App\Models\Quitacao|\App\Models\PagamentoMinimo|\App\Models\PagamentoSaldoPendente $entidade
     * @param float|int $valor
     */
    private function processarCobrancaComTentativas($entidade, $valor)
    {
        $tentativas = 0;
        $maxTentativas = 5;
        $sucesso = false;

        $bankType = $this->emprestimo->banco->resolvedBankType();
        if ($bankType === 'xgate') {
            // Cobrança XGate será gerada on-demand ao copiar a chave PIX em outra tela.
            return;
        }

        $tipoOrigem = strtoupper((string) ($this->emprestimo->tipo_origem ?? ''));
        if ($tipoOrigem === 'REFINANCIAMENTO' && in_array($bankType, ['xgate', 'apix', 'goldpix'], true)) {
            return;
        }

        while ($tentativas < $maxTentativas && !$sucesso) {
            try {
                if ($bankType === 'xgate' || $bankType === 'apix' || $bankType === 'goldpix') {
                    $cliente = $this->emprestimo->client;
                    if (!$cliente) {
                        $ch = $bankType === 'apix' ? 'apix' : ($bankType === 'goldpix' ? 'goldpix' : 'xgate');
                        Log::channel($ch)->error("ProcessarPixJob: cliente ausente para cobrança {$bankType}", [
                            'emprestimo_id' => $this->emprestimo->id,
                        ]);
                        break;
                    }

                    $banco = $this->emprestimo->banco;
                    $referenceId = $this->montarReferenceIdCobranca($entidade);
                    $dueDate = $this->dueDateParaCobrancaXgate($entidade);

                    if ($bankType === 'xgate') {
                        $xgateService = new XGateService($banco);
                        $documentoXgate = $this->documentoXgateParaCliente($cliente);
                        $response = $xgateService->criarCobranca(
                            (float) $valor,
                            $cliente,
                            $referenceId,
                            $dueDate,
                            $documentoXgate
                        );
                    } elseif ($bankType === 'apix') {
                        $apixService = new ApixService($banco);
                        $response = $apixService->criarCobranca(
                            (float) $valor,
                            $cliente,
                            $referenceId,
                            $dueDate
                        );
                    } else {
                        $goldPixService = new GoldPixService($banco);
                        $response = $goldPixService->criarCobranca(
                            (float) $valor,
                            $cliente,
                            $referenceId,
                            $dueDate
                        );
                    }

                    if (is_array($response) && !empty($response['success'])) {
                        $entidade->identificador = $response['transaction_id'] ?? $referenceId;
                        $entidade->chave_pix = $response['pixCopiaECola'] ?? $response['qr_code'] ?? null;
                        $entidade->save();
                        $entidade->loadMissing('emprestimo.banco', 'emprestimo.client');
                        CobrancaPixIdentificadorHistorico::registrarCobranca(
                            $bankType,
                            $entidade->identificador,
                            $entidade,
                            (float) $valor,
                            $referenceId
                        );
                        $sucesso = true;
                    } else {
                        $tentativas++;
                        if ($tentativas < $maxTentativas) {
                            sleep(min($tentativas * 2, 10));
                        }
                    }
                } else {
                    $response = $this->bcodexService->criarCobranca($valor, $this->emprestimo->banco->document);

                    if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                        $json = $response->json();
                        if (is_array($json) && isset($json['txid'], $json['pixCopiaECola'])) {
                            $entidade->identificador = $json['txid'];
                            $entidade->chave_pix = $json['pixCopiaECola'];
                            $entidade->save();
                            $sucesso = true;
                        }
                    }
                    if (!$sucesso) {
                        $tentativas++;
                        if ($tentativas < $maxTentativas) {
                            sleep(min($tentativas * 2, 10));
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Erro ao processar cobrança: ' . $e->getMessage(), [
                    'tentativa' => $tentativas + 1,
                    'max_tentativas' => $maxTentativas,
                    'entidade_id' => $entidade->id ?? null,
                    'bank_type' => $bankType,
                ]);
                $tentativas++;
                if ($tentativas < $maxTentativas) {
                    sleep(min($tentativas * 2, 10));
                }
            }

            if (!$sucesso && $tentativas >= $maxTentativas) {
                Log::error('Falha ao processar cobrança após 5 tentativas.', [
                    'entidade_id' => $entidade->id ?? null,
                    'valor' => $valor,
                    'emprestimo_id' => $this->emprestimo->id ?? null,
                    'bank_type' => $bankType,
                ]);
            }
        }
    }

    /**
     * Mesmos prefixos usados em EmprestimoController para cobranças XGate (quitação, mínimo, saldo).
     */
    private function montarReferenceIdCobranca($entidade): string
    {
        $id = $entidade->id;
        $ts = time();

        if ($entidade instanceof Parcela) {
            return $id . '_' . $ts;
        }
        if ($entidade instanceof Quitacao) {
            return 'quitacao_' . $id . '_' . $ts;
        }
        if ($entidade instanceof PagamentoMinimo) {
            return 'pagamento_minimo_' . $id . '_' . $ts;
        }
        if ($entidade instanceof PagamentoSaldoPendente) {
            return 'saldo_' . $id . '_' . $ts;
        }

        return 'cobranca_' . $id . '_' . $ts;
    }

    private function dueDateParaCobrancaXgate($entidade): ?string
    {
        if ($entidade instanceof Parcela && $entidade->venc_real) {
            $v = $entidade->venc_real;

            return method_exists($v, 'format')
                ? $v->format('Y-m-d')
                : date('Y-m-d', strtotime((string) $v));
        }

        return null;
    }

    private function documentoXgateParaCliente($cliente): string
    {
        $cnpj = preg_replace('/\D/', '', (string) ($cliente->cnpj ?? ''));
        if (strlen($cnpj) >= 14) {
            return 'cnpj';
        }

        return 'cpf';
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Job ProcessarPixJob falhou: ' . $exception->getMessage(), [
            'exception' => get_class($exception),
        ]);
    }

    function obterSaudacao()
    {
        $hora = date('H');
        $saudacoesManha = ['🌤️ Bom dia', '👋 Olá, bom dia', '🌤️ Tenha um excelente dia'];
        $saudacoesTarde = ['🌤️ Boa tarde', '👋 Olá, boa tarde', '🌤️ Espero que sua tarde esteja ótima'];
        $saudacoesNoite = ['🌤️ Boa noite', '👋 Olá, boa noite', '🌤️ Espero que sua noite esteja ótima'];

        if ($hora < 12) {
            return $saudacoesManha[array_rand($saudacoesManha)];
        } elseif ($hora < 18) {
            return $saudacoesTarde[array_rand($saudacoesTarde)];
        } else {
            return $saudacoesNoite[array_rand($saudacoesNoite)];
        }
    }

    public function envioMensagem($parcela)
    {
        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

        $saudacao = self::obterSaudacao();

        $parcelaPendente = self::encontrarPrimeiraParcelaPendente($parcela->emprestimo->parcelas);

        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
        $fraseInicial = "

Relatório de Parcelas Pendentes:

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$parcela->emprestimo->company->numero_contato}
";


        $frase = $saudacaoTexto . $fraseInicial;

        $telefoneCliente = "55" . $telefone;
        $company = $parcela->emprestimo->company;

        if (!is_null($company->token_api_wtz) && !is_null($company->instance_id)) {
            $this->wapiService->enviarMensagem($company->token_api_wtz, $company->instance_id, ["delayMessage" => 1, "phone" => $telefoneCliente, "message" => $frase]);
        }
    }

    public function envioMensagemVideoYoutube($parcela)
    {

        try {

            $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
            $pix = $parcela->chave_pix;

            if ($parcela->emprestimo->pagamentominimo) {
                $pix = $parcela->emprestimo->pagamentominimo->chave_pix;
            }

            $link = '';
            $base64 = '';

            if (count($parcela->emprestimo->parcelas) == 1) {
                $base64 = 'https://api.agecontrole.com.br/storage/audios/umaParcela.mp4'; // video sobre 1 parcela
            } else {
                $base64 = 'https://api.agecontrole.com.br/storage/audios/variasParcelas.mp4'; // video sobre mais de 1 parcela
            }

            $telefoneCliente = "55" . $telefone;
            $company = $parcela->emprestimo->company;

            if (!is_null($company->token_api_wtz) && !is_null($company->instance_id)) {
                $this->wapiService->enviarMensagemVideo($company->token_api_wtz, $company->instance_id, ["delayMessage" => 1, "phone" => $telefoneCliente, "video" => $base64]);
            }
        } catch (\Throwable $th) {
            Log::error('ProcessarPixJob envioMensagemVideoYoutube: ' . $th->getMessage(), [
                'emprestimo_id' => $parcela->emprestimo->id ?? null,
                'trace' => $th->getTraceAsString(),
            ]);
        }
    }

    public function envioMensagemVideo($parcela, $videoPath)
    {
        if (isset($parcela->emprestimo->company->whatsapp)) {

            try {

                $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        // Enviar o vídeo MP4 para o endpoint
                        $response = Http::timeout(60)->attach(
                            'arquivo', // Nome do campo no formulário
                            file_get_contents($videoPath), // Conteúdo do arquivo
                            'video.mp4' // Nome do arquivo enviado
                        )->post($parcela->emprestimo->company->whatsapp . '/enviar-pdf', [
                            'numero' => "55" . $telefone,
                        ]);
                        sleep(2);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }

    function encontrarPrimeiraParcelaPendente($parcelas)
    {
        foreach ($parcelas as $parcela) {
            if ($parcela->dt_baixa === '' || $parcela->dt_baixa === null) {
                return $parcela;
            }
        }

        return null;
    }

    public function envioMensagemANTIGO($parcela)
    {
        if (isset($parcela->emprestimo->company->whatsapp)) {
            try {
                $response = Http::timeout(30)->get($parcela->emprestimo->company->whatsapp . '/logar');

                if ($response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

                        $saudacao = self::obterSaudacao();

                        $parcelaPendente = self::encontrarPrimeiraParcelaPendente($parcela->emprestimo->parcelas);

                        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
                        $fraseInicial = "

Relatório de Parcelas Pendentes:

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$parcela->emprestimo->company->numero_contato}
";


                        $frase = $saudacaoTexto . $fraseInicial;

                        $data = [
                            "numero" => "55" . $telefone,
                            "mensagem" => $frase
                        ];

                        $response = Http::timeout(30)->asJson()->post($baseUrl, $data);
                        sleep(2);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }

    public function envioAudio($parcela)
    {
        if (isset($parcela->emprestimo->company->whatsapp)) {
            try {
                $response = Http::timeout(30)->get($parcela->emprestimo->company->whatsapp . '/logar');

                if ($response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-audio';


                        $data = [
                            "numero" => "55" . $telefone,
                            "tipo" => "msginicio",
                            "nomeCliente" => "Sistema"
                        ];

                        $response = Http::timeout(30)->asJson()->post($baseUrl, $data);
                        sleep(2);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }

    public function envioMensagemVideoYoutubeANTIGO($parcela)
    {
        if (isset($parcela->emprestimo->company->whatsapp)) {

            try {

                $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                if ($response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

                        $pix = $parcela->chave_pix;

                        if ($parcela->emprestimo->pagamentominimo) {
                            $pix = $parcela->emprestimo->pagamentominimo->chave_pix;
                        }

                        $link = '';

                        if (count($parcela->emprestimo->parcelas) == 1) {
                            $link = 'https://www.youtube.com/watch?v=AZrN_-fDU2Y'; // video sobre 1 parcela
                        } else {
                            $link = 'https://www.youtube.com/watch?v=_DU0IvNHe2Q'; // video sobre mais de 1 parcela
                        }


                        $data = [
                            "numero" => "55" . $telefone,
                            "mensagem" => $link
                        ];

                        $response = Http::timeout(30)->asJson()->post($baseUrl, $data);
                        sleep(2);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }

    public function envioMensagemVideoANTIGO($parcela, $videoPath)
    {
        if (isset($parcela->emprestimo->company->whatsapp)) {

            try {

                $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                if ($response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        // Enviar o vídeo MP4 para o endpoint
                        $response = Http::timeout(60)->attach(
                            'arquivo', // Nome do campo no formulário
                            file_get_contents($videoPath), // Conteúdo do arquivo
                            'video.mp4' // Nome do arquivo enviado
                        )->post($parcela->emprestimo->company->whatsapp . '/enviar-pdf', [
                            'numero' => "55" . $telefone,
                        ]);
                        sleep(2);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }
}
