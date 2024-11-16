<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\ParcelaExtornox;
use App\Models\Quitacao;
use App\Models\PagamentoMinimo;
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

use App\Services\BcodexService;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use App\Jobs\ProcessarPixJob;

use DateTime;

use Ramsey\Uuid\Uuid;

use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Http\Resources\EmprestimoResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ParcelaResource;
use App\Http\Resources\BancosComSaldoResource;
use App\Http\Resources\CostcenterResource;
use App\Http\Resources\FeriadoEmprestimoResource;
use App\Http\Resources\FornecedorResource;

use App\Jobs\gerarPixParcelas;
use App\Models\ParcelaExtorno;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Http;

class EmprestimoController extends Controller
{

    protected $custom_log;

    protected $bcodexService;


    use VerificarPermissao;



    public function __construct(Customlog $custom_log, BcodexService $bcodexService)
    {
        $this->custom_log = $custom_log;
        $this->bcodexService = $bcodexService;
    }

    public function gerarCobranca(Request $request)
    {


        $response = $this->bcodexService->criarCobranca(18.00, '55439708000135');

        // Retorna a resposta da API externa
        if ($response->successful()) {
            $response->json()['txid'];
            return response()->json($response->json(), 201);
        }

        // Retorna erro caso a API externa retorne falha
        return response()->json([
            'error' => 'Erro ao criar a cobrança',
            'details' => $response->json(),
        ], $response->status());
    }

    public function id(Request $r, $id)
    {
        return new EmprestimoResource(Emprestimo::find($id));
    }

