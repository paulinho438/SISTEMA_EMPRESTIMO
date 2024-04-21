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

use Illuminate\Support\Facades\Http;

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

    public function gerarPix($dados) {

        $return = [];

        $caminhoAbsoluto = storage_path('app/public/documentos/' . $dados['banco']['certificado']);
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'client_id' => $dados['banco']['client_id'],
            'client_secret' => $dados['banco']['client_secret'],
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            'timeout' => 30,
        ];

        $params = [
            "txid" => Str::random(32)
        ];

        $body = [
            "calendario" => [
                "dataDeVencimento" => $dados['parcela']['venc_real'],
                "validadeAposVencimento" => 0
            ],
            "devedor" => [
                "nome" => $dados['cliente']['nome_completo'],
                "cpf" => str_replace(['-', '.'], '', $dados['cliente']['cpf']),
            ],
            "valor" => [
                "original" => number_format(str_replace(',', '', $dados['parcela']['valor']), 2, '.', ''),

            ],
            "chave" => $dados['banco']['chave'], // Pix key registered in the authenticated Efí account
            "solicitacaoPagador" => "Parcela ". $dados['parcela']['parcela'],
            "infoAdicionais" => [
                [
                    "nome" => "Emprestimo",
                    "valor" => "R$ ".$dados['parcela']['valor'],
                ],
                [
                    "nome" => "Parcela",
                    "valor" => $dados['parcela']['parcela']
                ]
            ]
        ];

        try {
            $api = new EfiPay($options);
            $pix = $api->pixCreateDueCharge($params, $body);


            if ($pix["txid"]) {
                $params = [
                    "id" => $pix["loc"]["id"]
                ];

                $return['identificador'] = $pix["loc"]["id"];


                try {
                    $qrcode = $api->pixGenerateQRCode($params);

                    $return['chave_pix'] = $qrcode['linkVisualizacao'];

                    return $return;

                } catch (EfiException $e) {
                    print_r($e->code . "<br>");
                    print_r($e->error . "<br>");
                    print_r($e->errorDescription) . "<br>";
                } catch (Exception $e) {
                    print_r($e->getMessage());
                }
            } else {
                echo "<pre>" . json_encode($pix, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";
            }
        } catch (EfiException $e) {
            print_r($e->code . "<br>");
            print_r($e->error . "<br>");
            print_r($e->errorDescription) . "<br>";
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}
