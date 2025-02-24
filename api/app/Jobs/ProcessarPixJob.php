<?php

namespace App\Jobs;

use App\Models\Emprestimo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\BcodexService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessarPixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emprestimo;
    protected $bcodexService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Emprestimo $emprestimo, BcodexService $bcodexService)
    {
        $this->emprestimo = $emprestimo;
        $this->bcodexService = $bcodexService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $this->envioMensagemVideoYoutube($this->emprestimo->parcelas[0]);

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

            $this->envioMensagem($this->emprestimo->parcelas[0]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar PIX: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Processar cobrança com tentativas
     *
     * @param $entidade
     * @param $valor
     */
    private function processarCobrancaComTentativas($entidade, $valor)
    {
        $tentativas = 0;
        $maxTentativas = 5;
        $sucesso = false;

        while ($tentativas < $maxTentativas && !$sucesso) {
            try {
                $response = $this->bcodexService->criarCobranca($valor, $this->emprestimo->banco->document);

                if ($response->successful()) {
                    $entidade->identificador = $response->json()['txid'];
                    $entidade->chave_pix = $response->json()['pixCopiaECola'];
                    $entidade->save();
                    $sucesso = true;
                } else {
                    $tentativas++;
                }
            } catch (\Exception $e) {
                Log::error('Erro ao processar cobrança: ' . $e->getMessage());
                $tentativas++;
            }

            if (!$sucesso && $tentativas >= $maxTentativas) {
                // Armazenar que não deu certo após 5 tentativas
                Log::error('Falha ao processar cobrança após 5 tentativas.');
                // Você pode adicionar lógica adicional aqui para marcar o pagamento como falhado no banco de dados, se necessário
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // Registrar informações adicionais quando o job falha
        Log::error('Job ProcessarPixJob falhou: ' . $exception->getMessage());
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
        if (isset($parcela->emprestimo->company->whatsapp)) {
            try {
                $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

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

                        $response = Http::asJson()->post($baseUrl, $data);
                        sleep(8);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }

    public function envioMensagemVideoYoutube($parcela)
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

                        $response = Http::asJson()->post($baseUrl, $data);
                        sleep(8);
                    }
                }
            } catch (\Throwable $th) {
                dd($th);
            }
        }
    }

    public function envioMensagemVideo($parcela, $videoPath)
    {
        if (isset($parcela->emprestimo->company->whatsapp)) {

            try {

                $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                if ($response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        // Enviar o vídeo MP4 para o endpoint
                        $response = Http::attach(
                            'arquivo', // Nome do campo no formulário
                            file_get_contents($videoPath), // Conteúdo do arquivo
                            'video.mp4' // Nome do arquivo enviado
                        )->post($parcela->emprestimo->company->whatsapp . '/enviar-pdf', [
                            'numero' => "55" . $telefone,
                        ]);
                        sleep(8);
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
}
