<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CobrancaAutomaticaA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:AutomaticaA';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cobrança automatica das parcelas em atraso';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Realizando a Cobrança Automatica das Parcelas em Atrasos');

        $today = Carbon::today()->toDateString();
        $isHoliday = Feriado::where('data_feriado', $today)->exists();

        if ($isHoliday) {
            return 0;
        }

        $parcelas = Parcela::whereNull('dt_baixa')
            ->whereNull('valor_recebido_pix')
            ->whereNull('valor_recebido')
            ->whereDate('venc_real', $today)
            ->get()
            ->unique('emprestimo_id');

        foreach ($parcelas as $parcela) {
            $this->processarParcela($parcela);
        }

        return 0;
    }

    private function processarParcela($parcela)
    {
        if (!$this->deveProcessarParcela($parcela)) {
            return;
        }

        try {
            $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

            if ($response->successful() && $response->json()['loggedIn']) {
                $this->enviarMensagem($parcela);
            }
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    private function deveProcessarParcela($parcela)
    {
        return isset($parcela->emprestimo->company->whatsapp) &&
            $parcela->emprestimo->contaspagar &&
            $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado";
    }

    private function enviarMensagem($parcela)
    {
        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

        $saudacao = $this->obterSaudacao();
        $parcelaPendente = $this->encontrarPrimeiraParcelaPendente($parcela->emprestimo->parcelas);

        $mensagem = $this->montarMensagem($parcela, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Log::info('Cobranca', $data);

        Http::asJson()->post($baseUrl, $data);
        sleep(4);
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
