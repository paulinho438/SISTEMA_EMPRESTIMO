<?php

namespace App\Console\Commands;

use App\Jobs\EnviarMensagemWhatsApp;
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

        Log::info("Cobranca Automatica A inicio de rotina");

        $today = Carbon::today()->toDateString();
        $isHoliday = Feriado::where('data_feriado', $today)->exists();

        if ($isHoliday) {
            return 0;
        }

        $todayHoje = now();

        $parcelasQuery = Parcela::whereNull('dt_baixa')->with('emprestimo');

        if (($todayHoje->isSaturday() || $todayHoje->isSunday())) {
            $parcelasQuery->where('atrasadas', '>', 0);
        }

        $parcelasQuery->orderByDesc('id');
        $parcelas = $parcelasQuery->get();

        if (($todayHoje->isSaturday() || $todayHoje->isSunday())) {
            $parcelas = $parcelas->filter(function ($parcela) {
                $dataProtesto = optional($parcela->emprestimo)->data_protesto;

                if (!$dataProtesto) {
                    return true;
                }

                return !Carbon::parse($dataProtesto)->lte(Carbon::now()->subDays(1));
            });
        }

        if (!($todayHoje->isSaturday() || $todayHoje->isSunday())) {
            $parcelas = $parcelas->filter(function ($parcela) use ($todayHoje) {
                $emprestimo = $parcela->emprestimo;

                $deveCobrarHoje = $emprestimo &&
                    !is_null($emprestimo->deve_cobrar_hoje) &&
                    Carbon::parse($emprestimo->deve_cobrar_hoje)->isSameDay($todayHoje);

                $vencimentoHoje = $parcela->venc_real &&
                    Carbon::parse($parcela->venc_real)->isSameDay($todayHoje);

                return $deveCobrarHoje || $vencimentoHoje;
            });
        }

        // Remover duplicados e resetar índices
        $parcelas = $parcelas->unique('emprestimo_id')->values();

        $count = count($parcelas);
        Log::info("Cobranca Automatica A quantidade de clientes: {$count}");
        //$parcelas = Parcela::where('id', 23167)->get();
        foreach ($parcelas as $parcela) {
            $this->processarParcela($parcela);
        }
        Log::info("Cobranca Automatica A finalizada");
        return 0;
    }

    private function processarParcela($parcela)
    {
        if (!$this->deveProcessarParcela($parcela)) {
            return;
        }

        if ($this->emprestimoEmProtesto($parcela)) {
            return;
        }

        try {
            $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

            if ($response->successful() && $response->json()['loggedIn']) {
                $this->enviarMensagem($parcela);
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }

    private function deveProcessarParcela($parcela)
    {
        return isset($parcela->emprestimo->company->whatsapp) &&
            $parcela->emprestimo->contaspagar &&
            $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado";
    }

    private function emprestimoEmProtesto($parcela)
    {
        if (!$parcela->emprestimo || !$parcela->emprestimo->data_protesto) {
            return false;
        }

        return Carbon::parse($parcela->emprestimo->data_protesto)->lte(Carbon::now()->subDays(14));
    }

    private function enviarMensagem($parcela)
    {
        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $baseUrl = $parcela->emprestimo->company->whatsapp;



        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($parcela, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Http::asJson()->post("$baseUrl/enviar-mensagem", $data);
        Log::info("MENSAGEM ENVIADA: " . $telefone);
        sleep(4);
        if ($parcela->emprestimo->company->mensagem_audio) {
            if ($parcela->atrasadas > 0) {
                $baseUrl = $parcela->emprestimo->company->whatsapp;
                $tipo = "0";
                switch ($parcela->atrasadas) {
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

                if ($tipo != "0") {
                    $data2 = [
                        "numero" => "55" . $telefone,
                        "nomeCliente" => $parcela->emprestimo->client->nome_completo,
                        "tipo" => $tipo
                    ];

                    Http::asJson()->post("$baseUrl/enviar-audio", $data2);
                }
            }
        }

        //identificar se o emprestimo é mensal
        //identificar se é a primeira cobranca
        if (count($parcela->emprestimo->parcelas) == 1) {
            if ($parcela->atrasadas == 0) {
                $data3 = [
                    "numero" => "55" . $telefone,
                    "nomeCliente" => "Sistema",
                    "tipo" => "msginfo1"
                ];

                Http::asJson()->post("$baseUrl/enviar-audio", $data3);
            }
        }
    }

    private function montarMensagem($parcela, $saudacao)
    {
        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
        $fraseInicial = "

Relatório de Parcelas Pendentes:

⚠️ *sempre enviar o comprovante para ajudar na conferência não se esqueça*

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
