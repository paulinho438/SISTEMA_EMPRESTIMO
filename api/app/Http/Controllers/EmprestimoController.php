<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\Locacao;
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
use App\Models\PagamentoPersonalizado;
use App\Models\Bank;

use App\Services\BcodexService;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use App\Jobs\ProcessarPixJob;

use App\Mail\ExampleEmail;
use Illuminate\Support\Facades\Mail;

use DateTime;

use Ramsey\Uuid\Uuid;

use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Http\Resources\EmprestimoResource;
use App\Http\Resources\EmprestimoPendentesResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ParcelaResource;
use App\Http\Resources\BancosComSaldoResource;
use App\Http\Resources\CostcenterResource;
use App\Http\Resources\FeriadoEmprestimoResource;
use App\Http\Resources\FornecedorResource;

use App\Jobs\gerarPixParcelas;
use App\Models\ParcelaExtorno;
use App\Models\PagamentoSaldoPendente;
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

    public function recalcularParcelas(Request $r)
    {

        $juros = Juros::value('juros');

        $parcelasVencidas = Parcela::where('venc_real', '<', Carbon::now()->subDay())->where('dt_baixa', null)->get();

        return $parcelasVencidas;
    }

    public function parcelasPendentesParaHoje(Request $request)
    {

        return EmprestimoPendentesResource::collection(
            Emprestimo::whereHas('parcelas', function ($query) use ($request) {
                $query->where('dt_baixa', null)
                    ->where('valor_recebido_pix', null)
                    ->whereHas('emprestimo', function ($query) use ($request) {
                        $query->where('company_id', $request->header('company-id'));
                    });
            })->get()
        );
    }

    public function parcelasParaExtorno(Request $request)
    {

        $extorno = ParcelaExtorno::whereHas('emprestimo', function ($query) use ($request) {
            $query->where('company_id', $request->header('company-id'));
        })->get()->unique('hash_extorno');

        $parcelas = [];

        foreach ($extorno as $ext) {
            if ($ext->parcela_associada->saldo > 0) {
                $parcelaResource = new ParcelaResource($ext->parcela_associada);
                $parcelaArray = $parcelaResource->resolve(); // Converte para array usando resolve
                $parcelaArray['saldo_correto'] = $ext->saldo - $ext->parcela_associada->saldo; // Adiciona o campo saldo_correto
                $parcelaArray['updated_at'] = $ext->parcela_associada->updated_at; // Adiciona o campo updated_at
                $parcelas[] = $parcelaArray;
            }
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

        $pagamentoSaldoPendente = [];
        $pagamentoSaldoPendente['emprestimo_id'] = $emprestimoAdd->parcelas[0]->emprestimo_id;
        $pagamentoSaldoPendente['valor'] = ($emprestimoAdd->parcelas[0]->saldo);
        PagamentoSaldoPendente::create($pagamentoSaldoPendente);

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

        $emprestimoAdd = Emprestimo::create($emprestimoAdd);

        if ($emprestimoAdd) {

            $contaspagar = [];
            $contaspagar['banco_id'] = $dados['banco']['id'];
            $contaspagar['emprestimo_id'] = $emprestimoAdd->id;
            $contaspagar['costcenter_id'] = $dados['costcenter']['id'];
            $contaspagar['status'] = 'Pagamento Efetuado';
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

        ProcessarPixJob::dispatch($emprestimoAdd, $this->bcodexService);

        return $emprestimoAdd;
    }

    public function pagamentoTransferencia(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_emprestimos_autorizar_pagamentos')) {
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

            if ($emprestimo->contaspagar->status == 'Pagamento Efetuado') {
                return response()->json([
                    "message" => "Pagamento já efetuado.",
                    "error" => ""
                ], Response::HTTP_FORBIDDEN);
            }

            if ($emprestimo->banco->wallet == 1) {
                if (!$emprestimo->client->pix_cliente) {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'Cliente não possui chave pix cadastrada'
                    ], Response::HTTP_FORBIDDEN);
                }

                $response = $this->bcodexService->consultarChavePix(($emprestimo->valor * 100), $emprestimo->client->pix_cliente, $emprestimo->banco->accountId);

                if ($response->successful()) {
                    if ($response->json()['status'] == 'AWAITING_CONFIRMATION') {

                        $response = $this->bcodexService->realizarPagamentoPix(($emprestimo->valor * 100), $emprestimo->banco->accountId, $response->json()['paymentId']);

                        if (!$response->successful()) {
                            return response()->json([
                                "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                                "error" => "Erro ao efetuar a transferencia do Emprestimo."
                            ], Response::HTTP_FORBIDDEN);
                        }

                        $array['response'] = $response->json();

                        $bank = Bank::where('ispb', $array['response']['creditParty']['bank'])->first();

                        $dados = [
                            'valor' => $emprestimo->valor,
                            'tipo_transferencia' => 'PIX',
                            'descricao' => 'Transferência realizada com sucesso',
                            'destino_nome' => $array['response']['creditParty']['name'],
                            'destino_cpf' => self::mascararString($emprestimo->client->cpf),
                            'destino_chave_pix' => $emprestimo->client->pix_cliente,
                            'destino_instituicao' => $bank->short_name ?? 'Unknown',
                            'destino_banco' => $bank->code_number ?? '000',
                            'destino_agencia' => str_pad($array['response']['creditParty']['branch'], 4, '0', STR_PAD_LEFT),
                            'destino_conta' => substr_replace($array['response']['creditParty']['accountNumber'], '-', -1, 0),
                            'origem_nome' => 'BCODEX TECNOLOGIA E SERVICOS LTDA',
                            'origem_cnpj' => '52.196.079/0001-71',
                            'origem_instituicao' => 'BANCO BTG PACTUAL S.A.',
                            'data_hora' => date('d/m/Y H:i:s'),
                            'id_transacao' => $array['response']['endToEndId'],
                        ];

                        $array['dados'] = $dados;

                        // Renderizar o HTML da view
                        $html = view('comprovante-template', $dados)->render();

                        // Salvar o HTML em um arquivo temporário
                        $htmlFilePath = storage_path('app/public/comprovante.html');
                        file_put_contents($htmlFilePath, $html);

                        // Caminho para o arquivo PNG de saída
                        $pngPath = storage_path('app/public/comprovante.png');

                        // Configurações de tamanho, qualidade e zoom
                        $width = 800;    // Largura em pixels
                        $height = 1600;  // Altura em pixels
                        $quality = 100;  // Qualidade máxima
                        $zoom = 1.6;     // Zoom de 2x

                        // Executar o comando wkhtmltoimage com ajustes
                        $command = "xvfb-run wkhtmltoimage --width {$width} --height {$height} --quality {$quality} --zoom {$zoom} {$htmlFilePath} {$pngPath}";
                        shell_exec($command);

                        // Verificar se o PNG foi gerado
                        if (file_exists($pngPath)) {
                            try {
                                $telefone = preg_replace('/\D/', '', $emprestimo->client->telefone_celular_1);
                                // Enviar o PNG gerado para o endpoint
                                $response = Http::attach(
                                    'arquivo', // Nome do campo no formulário
                                    file_get_contents($pngPath), // Conteúdo do arquivo
                                    'comprovante.png' // Nome do arquivo enviado
                                )->post($emprestimo->company->whatsapp . '/enviar-pdf', [
                                    'numero' =>  "55" . $telefone,
                                ]);
                            } catch (\Exception $e) {
                            }
                        } else {
                        }
                    }
                } else {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'O banco não possui saldo suficiente para efetuar a transferencia'
                    ], Response::HTTP_FORBIDDEN);
                }
                // Disparar o job para processar o empréstimo em paralelo
                ProcessarPixJob::dispatch($emprestimo, $this->bcodexService);
            }


            $emprestimo->contaspagar->status = 'Pagamento Efetuado';

            $emprestimo->contaspagar->dt_baixa = date('Y-m-d');
            $emprestimo->contaspagar->save();

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $emprestimo->banco->id;
            $movimentacaoFinanceira['company_id'] = $request->header('company-id');
            $movimentacaoFinanceira['descricao'] = 'Empréstimo Nº ' . $emprestimo->id . ' para ' . $emprestimo->client->nome_completo;
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $emprestimo->valor;

            Movimentacaofinanceira::create($movimentacaoFinanceira);

            $emprestimo->banco->saldo -= $emprestimo->valor;
            $emprestimo->banco->save();

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

    function mascararString($string)
    {
        $primeirosTres = substr($string, 0, 3);
        $ultimosDois = substr($string, -2);
        $mascarado = '***' . substr($string, 3, -2) . '**';
        return $mascarado;
    }

    public function pagamentoTransferenciaTituloAPagarConsultar(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_emprestimos_autorizar_pagamentos')) {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' não tem permissão para autorizar o pagamento do titulo a pagar ' . $id,
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

            $contaspagar = Contaspagar::find($id);

            if ($contaspagar->status == 'Pagamento Efetuado') {
                return response()->json([
                    "message" => "Pagamento já efetuado.",
                    "error" => ""
                ], Response::HTTP_FORBIDDEN);
            }

            if ($contaspagar->banco->wallet == 1) {
                if (!$contaspagar->fornecedor->pix_fornecedor) {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'Fornecedor não possui chave pix cadastrada'
                    ], Response::HTTP_FORBIDDEN);
                }

                $response = $this->bcodexService->consultarChavePix(($contaspagar->valor * 100), $contaspagar->fornecedor->pix_fornecedor, $contaspagar->banco->accountId);

                if ($response->successful()) {
                    return $response->json();
                } else {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'O banco não possui saldo suficiente para efetuar a transferencia'
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            DB::commit();

            return $array;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao efetuar a transferencia do Titulo a Pagar.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function pagamentoTransferenciaTituloAPagar(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_emprestimos_autorizar_pagamentos')) {
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

            $contaspagar = Contaspagar::find($id);

            if ($contaspagar->status == 'Pagamento Efetuado') {
                return response()->json([
                    "message" => "Pagamento já efetuado.",
                    "error" => ""
                ], Response::HTTP_FORBIDDEN);
            }

            if ($contaspagar->banco->wallet == 1) {
                if (!$contaspagar->fornecedor->pix_fornecedor) {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'Fornecedor não possui chave pix cadastrada'
                    ], Response::HTTP_FORBIDDEN);
                }

                $response = $this->bcodexService->consultarChavePix(($contaspagar->valor * 100), $contaspagar->fornecedor->pix_fornecedor, $contaspagar->banco->accountId);

                if ($response->successful()) {
                    if ($response->json()['status'] == 'AWAITING_CONFIRMATION') {

                        $response = $this->bcodexService->realizarPagamentoPix(($contaspagar->valor * 100), $contaspagar->banco->accountId, $response->json()['paymentId']);

                        if (!$response->successful()) {
                            return response()->json([
                                "message" => "Erro ao efetuar a transferencia do Titulo.",
                                "error" => "Erro ao efetuar a transferencia do Titulo."
                            ], Response::HTTP_FORBIDDEN);
                        }

                        $array['response'] = $response->json();

                        $bank = Bank::where('ispb', $array['response']['creditParty']['bank'])->first();

                        $dados = [
                            'valor' => $contaspagar->valor,
                            'tipo_transferencia' => 'PIX',
                            'descricao' => 'Transferência realizada com sucesso',
                            'destino_nome' => $array['response']['creditParty']['name'],
                            'destino_cpf' => self::mascararString($contaspagar->fornecedor->cpfcnpj),
                            'destino_chave_pix' => $contaspagar->fornecedor->pix_fornecedor,
                            'destino_instituicao' => $bank->short_name ?? 'Unknown',
                            'destino_banco' => $bank->code_number ?? '000',
                            'destino_agencia' => str_pad($array['response']['creditParty']['branch'], 4, '0', STR_PAD_LEFT),
                            'destino_conta' => substr_replace($array['response']['creditParty']['accountNumber'], '-', -1, 0),
                            'origem_nome' => 'BCODEX TECNOLOGIA E SERVICOS LTDA',
                            'origem_cnpj' => '52.196.079/0001-71',
                            'origem_instituicao' => 'BANCO BTG PACTUAL S.A.',
                            'data_hora' => date('d/m/Y H:i:s'),
                            'id_transacao' => $array['response']['endToEndId'],
                        ];

                        $array['dados'] = $dados;

                        // Renderizar o HTML da view
                        $html = view('comprovante-template', $dados)->render();

                        // Salvar o HTML em um arquivo temporário
                        $htmlFilePath = storage_path('app/public/comprovante.html');
                        file_put_contents($htmlFilePath, $html);

                        // Caminho para o arquivo PNG de saída
                        $pngPath = storage_path('app/public/comprovante.png');

                        // Configurações de tamanho, qualidade e zoom
                        $width = 800;    // Largura em pixels
                        $height = 1600;  // Altura em pixels
                        $quality = 100;  // Qualidade máxima
                        $zoom = 1.6;     // Zoom de 2x

                        // Executar o comando wkhtmltoimage com ajustes
                        $command = "xvfb-run wkhtmltoimage --width {$width} --height {$height} --quality {$quality} --zoom {$zoom} {$htmlFilePath} {$pngPath}";
                        shell_exec($command);

                        // Verificar se o PNG foi gerado
                        if (file_exists($pngPath)) {
                            try {
                                $telefone = preg_replace('/\D/', '', $contaspagar->fornecedor->telefone_celular_1);
                                // Enviar o PNG gerado para o endpoint
                                $response = Http::attach(
                                    'arquivo', // Nome do campo no formulário
                                    file_get_contents($pngPath), // Conteúdo do arquivo
                                    'comprovante.png' // Nome do arquivo enviado
                                )->post($contaspagar->company->whatsapp . '/enviar-pdf', [
                                    'numero' =>  "55" . $telefone,
                                ]);
                            } catch (\Exception $e) {
                            }
                        } else {
                        }
                    }
                } else {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Titulo.",
                        "error" => 'O banco não possui saldo suficiente para efetuar a transferencia'
                    ], Response::HTTP_FORBIDDEN);
                }
                // Disparar o job para processar o empréstimo em paralelo
            }


            $contaspagar->status = 'Pagamento Efetuado';

            $contaspagar->dt_baixa = date('Y-m-d');
            $contaspagar->save();

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $contaspagar->banco->id;
            $movimentacaoFinanceira['company_id'] = $request->header('company-id');
            $movimentacaoFinanceira['descricao'] = 'Título a pagar Nº ' . $contaspagar->id . ' para ' . $contaspagar->fornecedor->nome_completo;
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $contaspagar->valor;

            Movimentacaofinanceira::create($movimentacaoFinanceira);

            $contaspagar->banco->saldo -= $contaspagar->valor;
            $contaspagar->banco->save();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' autorizou o pagamento do titulo ' . $id . 'no valor de R$ ' . $contaspagar->valor . ' para o fornecedor ' . $contaspagar->fornecedor->nome_completo,
                'operation' => 'edit'
            ]);

            DB::commit();

            return $array;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao efetuar a transferencia do Titulo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function pagamentoTransferenciaConsultar(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_emprestimos_autorizar_pagamentos')) {
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

            if ($emprestimo->contaspagar->status == 'Pagamento Efetuado') {
                return response()->json([
                    "message" => "Pagamento já efetuado.",
                    "error" => ""
                ], Response::HTTP_FORBIDDEN);
            }

            if ($emprestimo->banco->wallet == 1) {
                if (!$emprestimo->client->pix_cliente) {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'Cliente não possui chave pix cadastrada'
                    ], Response::HTTP_FORBIDDEN);
                }

                $response = $this->bcodexService->consultarChavePix(($emprestimo->valor * 100), $emprestimo->client->pix_cliente, $emprestimo->banco->accountId);

                if ($response->successful()) {
                    return $response->json();
                } else {
                    return response()->json([
                        "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                        "error" => 'O banco não possui saldo suficiente para efetuar a transferencia'
                    ], Response::HTTP_FORBIDDEN);
                }
            }

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

    public function reprovarContasAPagar(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_emprestimos_autorizar_pagamentos')) {
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


            $permGroup = Contaspagar::findOrFail($id);

            if ($permGroup->status == "Pagamento Efetuado") {
                return response()->json([
                    "message" => "Erro ao excluir título, pagamento já foi efetuado",
                    "error" => "Erro ao excluir título, pagamento já foi efetuado"
                ], Response::HTTP_FORBIDDEN);
            }

            $permGroup->delete();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' excluiu o contas a pagar: ' . $id,
                'operation' => 'destroy'
            ]);

            DB::commit();

            return $array;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao efetuar a exclusão do titulo a pagar.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function reprovarEmprestimo(Request $request, $id)
    {

        if (!$this->contem($request->header('Company_id'), auth()->user(), 'view_emprestimos_autorizar_pagamentos')) {
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


            $permGroup = Emprestimo::findOrFail($id);

            if ($permGroup->contaspagar->status == "Pagamento Efetuado") {
                return response()->json([
                    "message" => "Erro ao excluir emprestimo, pagamento já foi efetuado",
                    "error" => "Erro ao excluir emprestimo, pagamento já foi efetuado"
                ], Response::HTTP_FORBIDDEN);
            }

            $permGroup->contaspagar->delete();

            $permGroup->delete();


            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' reprovou e deletou o Emprestimo: ' . $id,
                'operation' => 'destroy'
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
                return response()->json([
                    "message" => $validator->errors()->first(),
                    "error" => $validator->errors()->first()
                ], Response::HTTP_FORBIDDEN);
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
            $parcela = Parcela::find($id);



            // Verificar se a parcela de extorno foi encontrada
            if (!$parcela) {
                return response()->json([
                    "message" => "Erro ao cancelar baixa.",
                    "error" => 'Parcela não encontrada.'
                ], Response::HTTP_FORBIDDEN);
            }

            $parcela->emprestimo->company->caixa_pix -= $parcela->valor_recebido_pix;
            $parcela->emprestimo->company->caixa -= $parcela->valor_recebido;
            $parcela->emprestimo->company->save();


            $parcela->valor_recebido_pix = null;
            $parcela->valor_recebido = null;
            $parcela->save();

            $parcelaExtorno = ParcelaExtorno::where('parcela_id', $id)->first();
            $parcelaExtorno->delete();

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

    public function cancelarBaixaManualBK(Request $request, $id)
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
                        $parcela->dt_baixa = date('Y-m-d');
                        $parcela->saldo = 0;
                        $parcela->save();

                        if ($parcela->contasreceber) {
                            $parcela->contasreceber->status = 'Pago';
                            $parcela->contasreceber->dt_baixa = date('Y-m-d');
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

    public function personalizarPagamento(Request $request, $id)
    {

        $array = ['error' => '', 'data' => []];

        $user = auth()->user();

        $dados = $request->all();

        $parcela = Parcela::find($id);

        if ($parcela) {

            //API COBRANCA B.CODEX
            $response = $this->bcodexService->criarCobranca($dados['valor'], $parcela->emprestimo->banco->document);

            if ($response->successful()) {

                $newPagamento = [];

                $newPagamento['emprestimo_id'] = $parcela->emprestimo_id;
                $newPagamento['valor'] = $dados['valor'];

                $newPagamento['identificador'] = $response->json()['txid'];
                $newPagamento['chave_pix'] = $response->json()['pixCopiaECola'];

                PagamentoPersonalizado::create($newPagamento);

                self::enviarMensagem($parcela, 'Olá ' . $parcela->emprestimo->client->nome_completo . ', estamos entrando em contato para informar sobre seu empréstimo. Conforme solicitado segue chave pix referente ao valor personalizado de R$ ' . $dados['valor'] . '');

                self::enviarMensagem($parcela, $response->json()['pixCopiaECola']);

                return 'ok';
            } else {
                return response()->json([
                    "message" => "Erro ao gerar pagamento personalizado",
                    "error" => $response->json()
                ], Response::HTTP_FORBIDDEN);
            }
        } else {
            return response()->json([
                "message" => "Erro ao gerar pagamento personalizado",
                "error" => ''
            ], Response::HTTP_FORBIDDEN);
        }

        if ($parcela) {
            $array['data']['emprestimo'] = new EmprestimoResource($parcela->emprestimo);
            return $array;
        }
    }

    public function webhookRetornoCobranca(Request $request)
    {
        $data = $request->json()->all();

        // // Nome do arquivo
        // $file = 'webhookcobranca.txt';

        // // Verifica se o arquivo existe, se não, cria-o
        // if (!Storage::exists($file)) {
        //     Storage::put($file, '');
        // }

        // // Lê o conteúdo atual do arquivo
        // $current = Storage::get($file);

        // // Adiciona os novos dados ao conteúdo atual
        // $current .= json_encode($data) . PHP_EOL;

        // // Salva o conteúdo atualizado no arquivo
        // Storage::put($file, $current);

        //REFERENTE A PARCELAS
        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $parcela = Parcela::where('identificador', $txId)->first();

                if ($parcela) {
                    $parcela->saldo = 0;
                    $parcela->dt_baixa = $horario;
                    $parcela->save();

                    if ($parcela->contasreceber) {
                        $parcela->contasreceber->status = 'Pago';
                        $parcela->contasreceber->dt_baixa = date('Y-m-d');
                        $parcela->contasreceber->forma_recebto = 'PIX';
                        $parcela->contasreceber->save();

                        # MOVIMENTAÇÃO FINANCEIRA DE ENTRADA REFERENTE A BAIXA MANUAL

                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = sprintf(
                            'Baixa automática da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                            $parcela->id,
                            $parcela->emprestimo_id,
                            $parcela->emprestimo->client->nome_completo,
                            $pix['pagador']['nome']
                        );
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;

                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        # ADICIONANDO O VALOR NO SALDO DO BANCO

                        $parcela->emprestimo->banco->saldo = $parcela->emprestimo->banco->saldo + $valor;
                        $parcela->emprestimo->banco->save();

                        // $movimentacaoFinanceira = [];
                        // $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        // $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        // $movimentacaoFinanceira['descricao'] = 'Juros de ' . $parcela->emprestimo->banco->juros . '% referente a baixa automática via pix da parcela Nº ' . $parcela->parcela . ' do emprestimo n° ' . $parcela->emprestimo_id;
                        // $movimentacaoFinanceira['tipomov'] = 'S';
                        // $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                        // $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        // $movimentacaoFinanceira['valor'] = $juros;

                        // Movimentacaofinanceira::create($movimentacaoFinanceira);

                        if ($parcela->emprestimo->quitacao->chave_pix) {

                            $parcela->emprestimo->quitacao->valor = $parcela->emprestimo->parcelas[0]->totalPendente();
                            $parcela->emprestimo->quitacao->saldo = $parcela->emprestimo->parcelas[0]->totalPendente();
                            $parcela->emprestimo->quitacao->save();

                            $response = $this->bcodexService->criarCobranca($parcela->emprestimo->parcelas[0]->totalPendente(), $parcela->emprestimo->banco->document);

                            if ($response->successful()) {
                                $parcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                                $parcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                                $parcela->emprestimo->quitacao->save();
                            }
                        }
                    }
                }
            }
        }

        //REFERENTE A LOCACAO
        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $locacao = Locacao::where('identificador', $txId)->first();
                if ($locacao) {
                    $locacao->data_pagamento = $horario;
                    $locacao->save();

                    $details = [
                        'title' => 'Relatório de Emprestimos',
                        'body' => 'This is a test email using MailerSend in Laravel.'
                    ];

                    Mail::to($locacao->company->email)->send(new ExampleEmail($details, $locacao));
                }
            }
        }

        //REFERENTE A PAGAMENTO MINIMO
        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $minimo = PagamentoMinimo::where('identificador', $txId)->first();
                if ($minimo) {

                    $juros = 0;

                    $parcela = Parcela::where('emprestimo_id', $minimo->emprestimo_id)->first();

                    if ($parcela) {

                        $parcela->saldo -= $minimo->valor;

                        //valor usado lá na frente em pagamento minimo
                        $juros = $parcela->emprestimo->juros * $parcela->saldo / 100;

                        $parcela->saldo += $parcela->emprestimo->juros * $parcela->saldo / 100;

                        $qtAtrasadas = 1;
                        $qtAtrasadas += $parcela->atrasadas;

                        $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                        $dataFinalCarbon = Carbon::parse($parcela->venc_real);

                        $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);

                        $diferencaEmMeses++;

                        $parcela->venc_real = Carbon::parse($parcela->venc)->addMonths($diferencaEmMeses);

                        $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document);

                        if ($response->successful()) {
                            $parcela->identificador = $response->json()['txid'];
                            $parcela->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->save();
                        }

                        $parcela->save();

                        if ($parcela->contasreceber) {

                            # MOVIMENTAÇÃO FINANCEIRA DE ENTRADA REFERENTE A BAIXA MANUAL

                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = 'Pagamento Minimo da parcela Nº ' . $parcela->parcela . ' do emprestimo n° ' . $parcela->emprestimo_id;
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $minimo->valor;

                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            # ADICIONANDO O VALOR NO SALDO DO BANCO

                            $parcela->emprestimo->banco->saldo = $parcela->emprestimo->banco->saldo + $minimo->valor;
                            $parcela->emprestimo->banco->save();

                            if ($parcela->emprestimo->quitacao->chave_pix) {


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
            }
        }

        //REFERENTE A QUITACAO
        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $quitacao = Quitacao::where('identificador', $txId)->first();

                if ($quitacao) {
                    $parcelas = Parcela::where('emprestimo_id', $quitacao->emprestimo_id)->get();

                    foreach ($parcelas as $parcela) {
                        $parcela->saldo = 0;
                        $parcela->dt_baixa = Carbon::parse($pix['horario'])->toDateTimeString();
                        $parcela->save();

                        if ($parcela->contasreceber) {

                            # MOVIMENTAÇÃO FINANCEIRA DE ENTRADA REFERENTE A BAIXA MANUAL

                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = 'Quitação da parcela Nº ' . $parcela->parcela . ' do emprestimo n° ' . $parcela->emprestimo_id;
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $parcela->saldo;

                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            # ADICIONANDO O VALOR NO SALDO DO BANCO

                            $parcela->emprestimo->banco->saldo = $parcela->emprestimo->banco->saldo + $parcela->saldo;
                            $parcela->emprestimo->banco->save();
                        }
                    }
                }
            }
        }

        //REFERENTE A PAGAMENTO PERSONALIZADO
        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $pagamento = PagamentoPersonalizado::where('identificador', $txId)->first();

                if ($pagamento) {
                    $pagamento->dt_baixa = $horario;
                    $pagamento->save();

                    $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)->whereNull('dt_baixa')->first();

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
        }

        //REFERENTE A PAGAMENTO SALDO PENDENTE
        if (isset($data['pix']) && is_array($data['pix'])) {
            foreach ($data['pix'] as $pix) {
                $txId = $pix['txId'];
                $valor = $pix['valor'];
                $horario = Carbon::parse($pix['horario'])->toDateTimeString();

                // Encontrar a parcela correspondente
                $pagamento = PagamentoSaldoPendente::where('identificador', $txId)->first();

                if ($pagamento) {

                    $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)->whereNull('dt_baixa')->first();

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

                    $proximaParcela = $parcela->emprestimo->parcelas->firstWhere('dt_baixa', null);

                    if ($proximaParcela) {
                        $pagamento->valor = $proximaParcela->saldo;
                        $pagamento->save();

                        $response = $this->bcodexService->criarCobranca($proximaParcela->saldo, $parcela->emprestimo->banco->document);

                        if ($response->successful()) {
                            $pagamento->identificador = $response->json()['txid'];
                            $pagamento->chave_pix = $response->json()['pixCopiaECola'];
                            $pagamento->save();
                        }
                    }

                    if ($proximaParcela->contasreceber) {
                        $proximaParcela->contasreceber->status = 'Pago';
                        $proximaParcela->contasreceber->dt_baixa = date('Y-m-d');
                        $proximaParcela->contasreceber->forma_recebto = 'PIX';
                        $proximaParcela->contasreceber->save();

                        # MOVIMENTAÇÃO FINANCEIRA DE ENTRADA REFERENTE A BAIXA MANUAL

                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $proximaParcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $proximaParcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = sprintf(
                            'Baixa automática da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                            $proximaParcela->id,
                            $proximaParcela->emprestimo_id,
                            $proximaParcela->emprestimo->client->nome_completo,
                            $pix['pagador']['nome']
                        );
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['parcela_id'] = $proximaParcela->id;
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;

                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        # ADICIONANDO O VALOR NO SALDO DO BANCO

                        $proximaParcela->emprestimo->banco->saldo = $proximaParcela->emprestimo->banco->saldo + $valor;
                        $proximaParcela->emprestimo->banco->save();

                        // $movimentacaoFinanceira = [];
                        // $movimentacaoFinanceira['banco_id'] = $proximaParcela->emprestimo->banco_id;
                        // $movimentacaoFinanceira['company_id'] = $proximaParcela->emprestimo->company_id;
                        // $movimentacaoFinanceira['descricao'] = 'Juros de ' . $proximaParcela->emprestimo->banco->juros . '% referente a baixa automática via pix da proximaParcela Nº ' . $proximaParcela->proximaParcela . ' do emprestimo n° ' . $proximaParcela->emprestimo_id;
                        // $movimentacaoFinanceira['tipomov'] = 'S';
                        // $movimentacaoFinanceira['proximaParcela_id'] = $proximaParcela->id;
                        // $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        // $movimentacaoFinanceira['valor'] = $juros;

                        // Movimentacaofinanceira::create($movimentacaoFinanceira);

                        if ($proximaParcela->emprestimo->quitacao->chave_pix) {

                            $proximaParcela->emprestimo->quitacao->valor = $proximaParcela->emprestimo->proximaParcelas[0]->totalPendente();
                            $proximaParcela->emprestimo->quitacao->saldo = $proximaParcela->emprestimo->proximaParcelas[0]->totalPendente();
                            $proximaParcela->emprestimo->quitacao->save();

                            $response = $this->bcodexService->criarCobranca($proximaParcela->emprestimo->proximaParcelas[0]->totalPendente(), $proximaParcela->emprestimo->banco->document);

                            if ($response->successful()) {
                                $proximaParcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                                $proximaParcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                                $proximaParcela->emprestimo->quitacao->save();
                            }
                        }
                    }
                }
            }
        }

        return response()->json(['message' => 'Baixas realizadas com sucesso.']);
    }

    public function webhookPagamento(Request $request)
    {
        $data = $request->json()->all();

        // Nome do arquivo
        $file = 'webhook.txt';

        // Verifica se o arquivo existe, se não, cria-o
        if (!Storage::exists($file)) {
            Storage::put($file, '');
        }

        // Lê o conteúdo atual do arquivo
        $current = Storage::get($file);

        // Adiciona os novos dados ao conteúdo atual
        $current .= json_encode($data) . PHP_EOL;

        // Salva o conteúdo atualizado no arquivo
        Storage::put($file, $current);

        return response()->json(['message' => 'sucesso']);
    }

    public function corrigirPix()
    {
        $dados = [];

        $dados['Parcela'] = Parcela::whereNull('identificador')
            ->where('saldo', '>', 0)
            ->whereHas('emprestimo.banco', function ($query) {
                $query->where('wallet', true);
            })
            ->get();

        foreach ($dados['Parcela'] as $entidade) {
            $tentativas = 0;
            $maxTentativas = 5;
            $sucesso = false;

            while ($tentativas < $maxTentativas && !$sucesso) {
                try {
                    $response = $this->bcodexService->criarCobranca($entidade->saldo, $entidade->emprestimo->banco->document);

                    if ($response->successful()) {
                        $entidade->identificador = $response->json()['txid'];
                        $entidade->chave_pix = $response->json()['pixCopiaECola'];
                        $entidade->save();
                        $sucesso = true;
                    } else {
                        $tentativas++;
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar cobrança: ' . $e->getMessage());
                    $tentativas++;
                }

                if (!$sucesso && $tentativas >= $maxTentativas) {
                    // Armazenar que não deu certo após 5 tentativas
                    Log::error('Falha ao processar cobrança após 5 tentativas.');
                    // Você pode adicionar lógica adicional aqui para marcar o pagamento como falhado no banco de dados, se necessário
                }
            }
        }

        $dados['PagamentoSaldoPendente'] = PagamentoSaldoPendente::whereNull('identificador')
            ->where('valor', '>', 0)
            ->whereHas('emprestimo.banco', function ($query) {
                $query->where('wallet', true);
            })
            ->get();

        foreach ($dados['PagamentoSaldoPendente'] as $entidade) {
            $tentativas = 0;
            $maxTentativas = 5;
            $sucesso = false;

            while ($tentativas < $maxTentativas && !$sucesso) {
                try {
                    $response = $this->bcodexService->criarCobranca($entidade->valor, $entidade->emprestimo->banco->document);

                    if ($response->successful()) {
                        $entidade->identificador = $response->json()['txid'];
                        $entidade->chave_pix = $response->json()['pixCopiaECola'];
                        $entidade->save();
                        $sucesso = true;
                    } else {
                        $tentativas++;
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar cobrança: ' . $e->getMessage());
                    $tentativas++;
                }

                if (!$sucesso && $tentativas >= $maxTentativas) {
                    // Armazenar que não deu certo após 5 tentativas
                    Log::error('Falha ao processar cobrança após 5 tentativas.');
                    // Você pode adicionar lógica adicional aqui para marcar o pagamento como falhado no banco de dados, se necessário
                }
            }
        }

        $dados['PagamentoMinimo'] = PagamentoMinimo::whereNull('identificador')
            ->where('valor', '>', 0)
            ->whereHas('emprestimo.banco', function ($query) {
                $query->where('wallet', true);
            })
            ->get();

        foreach ($dados['PagamentoMinimo'] as $entidade) {
            $tentativas = 0;
            $maxTentativas = 5;
            $sucesso = false;

            while ($tentativas < $maxTentativas && !$sucesso) {
                try {
                    $response = $this->bcodexService->criarCobranca($entidade->valor, $entidade->emprestimo->banco->document);

                    if ($response->successful()) {
                        $entidade->identificador = $response->json()['txid'];
                        $entidade->chave_pix = $response->json()['pixCopiaECola'];
                        $entidade->save();
                        $sucesso = true;
                    } else {
                        $tentativas++;
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar cobrança: ' . $e->getMessage());
                    $tentativas++;
                }

                if (!$sucesso && $tentativas >= $maxTentativas) {
                    // Armazenar que não deu certo após 5 tentativas
                    Log::error('Falha ao processar cobrança após 5 tentativas.');
                    // Você pode adicionar lógica adicional aqui para marcar o pagamento como falhado no banco de dados, se necessário
                }
            }
        }

        $dados['Quitacao'] = Quitacao::whereNull('identificador')
            ->where('saldo', '>', 0)
            ->whereHas('emprestimo.banco', function ($query) {
                $query->where('wallet', true);
            })
            ->get();

        foreach ($dados['Quitacao'] as $entidade) {
            $tentativas = 0;
            $maxTentativas = 5;
            $sucesso = false;

            while ($tentativas < $maxTentativas && !$sucesso) {
                try {
                    $response = $this->bcodexService->criarCobranca($entidade->saldo, $entidade->emprestimo->banco->document);

                    if ($response->successful()) {
                        $entidade->identificador = $response->json()['txid'];
                        $entidade->chave_pix = $response->json()['pixCopiaECola'];
                        $entidade->save();
                        $sucesso = true;
                    } else {
                        $tentativas++;
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao processar cobrança: ' . $e->getMessage());
                    $tentativas++;
                }

                if (!$sucesso && $tentativas >= $maxTentativas) {
                    // Armazenar que não deu certo após 5 tentativas
                    Log::error('Falha ao processar cobrança após 5 tentativas.');
                    // Você pode adicionar lógica adicional aqui para marcar o pagamento como falhado no banco de dados, se necessário
                }
            }
        }


        return $dados;
    }

    public function aplicarMultaParcela(Request $request, $id)
    {
        $parcela = Parcela::find($id);

        if ($parcela->emprestimo && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado") {
            $valorJuros = 0;

            $juros = $parcela->emprestimo->company->juros ?? 1;

            $valorJuros = (float) number_format($parcela->emprestimo->valor * ($juros  / 100), 2, '.', '');

            $novoValor = $valorJuros + $parcela->saldo;

            if (count($parcela->emprestimo->parcelas) == 1) {
                $novoValor = $parcela->saldo + (1 * $parcela->saldo / 100);
                $valorJuros = (1 * $parcela->saldo / 100);
            }

            $parcela->saldo = $novoValor;
            $parcela->venc_real = date('Y-m-d');
            $parcela->atrasadas = $parcela->atrasadas + 1;

            if ($parcela->emprestimo->banco->wallet) {
                $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document);

                if ($response->successful()) {
                    $parcela->identificador = $response->json()['txid'];
                    $parcela->chave_pix = $response->json()['pixCopiaECola'];
                    $parcela->save();
                }
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

            if ($parcela->emprestimo->pagamentominimo && $parcela->emprestimo->pagamentominimo->chave_pix) {

                $parcela->emprestimo->pagamentominimo->valor += $valorJuros;

                $parcela->emprestimo->pagamentominimo->save();

                $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $parcela->emprestimo->banco->document);

                if ($response->successful()) {
                    $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                    $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                    $parcela->emprestimo->pagamentominimo->save();
                }
            }

            if ($parcela->emprestimo->pagamentosaldopendente && $parcela->emprestimo->pagamentosaldopendente->chave_pix) {

                $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->totalPendenteHoje();

                $parcela->emprestimo->pagamentosaldopendente->save();

                $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $parcela->emprestimo->banco->document);

                if ($response->successful()) {
                    $parcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'];
                    $parcela->emprestimo->pagamentosaldopendente->chave_pix = $response->json()['pixCopiaECola'];
                    $parcela->emprestimo->pagamentosaldopendente->save();
                }
            }
        }
    }

    public function cobrarAmanha(Request $request, $id)
    {

        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $editParcela = Parcela::find($id);

            $parcelas = Parcela::where('emprestimo_id', $editParcela->emprestimo_id)
                ->where('dt_baixa', null)
                ->where('atrasadas', '>', 0)
                ->get();

            foreach ($parcelas as $parcela) {
                $parcela->dt_ult_cobranca = $request->dt_ult_cobranca;
                $parcela->save();
            }

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

            if ($permGroup->contaspagar->status == "Pagamento Efetuado") {
                return response()->json([
                    "message" => "Erro ao excluir emprestimo, pagamento já foi efetuado",
                    "error" => "Erro ao excluir emprestimo, pagamento já foi efetuado"
                ], Response::HTTP_FORBIDDEN);
            }

            $permGroup->contaspagar->delete();

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

    public function enviarMensagem($parcela, $frase)
    {
        try {

            $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

            if ($response->successful()) {
                $r = $response->json();
                if ($r['loggedIn']) {

                    $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                    $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

                    $data = [
                        "numero" => "55" . $telefone,
                        "mensagem" => $frase
                    ];

                    $response = Http::asJson()->post($baseUrl, $data);
                }
            }
        } catch (\Throwable $th) {
            dd($th);
        }

        return true;
    }
}
