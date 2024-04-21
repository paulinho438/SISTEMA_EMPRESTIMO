<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;

class CobrancaAutomatica extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:Automatica';

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

            $parcelas = Parcela::where('venc_real', '<=', Carbon::now())->where('dt_baixa', null)->where('atrasadas', '>', 0)->get();
            $r = [];
            foreach($parcelas as $parcela){
                if(isset($parcela->emprestimo->company->whatsapp)){
                    try {
                        $response = Http::get($parcela->emprestimo->company->whatsapp.'/logar');
                        if ($response->successful()) {
                            $r = $response->json();
                            if($r['loggedIn']){
                                $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                                $baseUrl = $parcela->emprestimo->company->whatsapp.'/enviar-mensagem';
                                $valor_acrecimo = ($parcela->saldo - $parcela->valor) / $parcela->atrasadas;
                                $ultima_parcela = $parcela->saldo - $valor_acrecimo;
                                $data = [
                                    "numero" => "55".$telefone,
                                    "mensagem" => '
    Bom dia, '.$parcela->emprestimo->client->nome_completo.'!

    Espero que esteja tudo bem com você. Verificamos em nosso sistema que a parcela '.$parcela->parcela.' no valor de R$ '.number_format($ultima_parcela, 2, ',', '.').' ainda não foi quitada. Para sua conveniência, encaminho abaixo o link atualizado para o pagamento, já incluindo os acréscimos de R$ '.number_format($valor_acrecimo, 2, ',', '.').' referente a juros.

    '.$parcela->chave_pix.'

    Estamos à disposição para qualquer esclarecimento que seja necessário.'
                                ];
                                $response = Http::asJson()->post($baseUrl, $data);
                                sleep(4);
                            }
                        }
                    } catch (\Throwable $th) {

                    }

                }
            }

        exit;
    }

}
