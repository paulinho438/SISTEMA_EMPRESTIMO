<?php

namespace App\Console\Commands;

use App\Jobs\EnviarMensagemWhatsApp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EnvioMensagemRenovacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rotina:envioMensagemRenovacao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio de mensagem renovação de empréstimos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {

        Log::info("Inicio de rotina de envio de mensagem de renovação");

        $emprestimos = Emprestimo::withCount([
            'parcelas as total_parcelas',
            'parcelas as parcelas_baixadas_count' => function ($query) {
                $query->whereNotNull('dt_baixa');
            }
        ])
        ->whereNull('dt_envio_mensagem_renovacao')
        ->whereDoesntHave('parcelas', function ($query) {
            $query->where('atrasadas', '>', 0);
        })
        ->havingRaw('parcelas_baixadas_count = total_parcelas * 0.8')
        ->get();

        //$parcelas = Parcela::where('id', 23167)->get();
        foreach ($emprestimos as $emprestimo) {
            $this->processarEmprestimo($emprestimo);
        }
        Log::info("Rotina envio de mensagem finalizada");
    }

    private function processarEmprestimo($emprestimo)
    {

        try {
            $response = Http::get($emprestimo->company->whatsapp . '/logar');

            if ($response->successful() && $response->json()['loggedIn']) {
                $this->enviarMensagem($emprestimo);

                $emprestimo->dt_envio_mensagem_renovacao = now();
                $emprestimo->save();
                Log::info("Mensagem de renovação enviada para o empréstimo ID: {$emprestimo->id}");
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }
    private function enviarMensagem($emprestimo)
    {
        $telefone = preg_replace('/\D/', '', $emprestimo->client->telefone_celular_1);
        $baseUrl = $emprestimo->company->whatsapp;



        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($emprestimo, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Http::asJson()->post("$baseUrl/enviar-mensagem", $data);
        Log::info("MENSAGEM ENVIADA: " . $telefone);
    }

    private function montarMensagem($emprestimo, $saudacao)
    {
        $saudacaoTexto = "{$saudacao}, " . $emprestimo->client->nome_completo . "!";
        $fraseInicial = "

Informo que o seu empréstimo está com 80% das parcelas pagas, ou seja, já pode ser renovado.

Para renovar, basta enviar mensagem para a nossa equipe.

O valor liberado para renovação é de R$ e o saldo atual pendente é de R$ , a renovação será feita com o saldo atual pendente.
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
}
