<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Banco;
use App\Models\Deposito;
use App\Models\CustomLog;
use App\Models\Parcela;
use App\Models\Movimentacaofinanceira;

use App\Http\Resources\BancosResource;
use App\Http\Resources\BancosComSaldoResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\ControleBcodex;
use Illuminate\Support\Facades\Crypt;

use App\Services\BcodexService;
use App\Services\XGateService;

class BancoController extends Controller
{

    protected $custom_log;

    protected $bcodexService;

    public function __construct(Customlog $custom_log, BcodexService $bcodexService)
    {
        $this->custom_log = $custom_log;
        $this->bcodexService = $bcodexService;
    }

    public function id(Request $r, $id)
    {
        return new BancosComSaldoResource(Banco::find($id));
    }

    public function all(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Bancos',
            'operation' => 'index'
        ]);

        return BancosComSaldoResource::collection(Banco::where('company_id', $request->header('company-id'))->get());
    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'agencia' => 'required',
            'conta' => 'required',
            'saldo' => 'required',
        ]);

        $dados = $request->all();
        if (!$validator->fails()) {

            $dados['company_id'] = $request->header('company-id');
            
            // Definir wallet baseado no bank_type se não foi informado
            if (!isset($dados['wallet'])) {
                $dados['wallet'] = ($dados['bank_type'] ?? 'normal') === 'bcodex' ? 1 : 0;
            }
            
            // Definir bank_type padrão se não foi informado
            if (!isset($dados['bank_type'])) {
                $dados['bank_type'] = isset($dados['wallet']) && $dados['wallet'] == 1 ? 'bcodex' : 'normal';
            }
            
            // Garantir que bank_type seja uma string válida
            $dados['bank_type'] = (string)($dados['bank_type'] ?? 'normal');

            // Campos Velana
            if (($dados['bank_type'] ?? 'normal') === 'velana') {
                // Criptografar chave secreta Velana se fornecida
                if (isset($dados['velana_secret_key']) && !empty($dados['velana_secret_key'])) {
                    $dados['velana_secret_key'] = Crypt::encryptString($dados['velana_secret_key']);
                }
                // Chave pública não precisa ser criptografada (é pública)
                if (isset($dados['velana_public_key']) && empty($dados['velana_public_key'])) {
                    unset($dados['velana_public_key']);
                }
            } else {
                // Limpar campos Velana se não for banco Velana
                $dados['velana_secret_key'] = null;
                $dados['velana_public_key'] = null;
            }

            // Campos XGate
            if (($dados['bank_type'] ?? 'normal') === 'xgate') {
                // Criptografar senha XGate se fornecida
                if (isset($dados['xgate_password']) && !empty($dados['xgate_password'])) {
                    $dados['xgate_password'] = Crypt::encryptString($dados['xgate_password']);
                }
                // Email não precisa ser criptografado
                if (isset($dados['xgate_email']) && empty($dados['xgate_email'])) {
                    unset($dados['xgate_email']);
                }
            } else {
                // Limpar campos XGate se não for banco XGate
                $dados['xgate_email'] = null;
                $dados['xgate_password'] = null;
            }

            // Campos APIX (autenticação: client_id + client_secret → token via POST /api/auth/token)
            if (($dados['bank_type'] ?? 'normal') === 'apix') {
                if (isset($dados['apix_client_secret']) && !empty($dados['apix_client_secret'])) {
                    $dados['apix_client_secret'] = Crypt::encryptString($dados['apix_client_secret']);
                }
                if (isset($dados['apix_base_url']) && empty($dados['apix_base_url'])) {
                    unset($dados['apix_base_url']);
                }
            } else {
                $dados['apix_base_url'] = null;
                $dados['apix_api_key'] = null;
                $dados['apix_client_id'] = null;
                $dados['apix_client_secret'] = null;
            }

            $newGroup = Banco::create($dados);

            return response()->json([
                'data' => $newGroup,
                'message' => 'Banco criado com sucesso!'
            ], Response::HTTP_CREATED);
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => ""
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
                'name' => 'required',
                'agencia' => 'required',
                'conta' => 'required',
                'saldo' => 'required',
            ]);

            $dados = $request->all();
            if (!$validator->fails()) {

                $EditBanco = Banco::find($id);

                $EditBanco->name = $dados['name'];
                $EditBanco->agencia = $dados['agencia'];
                $EditBanco->conta = $dados['conta'];
                $EditBanco->saldo = $dados['saldo'];
                
                // Definir wallet baseado no bank_type se não foi informado
                if (isset($dados['bank_type'])) {
                    $EditBanco->bank_type = (string)$dados['bank_type'];
                    $EditBanco->wallet = ($dados['bank_type'] === 'bcodex') ? 1 : 0;
                } else {
                    $EditBanco->wallet = $dados['wallet'] ?? 0;
                    // Se wallet foi informado mas bank_type não, definir baseado no wallet
                    if (!isset($EditBanco->bank_type)) {
                        $EditBanco->bank_type = ($EditBanco->wallet == 1) ? 'bcodex' : 'normal';
                    }
                }
                
                // Garantir que bank_type seja uma string válida
                $EditBanco->bank_type = (string)($EditBanco->bank_type ?? 'normal');
                
                $EditBanco->info_recebedor_pix = $dados['info_recebedor_pix'] ?? null;
                $EditBanco->chavepix = $dados['chavepix'] ?? null;
                $EditBanco->juros = $dados['juros'] ?? null;

                // Campos Bcodex
                if (($EditBanco->bank_type ?? 'normal') === 'bcodex' || $EditBanco->wallet == 1) {
                    $EditBanco->document = $dados['document'] ?? null;
                    $EditBanco->accountId = $dados['accountId'] ?? null;
                }

                // Campos Cora
                if (($EditBanco->bank_type ?? 'normal') === 'cora') {
                    $EditBanco->client_id = $dados['client_id'] ?? null;
                    $EditBanco->certificate_path = $dados['certificate_path'] ?? null;
                    $EditBanco->private_key_path = $dados['private_key_path'] ?? null;
                }

                // Campos Velana
                if (($EditBanco->bank_type ?? 'normal') === 'velana') {
                    // Criptografar chave secreta Velana se fornecida
                    if (isset($dados['velana_secret_key']) && !empty($dados['velana_secret_key'])) {
                        $EditBanco->velana_secret_key = Crypt::encryptString($dados['velana_secret_key']);
                    } elseif (isset($dados['velana_secret_key']) && empty($dados['velana_secret_key'])) {
                        // Se enviar vazio, manter o valor atual (não sobrescrever)
                        // Não fazer nada, manter o valor existente
                    }
                    // Chave pública (não precisa criptografar, é pública)
                    if (isset($dados['velana_public_key'])) {
                        $EditBanco->velana_public_key = $dados['velana_public_key'];
                    }
                } else {
                    // Limpar campos Velana se não for banco Velana
                    $EditBanco->velana_secret_key = null;
                    $EditBanco->velana_public_key = null;
                }

                // Campos XGate
                if (($EditBanco->bank_type ?? 'normal') === 'xgate') {
                    // Criptografar senha XGate se fornecida
                    if (isset($dados['xgate_password']) && !empty($dados['xgate_password'])) {
                        $EditBanco->xgate_password = Crypt::encryptString($dados['xgate_password']);
                    } elseif (isset($dados['xgate_password']) && empty($dados['xgate_password'])) {
                        // Se enviar vazio, manter o valor atual (não sobrescrever)
                        // Não fazer nada, manter o valor existente
                    }
                    // Email (não precisa criptografar)
                    if (isset($dados['xgate_email'])) {
                        $EditBanco->xgate_email = $dados['xgate_email'];
                    }
                } else {
                    // Limpar campos XGate se não for banco XGate
                    $EditBanco->xgate_email = null;
                    $EditBanco->xgate_password = null;
                }

                // Campos APIX (client_id + client_secret para gerar token)
                if (($EditBanco->bank_type ?? 'normal') === 'apix') {
                    if (isset($dados['apix_client_secret']) && !empty($dados['apix_client_secret'])) {
                        $EditBanco->apix_client_secret = Crypt::encryptString($dados['apix_client_secret']);
                    }
                    if (isset($dados['apix_client_id'])) {
                        $EditBanco->apix_client_id = $dados['apix_client_id'] ?: null;
                    }
                    if (isset($dados['apix_base_url'])) {
                        $EditBanco->apix_base_url = $dados['apix_base_url'] ?: null;
                    }
                } else {
                    $EditBanco->apix_base_url = null;
                    $EditBanco->apix_api_key = null;
                    $EditBanco->apix_client_id = null;
                    $EditBanco->apix_client_secret = null;
                }

                $EditBanco->save();
            } else {
                return response()->json([
                    "message" => $validator->errors()->first(),
                    "error" => $validator->errors()->first()
                ], Response::HTTP_FORBIDDEN);
            }

            DB::commit();

            return response()->json([
                'data' => $EditBanco,
                'message' => 'Banco atualizado com sucesso!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar Banco.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function fechamentocaixa(Request $request, $id)
    {

        $passo = '';

        DB::beginTransaction();

        try {

            $EditBanco = Banco::find($id);

            $EditBanco->saldo += $EditBanco->company->caixa_pix;
            $EditBanco->save();

            $EditBanco->company->caixa_pix = 0;
            $EditBanco->company->save();

            $id = $EditBanco->id;

            // Encontrar a parcela correspondente
            $parcelas = Parcela::whereHas('emprestimo', function ($query) use ($id) {
                $query->where('banco_id', $id)
                    ->whereNull('dt_baixa')
                    ->where('valor_recebido', '>', 0);
            })->get();
            $passo = 'valor recebido';
            foreach ($parcelas as $parcela) {

                //EMPRESTIMOS MENSAL
                if (count($parcela->emprestimo->parcelas) == 1) {

                    $valor = $parcela->valor_recebido;

                    if ($parcela->emprestimo->banco->wallet == 0) {

                        $lucro = $parcela->emprestimo->lucro;

                        $novaParcela = 0;

                        if ($valor > $lucro) {
                            $novaParcela = $parcela->emprestimo->valor - ($valor - $lucro);
                            $parcela->saldo = $novaParcela;
                            $parcela->valor_recebido = 0;
                            $parcela->atrasadas = 0;
                            $parcela->save();

                            $parcela->emprestimo->lucro = ($parcela->emprestimo->juros / 100) * $novaParcela;
                            $parcela->emprestimo->save();

                            $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                            $dataFinalCarbon = Carbon::parse($parcela->venc_real);
                            $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);
                            $diferencaEmMeses++;
                            $parcela->venc_real = Carbon::parse($parcela->dt_lancamento)->addMonths($diferencaEmMeses);
                            $parcela->save();

                            $parcela->saldo += $parcela->emprestimo->lucro;
                            $parcela->save();
                        } else if ($valor == $lucro) {
                            $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                            $dataFinalCarbon = Carbon::parse($parcela->venc_real);
                            $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);
                            $diferencaEmMeses++;
                            $parcela->venc_real = Carbon::parse($parcela->dt_lancamento)->addMonths($diferencaEmMeses);
                            $parcela->atrasadas = 0;
                            $parcela->valor_recebido = 0;
                            $parcela->save();
                        } else {
                            continue;
                        }

                        // MOVIMENTACAO FINANCEIRA
                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo mensal n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        //$parcela->saldo += $parcela->emprestimo->lucro;
                        $parcela->save();

                        continue;
                    }


                    $valor = $parcela->valor_recebido;

                    if (!$parcela->emprestimo->pagamentominimo || !$parcela->emprestimo->pagamentosaldopendente) {
                        //Quando não tiver pagamento minimo quer dizer que o emprestimo foi refinanciado sem opcao de pagamento minimo
                        //Nesse caso o ray falou para só descontar do saldo e fim
                        $parcela->saldo -= $valor;
                        $parcela->valor_recebido = 0;
                        $parcela->save();

                        $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $parcela->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->identificador = $response->json()['txid'];
                            $parcela->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->save();
                        }

                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo mensal n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);
                        continue;
                    }

                    $valor1 = $parcela->emprestimo->pagamentominimo->valor;
                    $valor2 = $parcela->emprestimo->pagamentosaldopendente->valor - $parcela->emprestimo->pagamentominimo->valor;

                    $porcentagem = ($valor1 / $valor2);

                    $parcela->saldo -= $valor;
                    $parcela->valor_recebido = 0;
                    $parcela->save();

                    if ($parcela->saldo != 0) {


                        $novoAntigo = $parcela->saldo;
                        $novoValor = $novoAntigo  + ($novoAntigo * $porcentagem);

                        $parcela->atrasadas = 0;
                        $parcela->saldo = $novoValor;

                        $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                        $dataFinalCarbon = Carbon::parse($parcela->venc_real);

                        $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);

                        $diferencaEmMeses++;

                        $parcela->venc_real = Carbon::parse($parcela->dt_lancamento)->addMonths($diferencaEmMeses);
                        $parcela->save();

                        $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $parcela->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->identificador = $response->json()['txid'];
                            $parcela->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->save();
                        }


                        $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->saldo;

                        $parcela->emprestimo->pagamentosaldopendente->save();

                        $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $parcela->emprestimo->banco->document);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentosaldopendente->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentosaldopendente->save();
                        }

                        $parcela->emprestimo->pagamentominimo->valor = $novoValor - $novoAntigo;

                        $parcela->emprestimo->pagamentominimo->save();

                        // MOVIMENTACAO FINANCEIRA
                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo mensal n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $parcela->emprestimo->banco->document, $parcela->emprestimo->pagamentominimo->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentominimo->save();
                        }
                    } else {
                        $parcela->dt_baixa = date('Y-m-d');
                        $parcela->save();
                    }
                } else {
                    $valor = $parcela->valor_recebido;


                    while ($parcela && $valor > 0) {



                        if ($valor >= $parcela->saldo) {


                            // MOVIMENTACAO FINANCEIRA
                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $parcela->saldo;
                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            if ($parcela->contasreceber) {
                                $parcela->contasreceber->status = 'Pago';
                                $parcela->contasreceber->dt_baixa = date('Y-m-d');
                                $parcela->contasreceber->forma_recebto = 'PIX';
                                $parcela->contasreceber->save();
                            }

                            // Quitar a parcela atual
                            $valor -= $parcela->saldo;
                            $parcela->saldo = 0;
                            $parcela->dt_baixa = date('Y-m-d');
                        } else {

                            // MOVIMENTACAO FINANCEIRA
                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $valor;
                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            $parcela->saldo -= $valor;
                            $valor = 0;

                            $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $parcela->identificador);

                            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                $parcela->identificador = $response->json()['txid'];
                                $parcela->chave_pix = $response->json()['pixCopiaECola'];
                                $parcela->save();
                            }
                        }

                        $parcela->valor_recebido = 0;
                        $parcela->save();



                        if ($parcela->emprestimo->quitacao && $parcela->emprestimo->quitacao->chave_pix) {

                            $response = $this->bcodexService->criarCobranca($parcela->totalPendente(), $parcela->emprestimo->banco->document);

                            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                $parcela->emprestimo->quitacao->identificador = $response->json()['txid'];
                                $parcela->emprestimo->quitacao->chave_pix = $response->json()['pixCopiaECola'];
                                $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                                $parcela->emprestimo->quitacao->save();
                            }
                        }

                        // Encontrar a próxima parcela
                        $parcela = Parcela::where('emprestimo_id', $parcela->emprestimo_id)
                            ->where('id', '>', $parcela->id)
                            ->orderBy('id', 'asc')
                            ->first();
                    }


                    // Encontrar a próxima parcela
                    $parcela = Parcela::where('emprestimo_id', $parcela->emprestimo_id)
                        ->where('id', '>', $parcela->id)
                        ->orderBy('id', 'asc')
                        ->first();
                }
            }


            // Encontrar a parcela correspondente
            $parcelas = Parcela::whereHas('emprestimo', function ($query) use ($id) {
                $query->where('banco_id', $id)
                    ->whereNull('dt_baixa')
                    ->where('valor_recebido_pix', '>', 0);
            })->get();

            foreach ($parcelas as $parcela) {
                $valor = $parcela->valor_recebido_pix;


                if (count($parcela->emprestimo->parcelas) == 1) {
                    $valor = $parcela->valor_recebido_pix;


                    if ($parcela->emprestimo->banco->wallet == 0) {

                        $lucro = $parcela->emprestimo->lucro;

                        $novaParcela = 0;

                        if ($valor > $lucro) {
                            $novaParcela = $parcela->emprestimo->valor - ($valor - $lucro);
                            $parcela->saldo = $novaParcela;
                            $parcela->valor_recebido_pix = 0;
                            $parcela->atrasadas = 0;
                            $parcela->save();

                            $parcela->emprestimo->lucro = ($parcela->emprestimo->juros / 100) * $novaParcela;
                            $parcela->emprestimo->save();

                            $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                            $dataFinalCarbon = Carbon::parse($parcela->venc_real);
                            $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);
                            $diferencaEmMeses++;
                            $parcela->venc_real = $dataInicialCarbon->copy()->addMonths($diferencaEmMeses);
                            $parcela->save();

                            $parcela->saldo += $parcela->emprestimo->lucro;
                            $parcela->save();
                        } else if ($valor == $lucro) {
                            $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                            $dataFinalCarbon = Carbon::parse($parcela->venc_real);
                            $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);
                            $diferencaEmMeses++;
                            $parcela->venc_real = $dataInicialCarbon->copy()->addMonths($diferencaEmMeses);
                            $parcela->atrasadas = 0;
                            $parcela->valor_recebido_pix = 0;
                            $parcela->save();
                        } else {
                            continue;
                        }

                        // MOVIMENTACAO FINANCEIRA
                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo mensal n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        //$parcela->saldo += $parcela->emprestimo->lucro;
                        $parcela->save();

                        continue;
                    }


                    $valor = $parcela->valor_recebido_pix;

                    if (!$parcela->emprestimo->pagamentominimo || !$parcela->emprestimo->pagamentosaldopendente) {
                        //Quando não tiver pagamento minimo quer dizer que o emprestimo foi refinanciado sem opcao de pagamento minimo
                        //Nesse caso o ray falou para só descontar do saldo e fim
                        $parcela->saldo -= $valor;
                        $parcela->valor_recebido = 0;
                        $parcela->save();

                        $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $parcela->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->identificador = $response->json()['txid'];
                            $parcela->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->save();
                        }

                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo mensal n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);
                        continue;
                    }

                    $valor1 = $parcela->emprestimo->pagamentominimo->valor;
                    $valor2 = $parcela->emprestimo->pagamentosaldopendente->valor - $parcela->emprestimo->pagamentominimo->valor;

                    $porcentagem = ($valor1 / $valor2);

                    $parcela->saldo -= $valor;
                    $parcela->valor_recebido_pix = 0;
                    $parcela->save();

                    if ($parcela->saldo != 0) {


                        $novoAntigo = $parcela->saldo;
                        $novoValor = $novoAntigo  + ($novoAntigo * $porcentagem);

                        $parcela->atrasadas = 0;
                        $parcela->saldo = $novoValor;

                        $dataInicialCarbon = Carbon::parse($parcela->dt_lancamento);
                        $dataFinalCarbon = Carbon::parse($parcela->venc_real);

                        $diferencaEmMeses = $dataInicialCarbon->diffInMonths($dataFinalCarbon);

                        $diferencaEmMeses++;

                        $parcela->venc_real = $dataInicialCarbon->copy()->addMonths($diferencaEmMeses);
                        $parcela->save();

                        $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $parcela->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->identificador = $response->json()['txid'];
                            $parcela->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->save();
                        }


                        $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->saldo;

                        $parcela->emprestimo->pagamentosaldopendente->save();

                        $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $parcela->emprestimo->banco->document, $parcela->emprestimo->pagamentosaldopendente->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentosaldopendente->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentosaldopendente->save();
                        }

                        $parcela->emprestimo->pagamentominimo->valor = $novoValor - $novoAntigo;

                        $parcela->emprestimo->pagamentominimo->save();

                        // MOVIMENTACAO FINANCEIRA
                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo mensal n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $valor;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $parcela->emprestimo->banco->document, $parcela->emprestimo->pagamentominimo->identificador);

                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                            $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'];
                            $parcela->emprestimo->pagamentominimo->chave_pix = $response->json()['pixCopiaECola'];
                            $parcela->emprestimo->pagamentominimo->save();
                        }
                    } else {
                        $parcela->dt_baixa = date('Y-m-d');
                        $parcela->save();
                    }
                } else {


                    if ($parcela->emprestimo->extornos) {
                        foreach ($parcela->emprestimo->extornos as $ext) {
                            $ext->delete();
                        }
                    }

                    while ($parcela && $valor > 0) {
                        if ($valor >= $parcela->saldo) {

                            // MOVIMENTACAO FINANCEIRA
                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $parcela->saldo;
                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            if ($parcela->contasreceber) {
                                $parcela->contasreceber->status = 'Pago';
                                $parcela->contasreceber->dt_baixa = date('Y-m-d');
                                $parcela->contasreceber->forma_recebto = 'PIX';
                                $parcela->contasreceber->save();
                            }

                            // Quitar a parcela atual
                            $valor -= $parcela->saldo;
                            $parcela->saldo = 0;
                            $parcela->dt_baixa = date('Y-m-d');
                        } else {

                            // MOVIMENTACAO FINANCEIRA
                            $movimentacaoFinanceira = [];
                            $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                            $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                            $movimentacaoFinanceira['descricao'] = "Fechamento de Caixa - usuário {$parcela->nome_usuario_baixa} realizou a baixa manual da parcela Nº {$parcela->parcela}  do emprestimo n° {$parcela->emprestimo_id} do cliente {$parcela->emprestimo->client->nome_completo}";
                            $movimentacaoFinanceira['tipomov'] = 'E';
                            $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                            $movimentacaoFinanceira['valor'] = $valor;
                            Movimentacaofinanceira::create($movimentacaoFinanceira);

                            $parcela->saldo -= $valor;
                            $valor = 0;

                            $response = $this->bcodexService->criarCobranca($parcela->saldo, $parcela->emprestimo->banco->document, $parcela->identificador);

                            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                $parcela->identificador = $response->json()['txid'];
                                $parcela->chave_pix = $response->json()['pixCopiaECola'];
                                $parcela->save();
                            }
                        }

                        $parcela->valor_recebido_pix = 0;
                        $parcela->save();

                        // Encontrar a próxima parcela
                        $parcela = Parcela::where('emprestimo_id', $parcela->emprestimo_id)
                            ->where('id', '>', $parcela->id)
                            ->orderBy('id', 'asc')
                            ->first();
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Fechamento de Caixa Concluido.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao fechar o Caixa: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                "message" => "Erro ao fechar o Caixa.",
                "error" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function alterarCaixa(Request $request, $id)
    {


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();


            $dados = $request->all();

            $EditBanco = Banco::find($id);

            $EditBanco->saldo = $dados['saldobanco'];

            $EditBanco->save();

            $EditBanco->company->caixa = $dados['saldocaixa'];

            $EditBanco->company->caixa_pix = $dados['saldocaixapix'];

            $EditBanco->company->save();

            DB::commit();

            return response()->json(['message' => 'Alteração de Caixa Concluido.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao fechar o Caixa.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function depositar(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $dados = $request->all();
            $banco = Banco::find($id);

            if (!$banco->wallet && ($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    "message" => "Banco não é do tipo wallet.",
                    "error" => "Banco não é do tipo wallet."
                ], Response::HTTP_FORBIDDEN);
            }

            $bankType = $banco->bank_type ?? ($banco->wallet ? 'bcodex' : 'normal');

            if ($bankType === 'xgate') {
                if (empty($banco->chavepix)) {
                    return response()->json([
                        'message' => 'Para depositar no XGate, cadastre a Chave PIX do banco (CPF/titular) no cadastro do banco.',
                        'error' => 'Chave PIX do banco não configurada.',
                    ], Response::HTTP_FORBIDDEN);
                }
                $xgateService = new XGateService($banco);
                $referenceId = 'dep-caixa-' . $banco->id . '-' . time();
                $response = $xgateService->criarDepositoCaixa((float) $dados['valor'], $referenceId, $banco->chavepix);

                if (!empty($response['success']) && !empty($response['pixCopiaECola'])) {
                    Deposito::create([
                        'banco_id' => $banco->id,
                        'valor' => $dados['valor'],
                        'company_id' => $request->header('company-id'),
                        'identificador' => $response['transaction_id'],
                        'chave_pix' => $response['pixCopiaECola'],
                    ]);

                    return response()->json([
                        'message' => 'Pix criado com sucesso!',
                        'chavepix' => $response['pixCopiaECola'],
                    ]);
                }

                return response()->json([
                    'message' => $response['error'] ?? 'Erro ao criar depósito XGate.',
                    'error' => $response['error'] ?? 'Erro ao criar depósito XGate.',
                ], Response::HTTP_FORBIDDEN);
            }

            // BCodex
            $response = $this->bcodexService->criarCobranca($dados['valor'], $banco->document);

            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                ControleBcodex::create(['identificador' => $response->json()['txid']]);

                Deposito::create([
                    'banco_id' => $banco->id,
                    'valor' => $dados['valor'],
                    'company_id' => $request->header('company-id'),
                    'identificador' => $response->json()['txid'],
                    'chave_pix' => $response->json()['pixCopiaECola'],
                ]);

                return response()->json(['message' => 'Pix criado com sucesso!', 'chavepix' => $response->json()['pixCopiaECola']]);
            }

            return response()->json([
                'message' => 'Erro ao criar PIX.',
                'error' => 'Erro ao criar PIX.',
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao fechar o Caixa.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function saqueConsulta(Request $request, $id)
    {
        try {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' consultou o envio de pix no valor de R$' . $request->valor,
                'operation' => 'index'
            ]);

            $dados = $request->all();
            $banco = Banco::find($id);

            if ($banco->wallet != 1 && ($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    "message" => "Banco não é do tipo wallet.",
                    "error" => "Banco não é do tipo wallet."
                ], Response::HTTP_FORBIDDEN);
            }

            if (!$banco->chavepix) {
                return response()->json([
                    "message" => "Banco não possui chave pix cadastrada!",
                    "error" => 'Banco não possui chave pix cadastrada'
                ], Response::HTTP_FORBIDDEN);
            }

            $bankType = $banco->bank_type ?? ($banco->wallet ? 'bcodex' : 'normal');

            if ($bankType === 'xgate') {
                $xgateService = new XGateService($banco);
                $saldoDisponivel = $this->extrairSaldoXGateParaSaque($xgateService, $banco);
                $valor = (float) ($dados['valor'] ?? 0);

                if ($valor <= 0 || $saldoDisponivel < $valor) {
                    return response()->json([
                        "message" => "Saldo insuficiente para o saque.",
                        "error" => "O banco não possui saldo suficiente para efetuar a transferência"
                    ], Response::HTTP_FORBIDDEN);
                }

                return response()->json([
                    'creditParty' => [
                        'name' => $banco->name ?: 'Conta destino',
                    ],
                    'status' => 'AWAITING_CONFIRMATION',
                ]);
            }

            $accountId = $banco->accountId ?? null;
            if (empty($accountId)) {
                return response()->json([
                    "message" => "Erro ao efetuar saque.",
                    "error" => "Banco não possui Account ID configurado. Configure o Account ID do Bcodex nas configurações do banco."
                ], Response::HTTP_FORBIDDEN);
            }
            $response = $this->bcodexService->consultarChavePix(($dados['valor'] * 100), $banco->chavepix, $accountId);

            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                return $response->json();
            }

            return response()->json([
                "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                "error" => 'O banco não possui saldo suficiente para efetuar a transferencia'
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao efetuar saque.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function efetuarSaque(Request $request, $id)
    {
        try {
            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' consultou o envio de pix no valor de R$' . $request->valor,
                'operation' => 'index'
            ]);

            $dados = $request->all();
            $banco = Banco::find($id);

            if ($banco->wallet != 1 && ($banco->bank_type ?? 'normal') !== 'xgate') {
                return response()->json([
                    "message" => "Banco não é do tipo wallet.",
                    "error" => "Banco não é do tipo wallet."
                ], Response::HTTP_FORBIDDEN);
            }

            if (!$banco->chavepix) {
                return response()->json([
                    "message" => "Banco não possui chave pix cadastrada!",
                    "error" => 'Banco não possui chave pix cadastrada'
                ], Response::HTTP_FORBIDDEN);
            }

            $bankType = $banco->bank_type ?? ($banco->wallet ? 'bcodex' : 'normal');
            $valor = (float) ($dados['valor'] ?? 0);
            $nomeDestino = $banco->name ?: 'Conta destino';

            if ($bankType === 'xgate') {
                $xgateService = new XGateService($banco);
                $response = $xgateService->realizarTransferenciaPix(
                    $valor,
                    $banco->chavepix,
                    'Saque Fechamento de Caixa'
                );

                if (empty($response['success'])) {
                    return response()->json([
                        "message" => $response['error'] ?? "Erro ao efetuar a transferência na XGate.",
                        "error" => $response['error'] ?? "Erro ao efetuar a transferência na XGate."
                    ], Response::HTTP_FORBIDDEN);
                }

                $transactionId = $response['transaction_id'] ?? null;
                $descricao = 'Saque realizado para ' . $nomeDestino;
                if ($transactionId) {
                    $descricao .= ' (ID transação XGate: ' . $transactionId . ')';
                }

                $movimentacaoFinanceira = [
                    'banco_id' => $banco->id,
                    'company_id' => $request->header('company-id'),
                    'descricao' => $descricao,
                    'tipomov' => 'S',
                    'dt_movimentacao' => date('Y-m-d'),
                    'valor' => $valor,
                ];

                $banco->saldo = ($banco->saldo ?? 0) - $valor;
                $banco->save();

                Movimentacaofinanceira::create($movimentacaoFinanceira);

                return response()->json(['message' => 'Saque efetuado com sucesso.']);
            }

            $accountId = $banco->accountId ?? null;
            if (empty($accountId)) {
                return response()->json([
                    "message" => "Erro ao efetuar a transferência.",
                    "error" => "Banco não possui Account ID configurado. Configure o Account ID do Bcodex nas configurações do banco."
                ], Response::HTTP_FORBIDDEN);
            }
            $response = $this->bcodexService->consultarChavePix(($valor * 100), $banco->chavepix, $accountId);

            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                if ($response->json()['status'] == 'AWAITING_CONFIRMATION') {
                    $response = $this->bcodexService->realizarPagamentoPix(($valor * 100), $accountId, $response->json()['paymentId']);

                    if (!$response->successful()) {
                        return response()->json([
                            "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                            "error" => "Erro ao efetuar a transferencia do Emprestimo."
                        ], Response::HTTP_FORBIDDEN);
                    }
                }
            } else {
                return response()->json([
                    "message" => "Erro ao efetuar a transferencia do Emprestimo.",
                    "error" => 'O banco não possui saldo suficiente para efetuar a transferencia'
                ], Response::HTTP_FORBIDDEN);
            }

            $movimentacaoFinanceira = [];
            $movimentacaoFinanceira['banco_id'] = $banco->id;
            $movimentacaoFinanceira['company_id'] = $request->header('company-id');
            $movimentacaoFinanceira['descricao'] = 'Saque realizado para ' . $response->json()['creditParty']['name'];
            $movimentacaoFinanceira['tipomov'] = 'S';
            $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
            $movimentacaoFinanceira['valor'] = $valor;

            $banco->saldo -= $valor;
            $banco->save();

            Movimentacaofinanceira::create($movimentacaoFinanceira);

            return response()->json(['message' => 'Saque efetuado com sucesso.']);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao efetuar saque.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Extrai saldo disponível para saque XGate: tenta API; se 0 ou falha, usa saldo do banco no sistema.
     */
    private function extrairSaldoXGateParaSaque(XGateService $xgateService, Banco $banco): float
    {
        try {
            $saldoResult = $xgateService->consultarSaldo();
            if (empty($saldoResult['success']) || !isset($saldoResult['response'])) {
                return (float) ($banco->saldo ?? 0);
            }
            $resp = $saldoResult['response'];
            if (is_array($resp) && isset($resp['balance']) && is_numeric($resp['balance'])) {
                $s = (float) $resp['balance'];
                if ($s > 0) {
                    return $s;
                }
            }
            if (is_array($resp) && isset($resp['amount']) && is_numeric($resp['amount'])) {
                $s = (float) $resp['amount'];
                if ($s > 0) {
                    return $s;
                }
            }
            if (is_array($resp) && isset($resp['totalAmount']) && is_numeric($resp['totalAmount'])) {
                $s = (float) $resp['totalAmount'];
                if ($s > 0) {
                    return $s;
                }
            }
            if (is_array($resp)) {
                foreach ($resp as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    foreach (['totalAmount', 'balance', 'amount'] as $key) {
                        if (isset($item[$key]) && is_numeric($item[$key])) {
                            $s = (float) $item[$key];
                            if ($s > 0) {
                                return $s;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::channel('xgate')->warning('BancoController saqueConsulta: falha ao consultar saldo XGate - ' . $e->getMessage());
        }
        return (float) ($banco->saldo ?? 0);
    }

    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Banco::findOrFail($id);

            if ($permGroup->emprestimos) {
                return response()->json([
                    "message" => "Banco está relacionado com emprestimo.",
                    "error" => "Banco está relacionado com emprestimo."
                ], Response::HTTP_FORBIDDEN);
            }

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' deletou o Banco: ' . $id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Banco excluído com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' tentou deletar o Banco: ' . $id . ' ERROR: ' . $e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir Banco.",
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
