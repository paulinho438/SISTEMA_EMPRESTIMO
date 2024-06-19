<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Banco;
use App\Models\Parcela;
use App\Models\Quitacao;
use App\Models\User;
use App\Models\Movimentacaofinanceira;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class EnvioManualQuitacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baixa:AutomaticaQuitacao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realizando as Baixas Automaticas Quitacao';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Realizando as Baixas');



        $bancos = Banco::all();
        $quitacao = new Quitacao;


        $parcela = new Parcela;


        foreach ($bancos as $banco) {
            // $parcelas = $parcela->where('dt_baixa', null)->whereDate('venc_real', '<=', Carbon::now()->toDateString())->whereHas('emprestimo', function ($query) use ($banco) {
            //     $query->whereHas('banco', function ($query) use ($banco) {
            //         $query->where('id', $banco->id);
            //     });
            // })->get();

            if ($banco['efibank'] == 1) {


                $quitacaoQry = $quitacao->where('dt_baixa', null)->whereHas('emprestimo', function ($query) use ($banco) {
                    $query->whereHas('banco', function ($query) use ($banco) {
                        $query->where('id', $banco->id);
                    });
                })->get();

                $primeiroRegistro = $parcela->where('dt_baixa', null)->whereHas('emprestimo', function ($query) use ($banco) {
                    $query->whereHas('banco', function ($query) use ($banco) {
                        $query->where('id', $banco->id);
                    });
                })->orderBy('venc')->first();

                $ultimoRegistro = $parcela->where('dt_baixa', null)->whereHas('emprestimo', function ($query) use ($banco) {
                    $query->whereHas('banco', function ($query) use ($banco) {
                        $query->where('id', $banco->id);
                    });
                })->orderBy('venc', 'desc')->first();

                $caminhoAbsoluto = storage_path('app/public/documentos/' . $banco['certificado']);
                $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
                $options = [
                    'client_id' => $banco['clienteid'],
                    'client_secret' => $banco['clientesecret'],
                    'certificate' => $caminhoAbsoluto,
                    'sandbox' => false,
                    'timeout' => 30,
                ];

                $params = [
                    "inicio" => $primeiroRegistro->venc_real . "T00:00:00Z",
                    "fim" => $ultimoRegistro->venc_real . "T23:59:59Z",
                    "status" => "CONCLUIDA", // "ATIVA","CONCLUIDA", "REMOVIDA_PELO_USUARIO_RECEBEDOR", "REMOVIDA_PELO_PSP"
                ];

                try {
                    $api = new EfiPay($options);
                    $response = $api->pixListDueCharges($params);

                    // Array para armazenar os valores de "id" de "loc"
                    $arrayIdsLoc = [];

                    // Loop através do array original
                    foreach ($response['cobs'] as $item) {
                        // Verifica se a chave "loc" existe e se a chave "id" está presente dentro de "loc"
                        if (isset($item['loc']['id'])) {
                            // Adiciona o valor de "id" ao novo array
                            $arrayIdsLoc[] = $item['loc']['id'];
                        }
                    }

                    foreach ($quitacaoQry as $item) {
                        if (in_array($item->identificador, $arrayIdsLoc)) {

                            $q = Quitacao::find($item->id);
                            $q->dt_baixa = date('Y-m-d');
                            $q->save();



                            foreach ($q->emprestimo->parcelas as $parcela) {
                                if ($parcela->dt_baixa == null) {
                                    $parcela->dt_baixa = date('Y-m-d');
                                    $parcela->save();

                                    $parcela->contasreceber->status = 'Pago';
                                    $parcela->contasreceber->dt_baixa = date('Y-m-d');
                                    $parcela->contasreceber->forma_recebto = 'PIX';
                                    $parcela->contasreceber->save();

                                }
                            }

                            $valor = $q->saldo;
                            $taxa = $q->emprestimo->banco->juros / 100;
                            $juros = $valor * $taxa;

                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $q->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $q->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = 'Baixa automática do emprestimo Nº ' . $q->emprestimo_id;
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $q->saldo - $juros;

                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            # ADICIONANDO O VALOR NO SALDO DO BANCO

                            $q->emprestimo->banco->saldo = $q->emprestimo->banco->saldo + $q->saldo - $juros;
                            $q->emprestimo->banco->save();

                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $q->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $q->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = 'Juros de ' . $q->emprestimo->banco->juros . '% referente a baixa automática do emprestimo Nº ' . $q->emprestimo_id;
                            $movimentacaoFinanceira['tipomov'] = 'S';
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $juros;

                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                        }
                    }


                    print_r("<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>");
                } catch (EfiException $e) {
                    print_r($e->code . "<br>");
                    print_r($e->error . "<br>");
                    print_r($e->errorDescription) . "<br>";
                } catch (Exception $e) {
                    print_r($e->getMessage());
                }

            }




        }

        exit;









    }
}
