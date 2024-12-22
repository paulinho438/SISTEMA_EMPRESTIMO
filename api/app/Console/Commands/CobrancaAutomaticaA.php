<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;

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

        $mensagem = $this->montarMensagem($parcela, $parcelaPendente, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Http::asJson()->post($baseUrl, $data);
        sleep(8);
    }

    private function montarMensagem($parcela, $parcelaPendente, $saudacao)
    {
        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
        $fraseInicial = "

Relatório de Parcelas Pendentes:

Segue abaixo link para pagamento parcela diária e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}
";

        $valorJuros = $parcelaPendente->saldo - $parcelaPendente->emprestimo->valor;

        if (count($parcela->emprestimo->parcelas) == 1) {
            if (!$parcelaPendente->emprestimo->pagamentominimo) {
                $fraseInicial .= "Copie e cole abaixo a chave pix

Beneficiário: {$parcelaPendente->emprestimo->banco->info_recebedor_pix}
Chave pix: {$parcela->emprestimo->banco->chavepix}

📲 Entre em contato pelo WhatsApp {$parcelaPendente->emprestimo->company->numero_contato}
";
            } else {
                $fraseInicial .= "
💸 Pagamento Total R$ {$parcelaPendente->saldo}

Pagamento mínimo - Juros R$ {$valorJuros}

Para pagamento de demais valores
";
            }
        }

        if ($parcelaPendente != null && $parcelaPendente->chave_pix != '') {
            $fraseInicial .= "Copie e cole abaixo a chave pix e faça o pagamento de R$ {$parcelaPendente->saldo} referente a parcela do dia:

{$parcelaPendente->chave_pix}

📲 Para mais informações WhatsApp {$parcelaPendente->emprestimo->company->numero_contato}
";
        } else if (count($parcela->emprestimo->parcelas) > 1) {
            $fraseInicial .= "Copie e cole abaixo a chave pix e faça o pagamento referente ao saldo pendente de R$ {$parcelaPendente->totalPendenteHoje()}:

Beneficiário: {$parcelaPendente->emprestimo->banco->info_recebedor_pix}
Chave pix: {$parcela->emprestimo->banco->chavepix}
";
        }

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
