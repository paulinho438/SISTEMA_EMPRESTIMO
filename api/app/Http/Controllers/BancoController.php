<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Banco;
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

class BancoController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log)
    {
        $this->custom_log = $custom_log;
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
            'efibank' => 'required',
        ]);

        $dados = $request->all();
        if (!$validator->fails()) {

            $dados['company_id'] = $request->header('company-id');

            if (isset($_FILES['certificado'])) {

                $certificado = $request->file('certificado');

                // Gerar um nome único para o arquivo
                $nomeArquivo = Str::uuid() . '.' . $certificado->getClientOriginalExtension();

                // Salvar o arquivo na pasta 'public/fotos'
                $caminhoArquivo = $certificado->storeAs('public/documentos', $nomeArquivo);

                $dados['certificado'] = 'storage/documentos/' . $nomeArquivo;
            }

            $newGroup = Banco::create($dados);

            return $array;
        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
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
                'efibank' => 'required',
            ]);

            $dados = $request->all();
            if (!$validator->fails()) {

                $EditBanco = Banco::find($id);

                $EditBanco->name = $dados['name'];
                $EditBanco->agencia = $dados['agencia'];
                $EditBanco->conta = $dados['conta'];
                $EditBanco->saldo = $dados['saldo'];
                $EditBanco->efibank = $dados['efibank'];
                $EditBanco->info_recebedor_pix = $dados['info_recebedor_pix'];

                if ($dados['efibank'] == 1) {
                    $EditBanco->clienteid = $dados['clienteid'];
                    $EditBanco->clientesecret = $dados['clientesecret'];
                    $EditBanco->chavepix = $dados['chavepix'];
                    $EditBanco->juros = $dados['juros'];
                } else {
                    $EditBanco->clienteid = null;
                    $EditBanco->clientesecret = null;
                    $EditBanco->chavepix = $dados['chavepix'];
                    $EditBanco->juros = null;
                }


                if (isset($_FILES['certificado'])) {

                    $certificado = $request->file('certificado');

                    // Gerar um nome único para o arquivo
                    $nomeArquivo = Str::uuid() . '.' . $certificado->getClientOriginalExtension();

                    // Salvar o arquivo na pasta 'public/fotos'
                    $caminhoArquivo = $certificado->storeAs('public/documentos', $nomeArquivo);

                    $dados['certificado'] = $nomeArquivo;
                }

                $EditBanco->certificado = $dados['certificado'];


                $EditBanco->save();
            } else {
                $array['error'] = $validator->errors()->first();
                return $array;
            }

            DB::commit();

            return $array;
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

            $id = $EditBanco->id;


            // Encontrar a parcela correspondente
            $parcelas = Parcela::whereHas('emprestimo', function ($query) use ($id) {
                $query->where('banco_id', $id)
                    ->whereNull('dt_baixa')
                    ->where('valor_recebido', '>', 0);
            })->get();

            foreach ($parcelas as $parcela) {
                $valor = $parcela->valor_recebido;

                while ($parcela && $valor > 0) {
                    if ($valor >= $parcela->saldo) {

                        // MOVIMENTACAO FINANCEIRA
                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = 'Fechamento de Caixa - Baixa da parcela Nº ' . $parcela->parcela . ' do emprestimo n° ' . $parcela->emprestimo_id;
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $parcela->saldo;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        $parcela->contasreceber->status = 'Pago';
                        $parcela->contasreceber->dt_baixa = date('Y-m-d');
                        $parcela->contasreceber->forma_recebto = 'PIX ou Dinheiro';
                        $parcela->contasreceber->save();

                        // Quitar a parcela atual
                        $valor -= $parcela->saldo;
                        $parcela->saldo = 0;
                        $parcela->dt_baixa = date('Y-m-d');

                    } else {

                        // MOVIMENTACAO FINANCEIRA
                        $movimentacaoFinanceira = [];
                        $movimentacaoFinanceira['banco_id'] = $parcela->emprestimo->banco_id;
                        $movimentacaoFinanceira['company_id'] = $parcela->emprestimo->company_id;
                        $movimentacaoFinanceira['descricao'] = 'Fechamento de Caixa - Baixa da parcial da parcela Nº ' . $parcela->parcela . ' do emprestimo n° ' . $parcela->emprestimo_id;
                        $movimentacaoFinanceira['tipomov'] = 'E';
                        $movimentacaoFinanceira['parcela_id'] = $parcela->id;
                        $movimentacaoFinanceira['dt_movimentacao'] = date('Y-m-d');
                        $movimentacaoFinanceira['valor'] = $parcela->saldo;
                        Movimentacaofinanceira::create($movimentacaoFinanceira);

                        $parcela->saldo -= $valor;
                        $valor = 0;
                    }

                    $parcela->valor_recebido = 0;
                    $parcela->save();


                    // Encontrar a próxima parcela
                    $parcela = Parcela::where('emprestimo_id', $parcela->emprestimo_id)
                        ->where('id', '>', $parcela->id)
                        ->orderBy('id', 'asc')
                        ->first();
                }
            }


            // foreach ($parcelas as $parcela) {

            //     $parcela->saldo = $parcela->saldo - $parcela->valor_recebido;
            //     $parcela->valor_recebido = 0;

            //     if ($parcela->saldo == $parcela->valor_recebido) {
            //         $parcela->dt_baixa = date('Y-m-d');
            //     }

            //     $parcela->save();

            //     if ($parcela->chave_pix) {
            //         $gerarPix = self::gerarPix(
            //             [
            //                 'banco' => [
            //                     'client_id' => $parcela->emprestimo->banco->clienteid,
            //                     'client_secret' => $parcela->emprestimo->banco->clientesecret,
            //                     'certificado' => $parcela->emprestimo->banco->certificado,
            //                     'chave' => $parcela->emprestimo->banco->chavepix,
            //                 ],
            //                 'parcela' => [
            //                     'parcela' => $parcela->parcela,
            //                     'valor' => $parcela->saldo,
            //                     'venc_real' => date('Y-m-d'),
            //                 ],
            //                 'cliente' => [
            //                     'nome_completo' => $parcela->emprestimo->client->nome_completo,
            //                     'cpf' => $parcela->emprestimo->client->cpf
            //                 ]
            //             ]
            //         );

            //         $parcela->identificador = $gerarPix['identificador'];
            //         $parcela->chave_pix = $gerarPix['chave_pix'];
            //         $parcela->save();
            //     }

            //     if ($parcela->emprestimo->quitacao->chave_pix) {

            //         $parcela->emprestimo->quitacao->valor = $parcela->emprestimo->parcelas[0]->totalPendente();
            //         $parcela->emprestimo->quitacao->saldo = $parcela->emprestimo->parcelas[0]->totalPendente();
            //         $parcela->emprestimo->quitacao->save();

            //         $gerarPixQuitacao = self::gerarPixQuitacao(
            //             [
            //                 'banco' => [
            //                     'client_id' => $parcela->emprestimo->banco->clienteid,
            //                     'client_secret' => $parcela->emprestimo->banco->clientesecret,
            //                     'certificado' => $parcela->emprestimo->banco->certificado,
            //                     'chave' => $parcela->emprestimo->banco->chavepix,
            //                 ],
            //                 'parcela' => [
            //                     'parcela' => $parcela->parcela,
            //                     'valor' => $parcela->emprestimo->parcelas[0]->totalPendente(),
            //                     'venc_real' => date('Y-m-d'),
            //                 ],
            //                 'cliente' => [
            //                     'nome_completo' => $parcela->emprestimo->client->nome_completo,
            //                     'cpf' => $parcela->emprestimo->client->cpf
            //                 ]
            //             ]
            //         );

            //         $parcela->emprestimo->quitacao->identificador = $gerarPixQuitacao['identificador'];
            //         $parcela->emprestimo->quitacao->chave_pix = $gerarPixQuitacao['chave_pix'];

            //         $parcela->emprestimo->quitacao->save();
            //     }
            // }



            DB::commit();

            return response()->json(['message' => 'Fechamento de Caixa Concluido.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao fechar o Caixa.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Banco::findOrFail($id);

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