    public function all(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Emprestimos',
            'operation' => 'index'
        ]);

        return EmprestimoResource::collection(Emprestimo::where('company_id', $request->header('company-id'))->orderBy('id', 'desc')->get());
    }

    public function cobrancaAutomatica()
    {

        // Obtendo a data de hoje no formato YYYY-MM-DD
        $today = Carbon::today()->toDateString();

        // Verificando se hoje é um feriado
        $isHoliday = Feriado::where('data_feriado', $today)->exists();

        $parcelas = collect(); // Coleção vazia se hoje for um feriado

        if (!$isHoliday) {
            $parcelas = Parcela::where('dt_baixa', null)
                ->get()
                ->unique('emprestimo_id');
        }

        return $parcelas;
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

    public function recalcularParcelas(Request $r)
    {

        $juros = Juros::value('juros');

        $parcelasVencidas = Parcela::where('venc_real', '<', Carbon::now()->subDay())->where('dt_baixa', null)->get();

        return $parcelasVencidas;
    }

    public function parcelasPendentesParaHoje(Request $request)
    {
        $parcelas = collect();

        $today = Carbon::today()->toDateString();

        return EmprestimoResource::collection(
            Emprestimo::whereHas('parcelas', function ($query) use ($today, $request) {
                $query->where('dt_baixa', null)
                    ->where('venc_real', $today)
                    ->where('valor_recebido_pix', null)
                    ->whereHas('emprestimo', function ($query) use ($request) {
                        $query->where('company_id', $request->header('company-id'));
                    });
            })->get()
        );
    }

    public function parcelasParaExtorno(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $extorno = ParcelaExtorno::where('venc_real', $today)->get()->unique('hash_extorno');

        $parcelas = [];

        foreach ($extorno as $ext) {
            $parcelaResource = new ParcelaResource($ext->parcela_associada);
            $parcelaArray = $parcelaResource->resolve(); // Converte para array usando resolve
            $parcelaArray['saldo_correto'] = $ext->saldo - $ext->parcela_associada->saldo; // Adiciona o campo saldo_correto
            $parcelaArray['updated_at'] = $ext->parcela_associada->updated_at; // Adiciona o campo updated_at
            $parcelas[] = $parcelaArray;
        }

        // Ordenar as parcelas pelo campo updated_at do mais atual para o menos atual
        $parcelas = collect($parcelas)->sortByDesc(function ($parcela) {
            return $parcela['updated_at'];
        })->values()->all();

        return $parcelas;
    }


    public function feriados(Request $request)
    {
        return FeriadoEmprestimoResource::collection(Feriado::where('company_id', $request->header('company-id'))->orderBy('id', 'desc')->get());
    }

    public function searchFornecedor(Request $request)
    {

        return FornecedorResource::collection(Fornecedor::where("nome_completo", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchCliente(Request $request)
    {

        return ClientResource::collection(Client::where("nome_completo", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchBanco(Request $request)
    {

        return BancosComSaldoResource::collection(Banco::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchCostcenter(Request $request)
    {

        return CostcenterResource::collection(Costcenter::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }

    public function searchConsultor(Request $request)
    {

        // return User::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get();
        return User::where("nome_completo", "LIKE", "%{$request->name}%")
            ->whereHas('groups', function ($query) {
                $query->where('name', 'Consultor');
            })
            ->whereHas('companies', function ($query) use ($request) {
                $query->where('id', $request->header('company-id'));
            })
            ->get();
    }

    public function searchBancoFechamento(Request $request)
    {

        return BancosComSaldoResource::collection(Banco::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get());
    }
    public function insert(Request $request)
    {

        $array = ['error' => ''];

        $dados = $request->all();

        $emprestimoAdd = [];

        $emprestimoAdd['dt_lancamento'] = Carbon::createFromFormat('d/m/Y', $dados['dt_lancamento'])->format('Y-m-d');
        $emprestimoAdd['valor'] = $dados['valor'];
        $emprestimoAdd['lucro'] = $dados['lucro'];
        $emprestimoAdd['juros'] = $dados['juros'];
        $emprestimoAdd['costcenter_id'] = $dados['costcenter']['id'];
        $emprestimoAdd['banco_id'] = $dados['banco']['id'];
        $emprestimoAdd['client_id'] = $dados['cliente']['id'];
        $emprestimoAdd['user_id'] = $dados['consultor']['id'];
        $emprestimoAdd['company_id'] = $request->header('company-id');

        gerarPixParcelas::dispatch();


        $emprestimoAdd = Emprestimo::create($emprestimoAdd);

        if ($emprestimoAdd) {

            $contaspagar = [];
            $contaspagar['banco_id'] = $dados['banco']['id'];
            $contaspagar['emprestimo_id'] = $emprestimoAdd->id;
            $contaspagar['costcenter_id'] = $dados['costcenter']['id'];
            $contaspagar['status'] = 'Aguardando Pagamento';
            $contaspagar['tipodoc'] = 'Empréstimo';
            $contaspagar['lanc'] = date('Y-m-d');
            $contaspagar['venc'] = date('Y-m-d');
            $contaspagar['valor'] = $dados['valor'];
            $contaspagar['descricao'] = 'Empréstimo Nº ' . $emprestimoAdd->id . ' para ' . $dados['cliente']['nome_completo'];
            $contaspagar['company_id'] = $request->header('company-id');
            Contaspagar::create($contaspagar);

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $dados['banco']['id'];
            $movimentacaoFinanceira['company_id'] = $request->header('company-id');
            $movimentacaoFinanceira['descricao'] = 'Empréstimo Nº ' . $emprestimoAdd->id . ' para ' . $dados['cliente']['nome_completo'];
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $dados['valor'];

            Movimentacaofinanceira::create($movimentacaoFinanceira);

            $emprestimoAdd->banco->saldo = $emprestimoAdd->banco->saldo - $dados['valor'];
            $emprestimoAdd->banco->save();
        }

        $pegarUltimaParcela = $dados['parcelas'];
        end($pegarUltimaParcela);
        $ultimaParcela = current($pegarUltimaParcela);

        foreach ($dados['parcelas'] as $parcela) {

            $addParcela = [];
            $addParcela['emprestimo_id'] = $emprestimoAdd->id;
            $addParcela['dt_lancamento'] = date('Y-m-d');
            $addParcela['parcela'] = $parcela['parcela'];
            $addParcela['valor'] = $parcela['valor'];
            $addParcela['saldo'] = $parcela['saldo'];
            $addParcela['venc'] = Carbon::createFromFormat('d/m/Y', $parcela['venc'])->format('Y-m-d');
            $addParcela['venc_real'] = Carbon::createFromFormat('d/m/Y', $parcela['venc_real'])->format('Y-m-d');

            //API COBRANCA B.CODEX
            // $response = $this->bcodexService->criarCobranca($addParcela['valor']);

            // if ($response->successful()) {
            //     $addParcela['identificador'] = $response->json()['txid'];
            //     $addParcela['chave_pix'] = $response->json()['pixCopiaECola'];
            // }

            $parcela = Parcela::create($addParcela);

            if ($parcela) {
                $contasreceber = [];
                $contasreceber['company_id'] = $request->header('company-id');
                $contasreceber['parcela_id'] = $parcela->id;
                $contasreceber['client_id'] = $dados['cliente']['id'];
                $contasreceber['banco_id'] = $dados['banco']['id'];
                $contasreceber['descricao'] = 'Parcela N° ' . $parcela->parcela . ' do Emprestimo N° ' . $emprestimoAdd->id;
                $contasreceber['status'] = 'Aguardando Pagamento';
                $contasreceber['tipodoc'] = 'Empréstimo';
                $contasreceber['lanc'] = $parcela->dt_lancamento;
                $contasreceber['venc'] = $parcela->venc_real;
                $contasreceber['valor'] = $parcela->valor;

                Contasreceber::create($contasreceber);
            }
        }

        if ($dados['banco']['wallet'] == 1) {

            $quitacao = [];
            $quitacao['emprestimo_id'] = $emprestimoAdd->parcelas[0]->emprestimo_id;
            $quitacao['valor'] = $emprestimoAdd->parcelas[0]->totalPendente();
            $quitacao['saldo'] = $emprestimoAdd->parcelas[0]->totalPendente();

            //API COBRANCA B.CODEX
            // $response = $this->bcodexService->criarCobranca(
            //     ($emprestimoAdd->parcelas[0]->totalPendente() - $dados['valor'])
            // );

            // if ($response->successful()) {
            //     $pagamentoMinimo['identificador'] = $response->json()['txid'];
            //     $pagamentoMinimo['chave_pix'] = $response->json()['pixCopiaECola'];
            // }

            Quitacao::create($quitacao);
        }

        if ($dados['banco']['wallet'] == 1 && count($dados['parcelas']) == 1) {

            $pagamentoMinimo = [];
            $pagamentoMinimo['emprestimo_id'] = $emprestimoAdd->parcelas[0]->emprestimo_id;
            $pagamentoMinimo['valor'] = ($emprestimoAdd->parcelas[0]->totalPendente() - $dados['valor']);

            //API COBRANCA B.CODEX
            // $response = $this->bcodexService->criarCobranca($emprestimoAdd->parcelas[0]->totalPendente());

            // if ($response->successful()) {
            //     $quitacao['identificador'] = $response->json()['txid'];
            //     $quitacao['chave_pix'] = $response->json()['pixCopiaECola'];
            // }

            PagamentoMinimo::create($pagamentoMinimo);
        }

        return $emprestimoAdd;
    }

    public function insertRefinanciamento(Request $request)
    {

        $array = ['error' => ''];

        $dados = $request->all();

        $emprestimoAdd = [];

        $emprestimoAdd['dt_lancamento'] = Carbon::createFromFormat('d/m/Y', $dados['dt_lancamento'])->format('Y-m-d');
        $emprestimoAdd['valor'] = $dados['valor'];
        $emprestimoAdd['lucro'] = $dados['lucro'];
        $emprestimoAdd['juros'] = $dados['juros'];
        $emprestimoAdd['costcenter_id'] = $dados['costcenter']['id'];
        $emprestimoAdd['banco_id'] = $dados['banco']['id'];
        $emprestimoAdd['client_id'] = $dados['cliente']['id'];
        $emprestimoAdd['user_id'] = $dados['consultor']['id'];
        $emprestimoAdd['company_id'] = $request->header('company-id');

        gerarPixParcelas::dispatch();


        $emprestimoAdd = Emprestimo::create($emprestimoAdd);

        if ($emprestimoAdd) {

            $contaspagar = [];
            $contaspagar['banco_id'] = $dados['banco']['id'];
            $contaspagar['emprestimo_id'] = $emprestimoAdd->id;
            $contaspagar['costcenter_id'] = $dados['costcenter']['id'];
            $contaspagar['status'] = 'Aguardando Pagamento';
            $contaspagar['tipodoc'] = 'Empréstimo';
            $contaspagar['lanc'] = date('Y-m-d');
            $contaspagar['venc'] = date('Y-m-d');
            $contaspagar['valor'] = $dados['valor'];
            $contaspagar['descricao'] = 'Empréstimo Nº ' . $emprestimoAdd->id . ' para ' . $dados['cliente']['nome_completo'];
            $contaspagar['company_id'] = $request->header('company-id');
            Contaspagar::create($contaspagar);

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $dados['banco']['id'];
            $movimentacaoFinanceira['company_id'] = $request->header('company-id');
            $movimentacaoFinanceira['descricao'] = 'Refinanciamento Empréstimo Nº ' . $emprestimoAdd->id . ' para ' . $dados['cliente']['nome_completo'];
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $dados['valor'];

            Movimentacaofinanceira::create($movimentacaoFinanceira);
        }

        $pegarUltimaParcela = $dados['parcelas'];
        end($pegarUltimaParcela);
        $ultimaParcela = current($pegarUltimaParcela);

        foreach ($dados['parcelas'] as $parcela) {

            $addParcela = [];
            $addParcela['emprestimo_id'] = $emprestimoAdd->id;
            $addParcela['dt_lancamento'] = date('Y-m-d');
            $addParcela['parcela'] = $parcela['parcela'];
            $addParcela['valor'] = $parcela['valor'];
            $addParcela['saldo'] = $parcela['saldo'];
            $addParcela['venc'] = Carbon::createFromFormat('d/m/Y', $parcela['venc'])->format('Y-m-d');
            $addParcela['venc_real'] = Carbon::createFromFormat('d/m/Y', $parcela['venc_real'])->format('Y-m-d');


            if ($dados['banco']['wallet'] == 1) {

                $caminhoAbsoluto = storage_path('app/public/documentos/' . $dados['banco']['certificado']);
                $options = [
                    'clientId' => $dados['banco']['clienteid'],
                    'clientSecret' => $dados['banco']['clientesecret'],
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
                    "chave" => $dados['banco']['chavepix'], // Pix key registered in the authenticated Efí account
                    "solicitacaoPagador" => "Parcela " . $addParcela['parcela'],
                    "infoAdicionais" => [
                        [
                            "nome" => "Emprestimo",
                            "valor" => "R$ " . $emprestimoAdd->valor,
                        ],
                        [
                            "nome" => "Parcela",
                            "valor" => $addParcela['parcela'] . " / " . $ultimaParcela['parcela']
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

                            $this->custom_log->create([
                                'user_id' => auth()->user()->id,
                                'content' => 'Error ao gerar a parcela ' . $e->code . ' ' . $e->error . ' ' . $e->errorDescription,
                                'operation' => 'error'
                            ]);

                            print_r($e->code . "<br>");
                            print_r($e->error . "<br>");
                            print_r($e->errorDescription) . "<br>";
                        } catch (Exception $e) {
                            $this->custom_log->create([
                                'user_id' => auth()->user()->id,
                                'content' => $e->getMessage(),
                                'operation' => 'error'
                            ]);
                        }
                    } else {
                        $this->custom_log->create([
                            'user_id' => auth()->user()->id,
                            'content' => "<pre>" . json_encode($pix, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>",
                            'operation' => 'error'
                        ]);
                    }
                } catch (EfiException $e) {
                    $this->custom_log->create([
                        'user_id' => auth()->user()->id,
                        'content' => 'Error ao gerar a parcela ' . $e->code . ' ' . $e->error . ' ' . $e->errorDescription,
                        'operation' => 'error'
                    ]);
                } catch (Exception $e) {
                    $this->custom_log->create([
                        'user_id' => auth()->user()->id,
                        'content' => $e->getMessage(),
                        'operation' => 'error'
                    ]);
                }
            }

            $parcela = Parcela::create($addParcela);

            if ($parcela) {
                $contasreceber = [];
                $contasreceber['company_id'] = $request->header('company-id');
                $contasreceber['parcela_id'] = $parcela->id;
                $contasreceber['client_id'] = $dados['cliente']['id'];
                $contasreceber['banco_id'] = $dados['banco']['id'];
                $contasreceber['descricao'] = 'Parcela N° ' . $parcela->parcela . ' do Emprestimo N° ' . $emprestimoAdd->id;
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

    public function pagamentoTransferencia(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_fornecedores_create')) {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' não tem permissão para autorizar o pagamento do emprestimo ' . $id,
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

            // if ($emprestimo) {
            //     $envio = self::enviarPix([
            //         'valor' => $emprestimo->valor,
            //         'informacao' => 'Emprestimo de R$ ' . $emprestimo->valor . ' para o ' . $emprestimo->client->nome_completo,
            //         'pix_cliente' => $emprestimo->client->cpf
            //     ]);

            //     if ($envio['error_code'] != null) {
            //         return response()->json([
            //             "message" => $envio['error_description'],
            //             "error" => ''
            //         ], Response::HTTP_FORBIDDEN);
            //     }

            // }

            if ($emprestimo->banco->wallet == 1) {
                $emprestimo->contaspagar->status = 'Gerando Pix';

                // Disparar o job para processar o empréstimo em paralelo
                ProcessarPixJob::dispatch($emprestimo);

            } else {
                $emprestimo->contaspagar->status = 'Pagamento Efetuado';
            }

            $emprestimo->contaspagar->dt_baixa = date('Y-m-d');
            $emprestimo->contaspagar->save();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' autorizou o pagamento do emprestimo ' . $id . 'no valor de R$ ' . $emprestimo->valor . ' para o cliente ' . $emprestimo->client->nome_completo,
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

    public function reprovarEmprestimo(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_fornecedores_create')) {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' não tem permissão para autorizar o pagamento do emprestimo ' . $id,
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

            if ($emprestimo) {
                $emprestimo->contaspagar->status = 'Empréstimo Reprovado';
                $emprestimo->contaspagar->save();
            }



            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' reprovou o pagamento do emprestimo ' . $id . 'no valor de R$ ' . $emprestimo->valor . ' para o cliente ' . $emprestimo->client->nome_completo,
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

    public function update(Request $request, $id)
    {


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
            if (!$validator->fails()) {

                $EditEmprestimo = Emprestimo::find($id);

                $EditEmprestimo->valor = $dados['valor'];
                $EditEmprestimo->lucro = $dados['lucro'];
                $EditEmprestimo->juros = $dados['juros'];
                $EditEmprestimo->saldo = $dados['saldo'];
                $EditEmprestimo->costcenter_id = $dados['costcenter_id'];
                $EditEmprestimo->banco_id = $dados['banco_id'];
                $EditEmprestimo->client_id = $dados['client_id'];
                $EditEmprestimo->user_id = $dados['user_id'];

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

    public function cancelarBaixaManual(Request $request, $id)
    {


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            // Obter a primeira parcela de extorno correspondente ao ID fornecido
            $extornoParcela = ParcelaExtorno::where('parcela_id', $id)->first();

            // Verificar se a parcela de extorno foi encontrada
            if (!$extornoParcela) {
                return response()->json([
                    "message" => "Erro ao editar o Emprestimo.",
                    "error" => 'Parcela de extorno não encontrada.'
                ], Response::HTTP_FORBIDDEN);
            }

            // Obter todas as parcelas de extorno com o mesmo hash_extorno
            $extorno = ParcelaExtorno::where('hash_extorno', $extornoParcela->hash_extorno)->get();

            $extorno[0]->emprestimo->company->caixa_pix -= $extorno[0]->valor_alterado;
            $extorno[0]->emprestimo->company->save();

            foreach ($extorno as $ext) {
                $editParcela = Parcela::find($ext->parcela_id);
                $editParcela->valor = $ext->valor;
                $editParcela->saldo = $ext->saldo;
                $editParcela->venc = $ext->venc;
                $editParcela->venc_real = $ext->venc_real;
                $editParcela->dt_lancamento = $ext->dt_lancamento;
                $editParcela->dt_baixa = $ext->dt_baixa;
                $editParcela->identificador = $ext->identificador;
                $editParcela->chave_pix = $ext->chave_pix;
                $editParcela->dt_ult_cobranca = $ext->dt_ult_cobranca;
                $editParcela->created_at = $ext->created_at;
                $editParcela->updated_at = $ext->updated_at;
                $editParcela->valor_recebido_pix = $ext->valor_recebido_pix;
                $editParcela->save();
            }

            foreach ($extorno as $ext) {
                $ext->delete();
            }


            // $editParcela = Parcela::find($id);
            // $editParcela->saldo = $editParcela->valor;
            // $editParcela->dt_baixa = null;
            // if ($editParcela->contasreceber) {
            //     $editParcela->contasreceber->status = 'Aguardando Pagamento';
            //     $editParcela->contasreceber->dt_baixa = null;
            //     $editParcela->contasreceber->forma_recebto = null;
            //     $editParcela->contasreceber->save();
            // }

            // $editParcela->emprestimo->company->caixa = $editParcela->emprestimo->company->caixa - $editParcela->saldo;
            // $editParcela->emprestimo->company->save();

            // $editParcela->save();

            // $movimentacaoFinanceira = [];
            // $movimentacaoFinanceira['banco_id'] = $editParcela->emprestimo->banco_id;
            // $movimentacaoFinanceira['company_id'] = $editParcela->emprestimo->company_id;
            // $movimentacaoFinanceira['descricao'] = 'Cancelamento da Baixa da parcela Nº ' . $editParcela->parcela . ' do emprestimo n° ' . $editParcela->emprestimo_id;
            // $movimentacaoFinanceira['tipomov'] = 'S';
            // $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            // $movimentacaoFinanceira['valor'] = $editParcela->saldo;

            // Movimentacaofinanceira::create($movimentacaoFinanceira);



            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' cancelou a baixa manual da parcela: ' . $id,
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
    public function baixaManual(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $hash_extorno = Str::uuid()->toString();

            $editParcela = Parcela::find($id);

            $saldoParcela = $editParcela->saldo;

            $valor_recebido = $request->valor;

            $extorno = ParcelaExtorno::where('parcela_id', $id)->first();

            if ($extorno) {
                $extornos = ParcelaExtorno::where('emprestimo_id', $extorno->emprestimo_id)->get();

                foreach ($extornos as $ext) {
                    $ext->delete();
                }
            }

            $addParcelaExtorno = [];
            $addParcelaExtorno['parcela_id'] = $editParcela->id;
            $addParcelaExtorno['emprestimo_id'] = $editParcela->emprestimo_id;
            $addParcelaExtorno['hash_extorno'] = $hash_extorno;
            $addParcelaExtorno['dt_lancamento'] = $editParcela->dt_lancamento;
            $addParcelaExtorno['parcela'] = $editParcela->parcela;
            $addParcelaExtorno['valor'] = $editParcela->valor;
            $addParcelaExtorno['saldo'] = $editParcela->saldo;
            $addParcelaExtorno['venc'] = $editParcela->venc;
            $addParcelaExtorno['venc_real'] = $editParcela->venc_real;
            $addParcelaExtorno['dt_baixa'] = $editParcela->dt_baixa;
            $addParcelaExtorno['identificador'] = $editParcela->identificador;
            $addParcelaExtorno['chave_pix'] = $editParcela->chave_pix;
            $addParcelaExtorno['dt_ult_cobranca'] = $editParcela->dt_ult_cobranca;
            $addParcelaExtorno['valor_alterado'] = $valor_recebido;
            $addParcelaExtorno['valor_recebido_pix'] = $editParcela->valor_recebido_pix;

            ParcelaExtorno::create($addParcelaExtorno);

            $editParcela->dt_ult_cobranca = $request->dt_baixa;

            // if ($editParcela->contasreceber) {
            //     $editParcela->contasreceber->status = 'Pago';
            //     $editParcela->contasreceber->dt_baixa = $request->dt_baixa;
            //     $editParcela->contasreceber->forma_recebto = 'PIX';
            //     $editParcela->contasreceber->save();
            // }

            $editParcela->emprestimo->company->caixa_pix +=  $valor_recebido;
            $editParcela->emprestimo->company->save();

            $editParcela->valor_recebido_pix += $valor_recebido;
            $editParcela->save();



            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' realizou a baixa manual da parcela: ' . $id,
                'operation' => 'index'
            ]);

            DB::commit();

            return response()->json(['message' => 'Baixa realizada com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function baixaDesconto(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $emprestimo = Emprestimo::find($id);

            if ($emprestimo) {
                $dataHoje = date('Y-m-d');

                foreach ($emprestimo->parcelas as $parcela) {
                    if (!$parcela->dt_baixa) {
                        $parcela->dt_baixa = $dataHoje;
                        $parcela->saldo = 0;
                        $parcela->save();

                        if ($parcela->contasreceber) {
                            $parcela->contasreceber->status = 'Pago';
                            $parcela->contasreceber->dt_baixa = $dataHoje;
                            $parcela->contasreceber->forma_recebto = 'BAIXA COM DESCONTO';
                            $parcela->contasreceber->save();
                        }
                    }
                }

                $movimentacaoFinanceira = [];
                $movimentacaoFinanceira['banco_id'] = $emprestimo->banco_id;
                $movimentacaoFinanceira['company_id'] = $emprestimo->company_id;
                $movimentacaoFinanceira['parcela_id'] = $emprestimo->parcelas[0]->id;
                $movimentacaoFinanceira['descricao'] = 'Baixa com desconto no Empréstimo Nº ' . $emprestimo->id . ', que tinha um saldo pendente de R$ ' . number_format($request->saldo, 2, ',', '.') . ' e recebeu um desconto de R$ ' . number_format(($request->saldo - $request->valor), 2, ',', '.');
                $movimentacaoFinanceira['tipomov'] = 'E';
                $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                $movimentacaoFinanceira['valor'] = $request->valor;

                Movimentacaofinanceira::create($movimentacaoFinanceira);
            }

            DB::commit();

            return response()->json(['message' => 'Baixa realizada com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function refinanciamento(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $emprestimo = Emprestimo::find($id);

            if ($emprestimo) {
                $dataHoje = date('Y-m-d');

                foreach ($emprestimo->parcelas as $parcela) {
                    if (!$parcela->dt_baixa) {
                        $parcela->dt_baixa = $dataHoje;
                        $parcela->saldo = 0;
                        $parcela->save();

                        if ($parcela->contasreceber) {
                            $parcela->contasreceber->status = 'Pago';
                            $parcela->contasreceber->dt_baixa = $dataHoje;
                            $parcela->contasreceber->forma_recebto = 'REFINANCIAMENTO';
                            $parcela->contasreceber->save();
                        }
                    }
                }

                $movimentacaoFinanceira = [];
                $movimentacaoFinanceira['banco_id'] = $emprestimo->banco_id;
                $movimentacaoFinanceira['company_id'] = $emprestimo->company_id;
                $movimentacaoFinanceira['descricao'] = 'Refinanciamento do Empréstimo Nº ' . $emprestimo->id;
                $movimentacaoFinanceira['tipomov'] = 'E';
                $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                $movimentacaoFinanceira['valor'] = $request->saldo;

                Movimentacaofinanceira::create($movimentacaoFinanceira);
            }

            DB::commit();

            return response()->json(['message' => 'Refinanciamento realizado com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function baixaManualCobrador(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $editParcela = Parcela::find($id);

            $editParcela->valor_recebido = $request->valor;
            $editParcela->dt_ult_cobranca = $request->dt_baixa;

            $editParcela->save();



            $editParcela->emprestimo->company->caixa = $editParcela->emprestimo->company->caixa + $request->valor;
            $editParcela->emprestimo->company->save();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' recebeu a baixa parcial da parcela: ' . $id,
                'operation' => 'index'
            ]);

            DB::commit();

            return response()->json(['message' => 'Baixa realizada com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function infoEmprestimo(Request $request, $id)
    {

        $array = ['error' => ''];

        $user = auth()->user();

        $parcela = Parcela::find($id);
        if ($parcela) {
            return ParcelaResource::collection($parcela->emprestimo->parcelas);
        }



        return response()->json(['message' => 'Baixa realizada com sucesso.']);
    }


    public function infoEmprestimoFront(Request $request, $id)
    {

        $array = ['error' => '', 'data' => []];

        $user = auth()->user();


        $parcela = Parcela::find($id);

        if ($parcela) {
            $array['data']['emprestimo'] = new EmprestimoResource($parcela->emprestimo);
            return $array;
        }



        return response()->json(['message' => 'Baixa realizada com sucesso.']);
    }

    public function webhookRetornoCobranca(Request $request)
    {
        $data = $request->json()->all();

        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $parcela = Parcela::where('identificador', $txId)->first();

                while ($parcela && $valor > 0) {
                    if ($valor >= $parcela->saldo) {
                        // Quitar a parcela atual
                        $valor -= $parcela->saldo;
                        $parcela->saldo = 0;
                        $parcela->dt_baixa = $horario;
                    } else {
                        // Reduzir o saldo da parcela atual
                        $parcela->saldo -= $valor;
                        $valor = 0;
                    }
                    $parcela->save();

                    // Encontrar a próxima parcela
                    $parcela = Parcela::where('emprestimo_id', $parcela->emprestimo_id)
                        ->where('id', '>', $parcela->id)
                        ->orderBy('id', 'asc')
                        ->first();
                }
            }
        }

        return response()->json(['message' => 'Baixas realizadas com sucesso.']);
    }

    public function cobrarAmanha(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $editParcela = Parcela::find($id);
            $editParcela->dt_ult_cobranca = $request->dt_ult_cobranca;
            $editParcela->save();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' deixou a cobrança para amanha da parcela: ' . $id,
                'operation' => 'index'
            ]);

            return response()->json(['message' => 'Cobrança atualizada com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao mudar cobrança da parcela do Emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Emprestimo::findOrFail($id);

            $permGroup->banco->saldo = $permGroup->banco->saldo + $permGroup->valor;
            $permGroup->banco->save();

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $permGroup->banco->id;
            $movimentacaoFinanceira['company_id'] = $permGroup->company_id;
            $movimentacaoFinanceira['descricao'] = 'Exclusão Empréstimo Nº ' . $permGroup->id;
            $movimentacaoFinanceira['tipomov'] = 'E';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $permGroup->valor;

            Movimentacaofinanceira::create($movimentacaoFinanceira);

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' deletou o Emprestimo: ' . $id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Emprestimo excluída com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' tentou deletar o Emprestimo: ' . $id . ' ERROR: ' . $e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir emprestimo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
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
}
