<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use App\Models\CustomLog;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;

class RecalcularParcelas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recalcular:Parcelas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcular Parcelas em atrasos';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Recalculando as Parcelas em Atrasos');

        $juros = Juros::value('juros');

        $parcelasVencidas = Parcela::where('venc_real', '<', Carbon::now()->subDay())->where('dt_baixa', null)->get();

        // Faça algo com as parcelas vencidas, por exemplo, exiba-as
        foreach ($parcelasVencidas as $parcela) {

            if ($parcela->emprestimo) {

                $valorJuros = $parcela->emprestimo->valor * ($juros / 100);


                $novoValor = $valorJuros + $parcela->saldo;

                $parcela->saldo = $novoValor;
                $parcela->venc_real = date('Y-m-d');
                $parcela->atrasadas = $parcela->atrasadas + 1;

                if ($parcela->chave_pix) {
                    $gerarPix = self::gerarPix(
                        [
                            'banco' => [
                                'client_id' => $parcela->emprestimo->banco->clienteid,
                                'client_secret' => $parcela->emprestimo->banco->clientesecret,
                                'certificado' => $parcela->emprestimo->banco->certificado,
                                'chave' => $parcela->emprestimo->banco->chavepix,
                            ],
                            'parcela' => [
                                'parcela' => $parcela->parcela,
                                'valor' => $novoValor,
                                'venc_real' => date('Y-m-d'),
                            ],
                            'cliente' => [
                                'nome_completo' => $parcela->emprestimo->client->nome_completo,
                                'cpf' => $parcela->emprestimo->client->cpf
                            ]
                        ]
                    );

                    $parcela->identificador = $gerarPix['identificador'];
                    $parcela->chave_pix = $gerarPix['chave_pix'];

                }

                if ($parcela->emprestimo->quitacao->chave_pix) {

                    $parcela->emprestimo->quitacao->valor = $parcela->emprestimo->quitacao->valor + $valorJuros;
                    $parcela->emprestimo->quitacao->saldo = $parcela->emprestimo->quitacao->saldo + $valorJuros;
                    $parcela->emprestimo->quitacao->save();

                    $gerarPixQuitacao = self::gerarPixQuitacao(
                        [
                            'banco' => [
                                'client_id' => $parcela->emprestimo->banco->clienteid,
                                'client_secret' => $parcela->emprestimo->banco->clientesecret,
                                'certificado' => $parcela->emprestimo->banco->certificado,
                                'chave' => $parcela->emprestimo->banco->chavepix,
                            ],
                            'parcela' => [
                                'parcela' => $parcela->parcela,
                                'valor' => $parcela->emprestimo->quitacao->saldo,
                                'venc_real' => date('Y-m-d'),
                            ],
                            'cliente' => [
                                'nome_completo' => $parcela->emprestimo->client->nome_completo,
                                'cpf' => $parcela->emprestimo->client->cpf
                            ]
                        ]
                    );

                    $parcela->emprestimo->quitacao->identificador = $gerarPixQuitacao['identificador'];
                    $parcela->emprestimo->quitacao->chave_pix = $gerarPixQuitacao['chave_pix'];

                    $parcela->emprestimo->quitacao->save();

                }

                if ($parcela->contasreceber) {
                    $parcela->contasreceber->venc = $parcela->venc_real;
                    $parcela->contasreceber->valor = $parcela->saldo;
                    $parcela->contasreceber->save();
                }
                $parcela->save();


            }
        }

        exit;
    }

    public function gerarPix($dados)
    {

        $return = [];

        $caminhoAbsoluto = storage_path('app/public/documentos/' . $dados['banco']['certificado']);
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'clientId' => $dados['banco']['client_id'],
            'clientSecret' => $dados['banco']['client_secret'],
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            "debug" => false,
            'timeout' => 60,
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
            "solicitacaoPagador" => "Parcela " . $dados['parcela']['parcela'],
            "infoAdicionais" => [
                [
                    "nome" => "Emprestimo",
                    "valor" => "R$ " . $dados['parcela']['valor'],
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

    public function gerarPixQuitacao($dados)
    {

        $return = [];

        $caminhoAbsoluto = storage_path('app/public/documentos/' . $dados['banco']['certificado']);
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'clientId' => $dados['banco']['client_id'],
            'clientSecret' => $dados['banco']['client_secret'],
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            "debug" => false,
            'timeout' => 60,
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
            "solicitacaoPagador" => "Parcela " . $dados['parcela']['parcela'],
            "infoAdicionais" => [
                [
                    "nome" => "Emprestimo",
                    "valor" => "R$ " . $dados['parcela']['valor'],
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
