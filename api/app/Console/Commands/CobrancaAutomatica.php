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

                                // Obtenha a saudação baseada na hora atual
                                $saudacao = obterSaudacao();

                                $data = [
                                    "numero" => "55" . $telefone,
                                    "mensagem" => '
                                    ' . $saudacao . ', ' . $parcela->emprestimo->client->nome_completo . '!

                                    Espero que você esteja bem. Gostaríamos de informá-lo que a parcela ' . $parcela->parcela . ' no valor de R$ ' . number_format($ultima_parcela, 2, ',', '.') . ' ainda não foi quitada. Para sua conveniência, segue abaixo o link atualizado para o pagamento, incluindo os acréscimos de R$ ' . number_format($valor_acrecimo, 2, ',', '.') . ' referentes a juros.

                                    Chave PIX: ' . $parcela->chave_pix . '

                                    Caso tenha alguma dúvida ou precise de mais informações, estamos à disposição para ajudá-lo.

                                    Atenciosamente,
                                    RJ EMPRESTIMOS'
                                ];
                                $response = Http::asJson()->post($baseUrl, $data);
                                sleep(8);
                            }
                        }
                    } catch (\Throwable $th) {

                    }

                }
            }

        exit;
    }

    function obterSaudacao()
    {
        $hora = date('H');
        $saudacoesManha = ['Bom dia', 'Olá, bom dia', 'Tenha um excelente dia'];
        $saudacoesTarde = ['Boa tarde', 'Olá, boa tarde', 'Espero que sua tarde esteja ótima'];
        $saudacoesNoite = ['Boa noite', 'Olá, boa noite', 'Espero que sua noite esteja ótima'];

        if ($hora < 12) {
            return $saudacoesManha[array_rand($saudacoesManha)];
        } elseif ($hora < 18) {
            return $saudacoesTarde[array_rand($saudacoesTarde)];
        } else {
            return $saudacoesNoite[array_rand($saudacoesNoite)];
        }
    }

}
