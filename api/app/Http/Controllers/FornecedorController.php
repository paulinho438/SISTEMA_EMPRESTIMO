<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\Fornecedor;
use App\Models\CustomLog;
use App\Models\User;

use DateTime;
use App\Http\Resources\FornecedorResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class FornecedorController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }

    /**
     * Verifica se já existe fornecedor com o mesmo CPF/CNPJ (normalizado) na empresa.
     */
    protected function fornecedorCpfcnpjJaExiste(Request $request, string $cpfcnpj, ?int $ignorarId = null): bool
    {
        $companyId = $request->header('company-id');
        $docNormalizado = preg_replace('/\D/', '', $cpfcnpj);

        $query = Fornecedor::where('company_id', $companyId)
            ->where(function ($q) use ($cpfcnpj, $docNormalizado) {
                $q->where('cpfcnpj', $cpfcnpj)
                    ->orWhere('cpfcnpj', $docNormalizado)
                    ->orWhereRaw(
                        'REPLACE(REPLACE(REPLACE(REPLACE(cpfcnpj, ".", ""), "-", ""), "/", ""), " ", "") = ?',
                        [$docNormalizado]
                    );
            });

        if ($ignorarId !== null) {
            $query->where('id', '!=', $ignorarId);
        }

        return $query->exists();
    }

    public function id(Request $r, $id){
        return new FornecedorResource(Fornecedor::find($id));
    }

    public function all(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Fornecedores',
            'operation' => 'index'
        ]);

        return FornecedorResource::collection(Fornecedor::where('company_id', $request->header('company-id'))->get());
    }

    public function insert(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'nome_completo' => 'required',
            'cpfcnpj' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if ($this->fornecedorCpfcnpjJaExiste($request, (string) $value)) {
                        $fail('O CPF/CNPJ já está em uso para esta empresa.');
                    }
                },
            ],
            'telefone_celular_1' => 'required',
            'telefone_celular_2' => 'required',
            'address' => 'required',
            'cep' => 'required',
            'number' => 'required',
            'neighborhood' => 'required',
            'city' => 'required',
        ]);

        $dados = $request->all();
        if(!$validator->fails()){

            $dados['company_id'] = $request->header('company-id');

            $newGroup = Fornecedor::create($dados);

            return $array;

        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => ""
            ], Response::HTTP_FORBIDDEN);
        }

        return $array;
    }

    public function update(Request $request, $id){


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $idFornecedor = (int) $id;

            $validator = Validator::make($request->all(), [
                'nome_completo' => 'required',
                'cpfcnpj' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request, $idFornecedor) {
                        if ($this->fornecedorCpfcnpjJaExiste($request, (string) $value, $idFornecedor)) {
                            $fail('O CPF/CNPJ já está em uso para esta empresa.');
                        }
                    },
                ],
                'telefone_celular_1' => 'required',
                'telefone_celular_2' => 'required',
                'address' => 'required',
                'cep' => 'required',
                'number' => 'required',
                'neighborhood' => 'required',
                'city' => 'required',
            ]);

            $dados = $request->all();
            if(!$validator->fails()){

                $EditFornecedor = Fornecedor::find($id);

                $EditFornecedor->nome_completo = $dados['nome_completo'];
                $EditFornecedor->cpfcnpj = $dados['cpfcnpj'];
                $EditFornecedor->cnpj = $dados['cnpj'] ?? null;
                $EditFornecedor->telefone_celular_1 = $dados['telefone_celular_1'];
                $EditFornecedor->telefone_celular_2 = $dados['telefone_celular_2'];
                $EditFornecedor->address = $dados['address'];
                $EditFornecedor->cep = $dados['cep'];
                $EditFornecedor->number = $dados['number'];
                $EditFornecedor->complement = $dados['complement'];
                $EditFornecedor->neighborhood = $dados['neighborhood'];
                $EditFornecedor->city = $dados['city'];
                $EditFornecedor->observation = $dados['observation'];
                $EditFornecedor->pix_fornecedor = $dados['pix_fornecedor'];
                $EditFornecedor->save();

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
                "message" => "Erro ao editar Fornecedor.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }



    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Fornecedor::findOrFail($id);

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' deletou o Fornecedor: '.$id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Fornecedor excluída com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' tentou deletar o Fornecedor: '.$id.' ERROR: '.$e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir Fornecedor.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
