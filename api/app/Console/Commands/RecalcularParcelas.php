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
    public function handle(): void
    {
        $inicioTotal = microtime(true); // Marca o início da execução total

        $this->info('Recalculando as Parcelas em Atrasos');
        $processo = strtoupper(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5));

        Log::info("PROCESSO N: $processo - Início do processo");

        $inicioQuery = microtime(true);
        $parcelasVencidas = Parcela::where('venc_real', '<', Carbon::now()->subDay())
            ->whereNull('dt_baixa')
            ->where(function ($query) {
                $query->whereNull('ult_dt_processamento_rotina')
                      ->orWhereDate('ult_dt_processamento_rotina', '!=', Carbon::today());
            })
            ->with('emprestimo')
            ->orderByDesc('id')
            ->take(300)
            ->get()
            ->filter(function ($parcela) {
                $protesto = optional($parcela->emprestimo)->protesto;
                return !$protesto || $protesto == 0;
            })
            ->values();
        $duracaoQuery = round(microtime(true) - $inicioQuery, 4);
        Log::info("PROCESSO N: $processo - Tempo para carregar parcelas vencidas: {$duracaoQuery}s");

        $hoje = Carbon::today();

        $inicioAtualizacao = microtime(true);
        foreach ($parcelasVencidas as $parcela) {
            if ($parcela->emprestimo) {
                $emprestimo = $parcela->emprestimo;

                if ($emprestimo->deve_cobrar_hoje !== $hoje->toDateString()) {
                    $emprestimo->deve_cobrar_hoje = $hoje;
                    $emprestimo->save();
                }
            }

            $parcela->ult_dt_processamento_rotina = $hoje;
            $parcela->save();
        }
        $duracaoAtualizacao = round(microtime(true) - $inicioAtualizacao, 4);
        Log::info("PROCESSO N: $processo - Tempo para atualizar parcelas: {$duracaoAtualizacao}s");

        $bcodexService = new BcodexService();

        $qtClientes = count($parcelasVencidas);
        Log::info("PROCESSO N: $processo - Processando $qtClientes clientes");

        foreach ($parcelasVencidas as $index => $parcela) {
            $inicioCliente = microtime(true);

            if ($parcela->emprestimo && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado") {
                $valorJuros = 0;
                $juros = $parcela->emprestimo->company->juros ?? 1;
                $valorJuros = (float) number_format($parcela->emprestimo->valor * ($juros  / 100), 2, '.', '');
                $novoValor = $valorJuros + $parcela->saldo;

                if (count($parcela->emprestimo->parcelas) == 1) {
                    $novoValor = $parcela->saldo + (1 * $parcela->saldo / 100);
                    $valorJuros = (1 * $parcela->saldo / 100);
                }

                if ($parcela->emprestimo->banco->wallet) {
                    if (!self::podeProcessarParcela($parcela)) {
                        continue;
                    }

                    $txId = $parcela->identificador ?: null;
                    Log::info("PROCESSO N: $processo - Recalculo: Alterando parcela {$parcela->id} (processando " . ($index + 1) . " de $qtClientes)");


                    $inicioPix = microtime(true);
                    $response = $bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $txId);
                    $duracaoPix = round(microtime(true) - $inicioPix, 4);

                    if ($response->successful()) {
                        $newTxId = $response->json()['txid'];
                        $parcela->saldo = $novoValor;
                        $parcela->venc_real = date('Y-m-d');
                        $parcela->atrasadas += 1;
                        $parcela->identificador = $newTxId;
                        $parcela->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->save();
                        Log::info("PROCESSO N: $processo - Parcela {$parcela->id} atualizada com sucesso em {$duracaoPix}s");
                    } else {
                        Log::info("PROCESSO N: $processo - Erro ao atualizar parcela {$parcela->id} em {$duracaoPix}s");
                    }
                }

                // Quitação
                if ($parcela->emprestimo->quitacao) {
                    $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                    $parcela->emprestimo->quitacao->save();

                    $txId = $parcela->emprestimo->quitacao->identificador ?: null;
                    $response = $bcodexService->criarCobranca($parcela->totalPendente(), $parcela->emprestimo->banco->document, $txId);

                    if ($response->successful()) {
                        $parcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                        $parcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->emprestimo->quitacao->save();
                    }
                }

                // Pagamento mínimo
                if ($parcela->emprestimo->pagamentominimo) {
                    $parcela->emprestimo->pagamentominimo->valor += $valorJuros;
                    $parcela->emprestimo->pagamentominimo->save();

                    $txId = $parcela->emprestimo->pagamentominimo->identificador ?: null;
                    $response = $bcodexService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $parcela->emprestimo->banco->document, $txId);

                    if ($response->successful()) {
                        $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                        $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                        $parcela->emprestimo->pagamentominimo->save();
                    }
                }

                // Pagamento saldo pendente
                if ($parcela->emprestimo->pagamentosaldopendente) {
                    $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->totalPendenteHoje();

                    if ($parcela->emprestimo->pagamentosaldopendente->valor > 0) {
                        $parcela->emprestimo->pagamentosaldopendente->save();

                        $txId = $parcela->emprestimo->pagamentosaldopendente->identificador ?: null;
                        $response = $bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $parcela->emprestimo->banco->document, $txId);

                        if ($response->successful()) {
                            $parcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentosaldopendente->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentosaldopendente->save();
                        }
                    } else {
                        Log::info("PROCESSO N: $processo - Saldo pendente zerado para parcela {$parcela->id}");
                    }
                }
            }

            $duracaoCliente = round(microtime(true) - $inicioCliente, 4);
            Log::info("PROCESSO N: $processo - Tempo para processar parcela {$parcela->id}: {$duracaoCliente}s");
        }

        $duracaoTotal = round(microtime(true) - $inicioTotal, 4);
        Log::info("PROCESSO N: $processo - Tempo total de execução: {$duracaoTotal}s");
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

    private static function podeProcessarParcela($parcela)
    {
        $parcelaPesquisa = Parcela::find($parcela->id);

        if ($parcelaPesquisa->dt_baixa !== null) {
            Log::info("Parcela {$parcela->id} já baixada, não será processada novamente.");
            return false;
        }

        return true;
    }
}
