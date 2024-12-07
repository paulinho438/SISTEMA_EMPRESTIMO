<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\User;
use App\Models\Parcela;
use App\Models\CustomLog;
use App\Models\Permgroup;

use DateTime;
use App\Http\Resources\UsuarioResource;
use App\Http\Resources\ParcelaResource;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log)
    {
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id)
    {
        return new UsuarioResource(User::find($id));
    }

    public function parcelasAtrasadas(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Usuarios',
            'operation' => 'index'
        ]);

        $companyId = $request->header('company-id');

        return ParcelaResource::collection(Parcela::where('atrasadas', '>', 0)
            ->where('dt_baixa', null)
            ->where('valor_recebido', null)
            ->where(function ($query) {
                $today = Carbon::now()->toDateString();
                $query->whereNull('dt_ult_cobranca')
                    ->orWhereDate('dt_ult_cobranca', '!=', $today);
            })
            ->whereHas('emprestimo', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get()->unique('emprestimo_id'));
    }

    public function all(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Usuarios',
            'operation' => 'index'
        ]);

        $user = auth()->user();

        if ($user->login === 'MASTERGERAL') {
            return UsuarioResource::collection(User::all());
        } else {
            $companyId = $request->header('company-id');

            return UsuarioResource::collection(User::whereHas('companies', function ($query) use ($companyId) {
                $query->where('id', $companyId);
            })->get());
        }
    }

    public function allCompany(Request $request)
    {

        $companyId = $request->header('company-id');

        return UsuarioResource::collection(User::whereHas('companies', function ($query) use ($companyId) {
            $query->where('id', $companyId);
        })->get());
    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'login' => 'required|unique:users,login',
            'nome_completo' => 'required',
            'cpf' => 'required',
            'rg' => 'required',
            'data_nascimento' => 'required',
            'sexo' => 'required',
            'email' => 'required',
            'telefone_celular' => 'required',
        ]);

        $dados = $request->all();
        if (!$validator->fails()) {

            $cpf = preg_replace('/[^0-9]/', '', $dados['cpf']);

            $dados['data_nascimento'] = (DateTime::createFromFormat('d/m/Y', $dados['data_nascimento']))->format('Y-m-d');
            $dados['cpf'] = $cpf;
            $dados['password'] = password_hash($dados['password'], PASSWORD_DEFAULT);

            $newGroup = User::create($dados);

            $user = auth()->user();

            if ($user->login === 'MASTERGERAL') {

                if ($dados['empresas'] == null) {
                    return response()->json([
                        "message" => "O usuário deve pertencer a pelo menos uma empresa.",
                        "error" => "O usuário deve pertencer a pelo menos uma empresa."
                    ], Response::HTTP_FORBIDDEN);
                }

                $companyIds = array_map(function ($company) {
                    return $company['id'];
                }, $dados['empresas']);

                // Sincronize as empresas com o usuário
                $newGroup->companies()->sync($companyIds);
            } else {

                $companyId = $request->header('company-id');
                $newGroup->companies()->sync([$companyId]);
            }

            if ($dados['permissao']) {
                // Obter o grupo
                $group = Permgroup::findOrFail($dados['permissao']['id']);
                $group->users()->attach($newGroup->id);
            }



            return $array;
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => $validator->errors()->first()
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
                'nome_completo' => 'required',
                'cpf' => 'required',
                'rg' => 'required',
                'data_nascimento' => 'required',
                'sexo' => 'required',
                'telefone_celular' => 'required',
                'email' => 'required',
            ]);

            $dados = $request->all();


            if (!$validator->fails()) {

                $cpf = preg_replace('/[^0-9]/', '', $dados['cpf']);

                $EditUser = User::find($id);

                if ($dados['password'] != null) {
                    $dados['password'] = password_hash($dados['password'], PASSWORD_DEFAULT);
                } else {
                    $dados['password'] = $EditUser->password;
                }

                $EditUser->nome_completo = $dados['nome_completo'];
                $EditUser->cpf =  $cpf;
                $EditUser->rg = $dados['rg'];
                $EditUser->data_nascimento = (DateTime::createFromFormat('d/m/Y', $dados['data_nascimento']))->format('Y-m-d');
                $EditUser->sexo = $dados['sexo'];
                $EditUser->telefone_celular = $dados['telefone_celular'];
                $EditUser->password = $dados['password'];
                $EditUser->save();





                if ($user->login === 'MASTERGERAL') {

                    $companyIds = array_map(function ($company) {
                        return $company['id'];
                    }, $dados['empresas']);

                    // Sincronize as empresas com o usuário
                    $EditUser->companies()->sync($companyIds);
                }

                if ($dados['permissao']) {
                    // Obter o grupo
                    $t = $EditUser->getGroupByEmpresaId($request->header('company-id') );
                    $t->users()->detach($EditUser->id);

                    $group = Permgroup::findOrFail($dados['permissao']['id']);
                    $group->users()->attach($EditUser->id);

                }

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
                "message" => "Erro ao editar Usere.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }



    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = User::withCount('emprestimos')->findOrFail($id);

            if ($permGroup->emprestimos_count > 0) {
                return response()->json([
                    "message" => "Usuário ainda tem empréstimos associados."
                ], Response::HTTP_FORBIDDEN);
            }

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' deletou o Usere: ' . $id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Usere excluída com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' tentou deletar o Usere: ' . $id . ' ERROR: ' . $e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir Usere.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
