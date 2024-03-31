<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\Client;
use App\Models\Fornecedor;
use App\Models\Banco;
use App\Models\Juros;
use App\Models\Costcenter;
use App\Models\CustomLog;
use App\Models\Feriado;
use App\Models\User;
use App\Models\Contaspagar;
use App\Models\Contasreceber;
use App\Models\Movimentacaofinanceira;
use App\Traits\VerificarPermissao;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Ramsey\Uuid\Uuid;

use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Http\Resources\EmprestimoResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\BancosResource;
use App\Http\Resources\CostcenterResource;
use App\Http\Resources\FeriadoResource;
use App\Http\Resources\FornecedorResource;

use App\Jobs\gerarPixParcelas;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
class EmprestimoController extends Controller
{

    protected $custom_log;

    use VerificarPermissao;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id){
        return new EmprestimoResource(Emprestimo::find($id));
    }

    public function all(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Emprestimos',
            'operation' => 'index'
        ]);

        return EmprestimoResource::collection(Emprestimo::where('company_id', $request->header('company-id'))->orderBy('id', 'desc')->get());
    }

    public function recalcularParcelas(Request $r){

        $juros = Juros::value('juros');

        $parcelasVencidas = Parcela::where('venc_real', '<', Carbon::now())->where('dt_baixa', null)->get();

        // Faça algo com as parcelas vencidas, por exemplo, exiba-as
        foreach ($parcelasVencidas as $parcela) {

            if ($parcela->emprestimo) {

                $valorJuros = $parcela->emprestimo->valor * ($juros / 100);

                $novoValor = $valorJuros + $parcela->saldo;

                $parcela->saldo = $novoValor;
                $parcela->venc_real = date('Y-m-d');

                if($parcela->chave_pix){
                    $gerarPix = self::gerarPix([
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

                if($parcela->contasreceber){
                    $parcela->contasreceber->venc = $parcela->venc_real;
                    $parcela->contasreceber->valor = $parcela->saldo;
                    $parcela->contasreceber->save();
                }
                $parcela->save();

            }
        }

        return $parcelasVencidas;

    }

    public function testeBank() {
        $parcelas = Parcela::where('dt_baixa', null)->get();

        $primeiroRegistro = Parcela::where('dt_baixa', null)->orderBy('venc_real')->first();

        $ultimoRegistro = Parcela::where('dt_baixa', null)->orderBy('venc_real', 'desc')->first();

        $caminhoAbsoluto = storage_path('app/certificados/producao-526004-SISTEMA.p12');
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'client_id' => 'Client_Id_1effa929eeb769ab15d3ff65f2f63d93cf3c8959',
            'client_secret' => 'Client_Secret_ac6fbe6e7cf2c7273fff08cebfa9b63165a6b69b',
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            'timeout' => 30,
        ];

        $params = [
            "inicio" => $primeiroRegistro->venc_real."T00:00:00Z",
            "fim" => $ultimoRegistro->venc_real."T23:59:59Z",
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

            foreach($parcelas as $item){
                if (in_array($item->identificador, $arrayIdsLoc)) {
                    $editParcela = Parcela::find($item->id);
                    $editParcela->dt_baixa = date('Y-m-d');
                    if ($editParcela->contasreceber) {
                        $editParcela->contasreceber->status = 'Pago';
                        $editParcela->contasreceber->dt_baixa = $request->dt_baixa;
                        $editParcela->contasreceber->forma_recebto = 'PIX';
                        $editParcela->contasreceber->save();
                    }
                    $editParcela->save();
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

    public function efibank() {


        $caminhoAbsoluto = storage_path('app/certificados/producao-526004-SISTEMA.p12');
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'client_id' => 'Client_Id_1effa929eeb769ab15d3ff65f2f63d93cf3c8959',
            'client_secret' => 'Client_Secret_ac6fbe6e7cf2c7273fff08cebfa9b63165a6b69b',
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            'timeout' => 30,
        ];

        $params = [
            "txid" => Str::random(32)
        ];

        $body = [
            "calendario" => [
                "dataDeVencimento" => "2023-12-23",
                "validadeAposVencimento" => 0
            ],
            "devedor" => [
                "nome" => "Paulo Peixoto",
                "cpf" => "05546356154",
            ],
            "valor" => [
                "original" => "0.12",
                // "multa" => [
                //     "modalidade" => 2,
                //     "valorPerc" => "2.00"
                // ],
                // "juros" => [
                //     "modalidade" => 2,
                //     "valorPerc" => "0.30"
                // ],
                // "desconto" => [
                //     "modalidade" => 1,
                //     "descontoDataFixa" => [
                //         [
                //             "data" => "2024-10-15",
                //             "valorPerc" => "30.00"
                //         ],
                //         [
                //             "data" => "2024-11-15",
                //             "valorPerc" => "15.00"
                //         ],
                //         [
                //             "data" => "2024-12-15",
                //             "valorPerc" => "5.00"
                //         ]
                //     ]
                // ]
            ],
            "chave" => "21e1f495-b437-4795-b724-bc98464cf19e", // Pix key registered in the authenticated Efí account
            "solicitacaoPagador" => "Parcela 004 / 015",
            "infoAdicionais" => [
                [
                    "nome" => "Emprestimo",
                    "valor" => "R$ 1.000,00"
                ],
                [
                    "nome" => "Parcela",
                    "valor" => "002 / 030"
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

                try {
                    $qrcode = $api->pixGenerateQRCode($params);

                    echo "<b>Detalhes da cobrança:</b>";
                    echo "<pre>" . json_encode($pix, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";

                    echo "<b>QR Code:</b>";
                    echo "<pre>" . json_encode($qrcode, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";

                    echo "<b>Imagem:</b><br/>";
                    echo "<img src='" . $qrcode["imagemQrcode"] . "' />";
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

    public function feriados(Request $request){
        return FeriadoResource::collection(Feriado::where('company_id', $request->header('company-id'))->orderBy('id', 'desc')->get());
    }

    public function searchFornecedor(Request $request){

        return FornecedorResource::collection(Fornecedor::where("nome_completo", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchCliente(Request $request){

        return ClientResource::collection(Client::where("nome_completo", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchBanco(Request $request){

        return BancosResource::collection(Banco::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchCostcenter(Request $request){

        return CostcenterResource::collection(Costcenter::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchConsultor(Request $request){

        // return User::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get();
        return User::where("nome_completo", "LIKE", "%{$request->name}%")
            ->whereHas('groups', function ($query) {
                $query->where('name', 'Consultor');
            })
            ->whereHas('companies', function ($query) use ($request) {
                $query->where('id', $request->header('Company_id'));
            })
            ->get();
    }

    public function enviarPix($dados){


        $return = [];

        $caminhoAbsoluto = storage_path('app/certificados/producao-526004-SISTEMA.p12');
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'client_id' => 'Client_Id_1effa929eeb769ab15d3ff65f2f63d93cf3c8959',
            'client_secret' => 'Client_Secret_ac6fbe6e7cf2c7273fff08cebfa9b63165a6b69b',
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            'timeout' => 30,
        ];

        $params = [
            "idEnvio" => Str::random(35)
        ];

        $body = [
            "valor" => $dados['valor'],
            "pagador" => [
                "chave" => "21e1f495-b437-4795-b724-bc98464cf19e", // Pix key registered in the authenticated Efí account
                "infoPagador" => $dados['informacao']
            ],
            "favorecido" => [
                "chave" => $dados['pix_cliente'] // Type key: random, email, phone, cpf or cnpj
            ]

        ];

        try {
            $api = EfiPay::getInstance($options);
            $response = $api->pixSend($params, $body);

            return $response;

            return [
                'error_code' => null,
                'error' => null,
                'error_description' => null,
                'responde' => $response
            ];

        } catch (EfiException $e) {
            return [
                'error_code' => $e->code,
                'error' => $e->error,
                'error_description' => $e->errorDescription
            ];
        } catch (Exception $e) {
            print_r($e->getMessage());
        }

    }

    public function gerarPix($dados) {

        $return = [];

        $caminhoAbsoluto = storage_path('app/certificados/producao-526004-SISTEMA.p12');
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'client_id' => 'Client_Id_1effa929eeb769ab15d3ff65f2f63d93cf3c8959',
            'client_secret' => 'Client_Secret_ac6fbe6e7cf2c7273fff08cebfa9b63165a6b69b',
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
            "chave" => "21e1f495-b437-4795-b724-bc98464cf19e", // Pix key registered in the authenticated Efí account
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

    public function insert(Request $request){

        $array = ['error' => ''];

        $dados = $request->all();

        $emprestimoAdd = [];

        $emprestimoAdd['dt_lancamento'] = date('Y-m-d');
        $emprestimoAdd['valor'] = $dados['valor'];
        $emprestimoAdd['lucro'] = $dados['lucro'];
        $emprestimoAdd['juros'] = $dados['juros'];
        $emprestimoAdd['costcenter_id'] = $dados['costcenter']['id'];
        $emprestimoAdd['banco_id'] = $dados['banco']['id'];
        $emprestimoAdd['client_id'] = $dados['cliente']['id'];
        $emprestimoAdd['user_id'] = $dados['consultor']['id'];
        $emprestimoAdd['company_id'] = $request->header('Company_id');

        gerarPixParcelas::dispatch();


        $emprestimoAdd = Emprestimo::create($emprestimoAdd);

        if($emprestimoAdd){

            $contaspagar = [];
            $contaspagar['banco_id'] = $dados['banco']['id'];
            $contaspagar['emprestimo_id'] = $emprestimoAdd->id;
            $contaspagar['costcenter_id'] = $dados['costcenter']['id'];
            $contaspagar['status'] = 'Aguardando Pagamento';
            $contaspagar['tipodoc'] = 'Empréstimo';
            $contaspagar['lanc'] = date('Y-m-d');
            $contaspagar['venc'] = date('Y-m-d');
            $contaspagar['valor'] = $dados['valor'];
            $contaspagar['descricao'] = 'Empréstimo Nº '.$emprestimoAdd->id.' para '.$dados['cliente']['nome_completo'];
            $contaspagar['company_id'] = $request->header('Company_id');
            Contaspagar::create($contaspagar);

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $dados['banco']['id'];
            $movimentacaoFinanceira['company_id'] = $request->header('Company_id');
            $movimentacaoFinanceira['descricao'] = 'Empréstimo Nº '.$emprestimoAdd->id.' para '.$dados['cliente']['nome_completo'];
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $dados['valor'];

            Movimentacaofinanceira::create($movimentacaoFinanceira);

        }

        $pegarUltimaParcela = $dados['parcelas'];
        end($pegarUltimaParcela);
        $ultimaParcela = current($pegarUltimaParcela);

        foreach($dados['parcelas'] as $parcela){

            $addParcela = [];
            $addParcela['emprestimo_id']    = $emprestimoAdd->id;
            $addParcela['dt_lancamento']    = date('Y-m-d');
            $addParcela['parcela']          = $parcela['parcela'];
            $addParcela['valor']            = $parcela['valor'];
            $addParcela['saldo']            = $parcela['saldo'];
            $addParcela['venc']             = Carbon::createFromFormat('d/m/Y', $parcela['venc'])->format('Y-m-d');
            $addParcela['venc_real']        = Carbon::createFromFormat('d/m/Y', $parcela['venc_real'])->format('Y-m-d');

            $caminhoAbsoluto = storage_path('app/certificados/producao-526004-SISTEMA.p12');
            $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
            $options = [
                'client_id' => 'Client_Id_1effa929eeb769ab15d3ff65f2f63d93cf3c8959',
                'client_secret' => 'Client_Secret_ac6fbe6e7cf2c7273fff08cebfa9b63165a6b69b',
                'certificate' => $caminhoAbsoluto,
                'sandbox' => false,
                'timeout' => 30,
            ];

            $params = [
                "txid" => Str::random(32)
            ];

            $body = [
                "calendario" => [
                    "dataDeVencimento" => $addParcela['venc_real'],
                    "validadeAposVencimento" => 0
                ],
                "devedor" => [
                    "nome" => $dados['cliente']['nome_completo'],
                    "cpf" => str_replace(['-', '.'], '', $dados['cliente']['cpf']),
                ],
                "valor" => [
                    "original" => number_format(str_replace(',', '', $addParcela['valor']), 2, '.', ''),

                ],
                "chave" => "21e1f495-b437-4795-b724-bc98464cf19e", // Pix key registered in the authenticated Efí account
                "solicitacaoPagador" => "Parcela ". $addParcela['parcela'],
                "infoAdicionais" => [
                    [
                        "nome" => "Emprestimo",
                        "valor" => "R$ ".$emprestimoAdd->valor,
                    ],
                    [
                        "nome" => "Parcela",
                        "valor" => $addParcela['parcela']." / ". $ultimaParcela['parcela']
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

                    $addParcela['identificador'] = $pix["loc"]["id"];


                    try {
                        $qrcode = $api->pixGenerateQRCode($params);

                        $addParcela['chave_pix'] = $qrcode['linkVisualizacao'];

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


                $parcela = Parcela::create($addParcela);

                if($parcela) {
                    $contasreceber = [];
                    $contasreceber['company_id'] = $request->header('Company_id');
                    $contasreceber['parcela_id'] = $parcela->id;
                    $contasreceber['client_id'] = $dados['cliente']['id'];
                    $contasreceber['banco_id'] = $dados['banco']['id'];
                    $contasreceber['descricao'] = 'Parcela N° '.$parcela->parcela.' do Emprestimo N° '.$emprestimoAdd->id;
                    $contasreceber['status'] = 'Aguardando Pagamento';
                    $contasreceber['tipodoc'] = 'Empréstimo';
                    $contasreceber['lanc'] = $parcela->dt_lancamento;
                    $contasreceber['venc'] = $parcela->venc_real;
                    $contasreceber['valor'] = $parcela->valor;

                    Contasreceber::create($contasreceber);

                }
            }

        return $emprestimoAdd;



        return $array;
    }

    public function pagamentoTransferencia(Request $request, $id) {

        if(!$this->contem($request->header('Company_id'), auth()->user(), 'view_fornecedores_create')){
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' não tem permissão para autorizar o pagamento do emprestimo '.$id,
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Sem permissão para efetuar o pagamento.",
                "error" => ""
            ], Response::HTTP_FORBIDDEN);

        }

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $emprestimo = Emprestimo::find($id);

            if($emprestimo){
               $envio = self::enviarPix([
                    'valor'          =>  $emprestimo->valor,
                    'informacao'     =>  'Emprestimo de R$ '.$emprestimo->valor.' para o '.$emprestimo->client->nome_completo,
                    'pix_cliente'    =>  $emprestimo->client->cpf
               ]);

               if($envio['error_code'] != null){
                    return response()->json([
                        "message" => $envio['error_description'],
                        "error" => ''
                    ], Response::HTTP_FORBIDDEN);
               }

            }

            $emprestimo->contaspagar->status = 'Pagamento Efetuado';
            $emprestimo->contaspagar->dt_baixa = date('Y-m-d');
            $emprestimo->contaspagar->save();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' autorizou o pagamento do emprestimo '.$id. 'no valor de R$ '.$emprestimo->valor.' para o cliente '.$emprestimo->client->nome_completo,
                'operation' => 'edit'
            ]);

            DB::commit();

            return $array;

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function reprovarEmprestimo(Request $request, $id) {

        if(!$this->contem($request->header('Company_id'), auth()->user(), 'view_fornecedores_create')){
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' não tem permissão para autorizar o pagamento do emprestimo '.$id,
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Sem permissão para reprovar o pagamento.",
                "error" => ""
            ], Response::HTTP_FORBIDDEN);

        }

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $emprestimo = Emprestimo::find($id);

            if($emprestimo){
                $emprestimo->contaspagar->status = 'Empréstimo Reprovado';
                $emprestimo->contaspagar->save();
            }



            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' reprovou o pagamento do emprestimo '.$id. 'no valor de R$ '.$emprestimo->valor.' para o cliente '.$emprestimo->client->nome_completo,
                'operation' => 'edit'
            ]);

            DB::commit();

            return $array;

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao efetuar a reprovação do Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function update(Request $request, $id){


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'dt_lancamento' => 'required',
                'valor' => 'required',
                'lucro' => 'required',
                'juros' => 'required',
                'saldo' => 'required',
                'costcenter_id' => 'required',
                'banco_id' => 'required',
                'client_id' => 'required',
                'user_id' => 'required',
            ]);

            $dados = $request->all();
            if(!$validator->fails()){

                $EditEmprestimo = Emprestimo::find($id);

                $EditEmprestimo->dt_lancamento  = $dados['dt_lancamento'];
                $EditEmprestimo->valor          = $dados['valor'];
                $EditEmprestimo->lucro          = $dados['lucro'];
                $EditEmprestimo->juros          = $dados['juros'];
                $EditEmprestimo->saldo          = $dados['saldo'];
                $EditEmprestimo->costcenter_id  = $dados['costcenter_id'];
                $EditEmprestimo->banco_id       = $dados['banco_id'];
                $EditEmprestimo->client_id      = $dados['client_id'];
                $EditEmprestimo->user_id        = $dados['user_id'];

                $EditEmprestimo->save();

            } else {
                $array['error'] = $validator->errors()->first();
                return $array;
            }

            DB::commit();

            return $array;

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function cancelarBaixaManual(Request $request, $id){


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $editParcela = Parcela::find($id);
            $editParcela->saldo = $editParcela->valor;
            $editParcela->dt_baixa = null;
            if($editParcela->contasreceber){
                $editParcela->contasreceber->status = 'Aguardando Pagamento';
                $editParcela->contasreceber->dt_baixa = null;
                $editParcela->contasreceber->forma_recebto = null;
                $editParcela->contasreceber->save();
            }

            $editParcela->save();

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $editParcela->emprestimo->banco_id;
            $movimentacaoFinanceira['company_id'] = $editParcela->emprestimo->company_id;
            $movimentacaoFinanceira['descricao'] = 'Cancelamento da Baixa da parcela Nº '.$editParcela->parcela.' do emprestimo n° '.$editParcela->emprestimo_id;
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $editParcela->saldo;

            Movimentacaofinanceira::create($movimentacaoFinanceira);



            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' cancelou a baixa manual da parcela: '.$id,
                'operation' => 'index'
            ]);

            return response()->json(['message' => 'Baixa cancelada com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
    public function baixaManual(Request $request, $id){

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $editParcela = Parcela::find($id);
            $editParcela->dt_baixa = $request->dt_baixa;
            if ($editParcela->contasreceber) {
                $editParcela->contasreceber->status = 'Pago';
                $editParcela->contasreceber->dt_baixa = $request->dt_baixa;
                $editParcela->contasreceber->forma_recebto = 'PIX';
                $editParcela->contasreceber->save();
            }
            $editParcela->save();

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $editParcela->emprestimo->banco_id;
            $movimentacaoFinanceira['company_id'] = $editParcela->emprestimo->company_id;
            $movimentacaoFinanceira['descricao'] = 'Baixa manual da parcela Nº '.$editParcela->parcela.' do emprestimo n° '.$editParcela->emprestimo_id;
            $movimentacaoFinanceira['tipomov'] = 'E';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $editParcela->saldo;

            Movimentacaofinanceira::create($movimentacaoFinanceira);

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' realizou a baixa manual da parcela: '.$id,
                'operation' => 'index'
            ]);

            return response()->json(['message' => 'Baixa realizada com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Emprestimo::findOrFail($id);

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' deletou o Emprestimo: '.$id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Emprestimo excluída com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' tentou deletar o Emprestimo: '.$id.' ERROR: '.$e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
