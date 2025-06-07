<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;

use App\Services\BcodexService;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\Log;

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

        Log::info("Recalculando as Parcelas em Atrasos");

        $parcelasVencidas = Parcela::where('venc_real', '<', Carbon::now()->subDay())
            ->whereNull('dt_baixa')
            ->whereDate('updated_at', '!=', Carbon::today())
            ->with('emprestimo')
            ->orderByDesc('id') // 👈 ordenação antes do get()
            ->get()
            ->filter(function ($parcela) {
                $dataProtesto = optional($parcela->emprestimo)->data_protesto;

                return $dataProtesto &&
                    Carbon::parse($dataProtesto)->lte(Carbon::now()->subDays(14));
            })
            ->values();

        $bcodexService = new BcodexService();

        $qtClientes = count($parcelasVencidas);
        Log::info("Processando $qtClientes clientes");

        // Faça algo com as parcelas vencidas, por exemplo, exiba-as
        foreach ($parcelasVencidas as $parcela) {

            if ($parcela->emprestimo && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado") {
                $valorJuros = 0;


                echo "<npre>" . $parcela->emprestimo->parcelas[0]->totalPendente() . "</pre>";

                $juros = $parcela->emprestimo->company->juros ?? 1;

                $valorJuros = (float) number_format($parcela->emprestimo->valor * ($juros  / 100), 2, '.', '');

                $novoValor = $valorJuros + $parcela->saldo;

                if (count($parcela->emprestimo->parcelas) == 1) {
                    $novoValor = $parcela->saldo + (1 * $parcela->saldo / 100);
                    $valorJuros = (1 * $parcela->saldo / 100);
                }



                if ($parcela->emprestimo->banco->wallet) {
                    $txId = $parcela->identificador ? $parcela->identificador : null;
                    echo "txId: $txId parcelaId: { $parcela->id }";
                    Log::info(message: "Alterando cobranca da parcela $parcela->id no valor de $parcela->saldo txid: $txId");
                    $response = $bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $txId);

                    if ($response->successful()) {
                        Log::info("Parcela alterada com sucesso");
                        $newTxId = $response->json()['txid'];
                        echo "sucesso txId: { $newTxId } parcelaId: { $parcela->id }";
                        $parcela->saldo = $novoValor;
                        $parcela->venc_real = date('Y-m-d');
                        $parcela->atrasadas = $parcela->atrasadas + 1;
                        $parcela->identificador = $response->json()['txid'];
                        $parcela->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->save();
                    } else {
                        Log::info("Não deu certo, parcela $parcela->id no valor de $parcela->saldo txid: $txId");
                        continue;
                    }
                }

                if ($parcela->emprestimo->quitacao) {

                    $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                    $parcela->emprestimo->quitacao->save();
                    $txId = $parcela->emprestimo->quitacao->identificador ? $parcela->emprestimo->quitacao->identificador : null;
                    $response = $bcodexService->criarCobranca($parcela->totalPendente(), $parcela->emprestimo->banco->document, $txId);
                    Log::info(message: "Alterando quitacao da parcela $parcela->id quitacao: {$parcela->emprestimo->quitacao->id} txid: $txId");
                    if ($response->successful()) {
                        Log::info('Quitacao alterada com sucesso');
                        $parcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                        $parcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                        $parcela->emprestimo->quitacao->save();
                    }
                }

                if ($parcela->emprestimo->pagamentominimo) {
                    $parcela->emprestimo->pagamentominimo->valor += $valorJuros;

                    $parcela->emprestimo->pagamentominimo->save();
                    $txId = $parcela->emprestimo->pagamentominimo->identificador ? $parcela->emprestimo->pagamentominimo->identificador : null;
                    $response = $bcodexService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $parcela->emprestimo->banco->document, $txId);
                    Log::info(message: "Alterando pagamento minimo da parcela $parcela->id no valor de {$parcela->emprestimo->pagamentominimo->valor} txid: $txId");

                    if ($response->successful()) {
                        Log::info(message: 'Pagamento minimo alterada com sucesso');
                        $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                        $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->emprestimo->pagamentominimo->save();
                    }
                }

                if ($parcela->emprestimo->pagamentosaldopendente) {

                    $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->totalPendenteHoje();

                    $parcela->emprestimo->pagamentosaldopendente->save();
                    $txId = $parcela->emprestimo->pagamentosaldopendente->identificador ? $parcela->emprestimo->pagamentosaldopendente->identificador : null;
                    $response = $bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $parcela->emprestimo->banco->document, $txId);
                    Log::info(message: "Alterando saldo pendente da parcela $parcela->id no valor de {$parcela->emprestimo->pagamentosaldopendente->valor} txid: $txId");
                    if ($response->successful()) {
                        Log::info(message: 'Saldo pendente alterada com sucesso');
                        $parcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'];
                        $parcela->emprestimo->pagamentosaldopendente->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->emprestimo->pagamentosaldopendente->save();
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
                } catch (\Exception $e) {
                    print_r($e->getMessage());
                }
            } else {
                echo "<pre>" . json_encode($pix, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";
            }
        } catch (EfiException $e) {
            print_r($e->code . "<br>");
            print_r($e->error . "<br>");
            print_r($e->errorDescription) . "<br>";
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    public function gerarPixQuitacao($dados)
    {

        $return = [];

        $caminhoAbsoluto = storage_path('app/public/documentos/' . $dados['banco']['certificado']);
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
                } catch (\Exception $e) {
                    print_r($e->getMessage());
                }
            } else {
                echo "<pre>" . json_encode($pix, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";
            }
        } catch (EfiException $e) {
            print_r($e->code . "<br>");
            print_r($e->error . "<br>");
            print_r($e->errorDescription) . "<br>";
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }

    private function emprestimoEmProtesto($parcela)
    {
        if (!$parcela->emprestimo || !$parcela->emprestimo->data_protesto) {
            return false;
        }

        return Carbon::parse($parcela->emprestimo->data_protesto)->lte(Carbon::now()->subDays(14));
    }
}
