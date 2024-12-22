<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;
use App\Models\BotaoCobranca;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;

class CobrancaAutomaticaBBotao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:AutomaticaBBotao';

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

        $presseds = BotaoCobranca::where('is_active', true)->where('click_count', 2)->get();

        foreach ($presseds as $pressed) {
            $pressed->update([
                'is_active' => false
            ]);

            $today = Carbon::today()->toDateString();
            // Verificando se hoje é um feriado
            $isHoliday = Feriado::where('data_feriado', $today)->exists();

            $parcelas = collect(); // Coleção vazia se hoje for um feriado

            if (!$isHoliday) {
                $parcelas = Parcela::where('dt_baixa', null)
                    ->whereNull('valor_recebido_pix')
                    ->whereNull('valor_recebido')
                    ->whereDate('venc_real', $today)
                    ->whereHas('emprestimo', function ($query) use ($pressed) {
                        $query->where('company_id', $pressed->company_id);
                    })
                    ->get()->unique('emprestimo_id');
            }



            $r = [];
            foreach ($parcelas as $parcela) {

                if ($parcela->emprestimo->banco->wallet == 0) {
                    return;
                }

                if (isset($parcela->emprestimo->company->whatsapp) && $parcela->emprestimo->contaspagar && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado") {

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

🤷‍♂️ Não identificamos seu pagamento, evite multas!

Segue abaixo link para pagamento parcela diária e acesso todo o histórico de parcelas:

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

            exit;
        }
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

    function encontrarPrimeiraParcelaPendente($parcelas) {

        foreach($parcelas as $parcela){
            if($parcela->dt_baixa === '' || $parcela->dt_baixa === null){
                return $parcela;
            }
        }

        return null;
    }
}
