<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Banco;
use App\Models\CustomLog;
use App\Models\User;

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
                if ($dados['efibank'] == 1) {
                    $EditBanco->clienteid = $dados['clienteid'];
                    $EditBanco->clientesecret = $dados['clientesecret'];
                    $EditBanco->chavepix = $dados['chavepix'];
                    $EditBanco->juros = $dados['juros'];
                } else {
                    $EditBanco->clienteid = null;
                    $EditBanco->clientesecret = null;
                    $EditBanco->chavepix = null;
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

            $EditBanco->company->save();

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
}
