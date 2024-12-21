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

        foreach ($this->emprestimo->parcelas as $parcela) {
            $response = $this->bcodexService->criarCobranca($parcela['valor'], $this->emprestimo->banco->document);

            if ($response->successful()) {
                $parcela['identificador'] = $response->json()['txid'];
                $parcela['chave_pix'] = $response->json()['pixCopiaECola'];
            }

            $parcela->save();
        }

        if ($this->emprestimo->pagamentominimo) {

            $response = $this->bcodexService->criarCobranca($this->emprestimo->pagamentominimo->valor, $this->emprestimo->banco->document);

            if ($response->successful()) {
                $this->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                $this->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                $this->emprestimo->pagamentominimo->save();
            }
        }

        if ($this->emprestimo->quitacao) {

            $response = $this->bcodexService->criarCobranca($this->emprestimo->quitacao->saldo, $this->emprestimo->banco->document);

            if ($response->successful()) {
                $this->emprestimo->quitacao->identificador = $response->json()['txid'];
                $this->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                $this->emprestimo->quitacao->save();
            }
        }

        $this->envioMensagem($this->emprestimo->parcelas[0]);

        $this->envioMensagemPix($this->emprestimo->parcelas[0]);


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

    public function envioMensagem($parcela){
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

Segue abaixo link para pagamento parcela diária e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}
";

$valorJuros = $parcelaPendente->saldo - $parcelaPendente->emprestimo->valor;
if(count($parcela->emprestimo->parcelas) == 1){
if(!$parcelaPendente->emprestimo->pagamentominimo){
$fraseInicial .= "Copie e cole abaixo a chave pix

Beneficiário: {$parcelaPendente->emprestimo->banco->info_recebedor_pix}

📲 Entre em contato pelo WhatsApp {$parcelaPendente->emprestimo->company->numero_contato}
";
}else{
$fraseInicial .= "
💸 Pagamento Total R$ {$parcelaPendente->saldo}

Pagamento mínimo - Juros R$ {$valorJuros}

Para pagamento de demais valores

";
}

}
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
    public function envioMensagemPix($parcela){
        if (isset($parcela->emprestimo->company->whatsapp)) {

            try {

                $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                if ($response->successful()) {
                    $r = $response->json();
                    if ($r['loggedIn']) {

                        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                        $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

                        $data = [
                            "numero" => "55" . $telefone,
                            "mensagem" => $parcela->chave_pix
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

    function encontrarPrimeiraParcelaPendente($parcelas) {

        foreach($parcelas as $parcela){
            if($parcela->dt_baixa === '' || $parcela->dt_baixa === null){
                return $parcela;
            }
        }

        return null;
    }
}
