<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;

use App\Services\BcodexService;

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

    protected $bcodexService;

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

        $bcodexService = new BcodexService();

        // Faça algo com as parcelas vencidas, por exemplo, exiba-as
        foreach ($parcelasVencidas as $parcela) {

            if ($parcela->emprestimo && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado") {

                if ($parcela->emprestimo->pagamentominimo) {
                    $parcela->saldo += $parcela->emprestimo->juros * $parcela->saldo / 100;;
                    $parcela->venc_real = date('Y-m-d');
                    $parcela->atrasadas = $parcela->atrasadas + 1;

                    $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document);

                    if ($response->successful()) {
                        $parcela->identificador = $response->json()['txid'];
                        $parcela->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->save();
                    }

                    $parcela->save();

                    if ($parcela->emprestimo->quitacao && $parcela->emprestimo->quitacao->chave_pix) {

                        $response = $this->bcodexService->criarCobranca($parcela->totalPendente(), $parcela->emprestimo->banco->document);

                        if ($response->successful()) {
                            $parcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                            $parcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                            $parcela->emprestimo->quitacao->save();
                        }
                    }

                    if ($parcela->emprestimo->pagamentominimo->chave_pix) {

                        $parcela->emprestimo->pagamentominimo->valor = $juros;

                        $parcela->emprestimo->pagamentominimo->save();

                        $response = $this->bcodexService->criarCobranca($juros, $parcela->emprestimo->banco->document);

                        if ($response->successful()) {
                            $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentominimo->save();
                        }
                    }

                } else {

                    echo "<npre>" . $parcela->emprestimo->parcelas[0]->totalPendente() . "</pre>";

                    $valorJuros = (float) number_format($parcela->emprestimo->valor * ($juros / 100), 2, '.', '');

                    $novoValor = $valorJuros + $parcela->saldo;

                    $parcela->saldo = $novoValor;
                    $parcela->venc_real = date('Y-m-d');
                    $parcela->atrasadas = $parcela->atrasadas + 1;

                    $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document);

                    if ($response->successful()) {
                        $parcela->identificador = $response->json()['txid'];
                        $parcela->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->save();
                    }

                    $parcela->save();

                    if ($parcela->emprestimo->quitacao && $parcela->emprestimo->quitacao->chave_pix) {

                        $response = $this->bcodexService->criarCobranca($parcela->totalPendente(), $parcela->emprestimo->banco->document);

                        if ($response->successful()) {
                            $parcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                            $parcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                            $parcela->emprestimo->quitacao->save();
                        }
                    }

                    if ($parcela->emprestimo->pagamentominimo->chave_pix) {

                        $parcela->emprestimo->pagamentominimo->valor = $juros;

                        $parcela->emprestimo->pagamentominimo->save();

                        $response = $this->bcodexService->criarCobranca($juros, $parcela->emprestimo->banco->document);

                        if ($response->successful()) {
                            $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentominimo->save();
                        }
                    }
                }
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
                "validadeAposVencimento" => 10
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
