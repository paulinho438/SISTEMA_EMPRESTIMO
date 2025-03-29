<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarMensagemWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $parcela;
    public function __construct($parcela)
    {
        $this->parcela = $parcela;
    }

    public function handle()
    {
        $telefone = preg_replace('/\D/', '', $this->parcela->emprestimo->client->telefone_celular_1);
        $baseUrl = $this->parcela->emprestimo->company->whatsapp;

        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($this->parcela, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Log::info('Cobranca Async', $data);

        Http::asJson()->post("$baseUrl/enviar-mensagem", $data);

        if($this->parcela->atrasadas > 0) {
            $this->enviarMensagemComAudio($this->parcela);
        }
    }

    public function enviarMensagemComAudio($parcela){
        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $baseUrl = $parcela->emprestimo->company->whatsapp;
        $tipo = "0";
        switch ($parcela->atrasada) {
            case 2:
                $tipo = "1.1";
                break;
            case 4:
                $tipo = "2.1";
                break;
            case 6:
                $tipo = "3.1";
                break;
            case 8:
                $tipo = "4.1";
                break;
            case 10:
                $tipo = "5.1";
                break;
            case 15:
                $tipo = "6.1";
                break;
        }

        if($tipo <> "0"){
            $data = [
                "numero" => "55" . $telefone,
                "nomeCliente" => $parcela->emprestimo->client->nome_completo,
                "tipo" => "1.1"
            ];

            Log::info("Cobranca AUDIO Async: tipo: $tipo", $data);

            Http::asJson()->post("$baseUrl/enviar-audio", $data);
        }
    }
    private function montarMensagem($parcela, $saudacao)
    {
        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
        $fraseInicial = "

Relatório de Parcelas Pendentes:

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$parcela->emprestimo->company->numero_contato}
";
        return $saudacaoTexto . $fraseInicial;
    }

    private function obterSaudacao()
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

    private function encontrarPrimeiraParcelaPendente($parcelas)
    {
        foreach ($parcelas as $parcela) {
            if (is_null($parcela->dt_baixa)) {
                return $parcela;
            }
        }

        return null;
    }
}

