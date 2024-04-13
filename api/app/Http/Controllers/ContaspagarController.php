<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\Contaspagar;
use App\Models\CustomLog;
use App\Models\User;

use DateTime;
use App\Http\Resources\ContaspagarResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ContaspagarController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id){
        return new ContaspagarResource(Contaspagar::find($id));
    }

    public function all(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Contas a Pagar',
            'operation' => 'index'
        ]);

        return ContaspagarResource::collection(Contaspagar::where('company_id', $request->header('company-id'))->get());
    }

    public function pagamentoPendentes(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Emprestimos Pendentes',
            'operation' => 'index'
        ]);

        return ContaspagarResource::collection(Contaspagar::where('company_id', $request->header('company-id'))->where('status', 'Aguardando Pagamento')->get());
    }

    public function insert(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'tipodoc' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
        ]);

        $dados = $request->all();
        if(!$validator->fails()){

            $dados['company_id'] = $request->header('company-id');
            $dados['lanc'] = date('Y-m-d');
            $dados['venc'] = date('Y-m-d');
            $dados['costcenter_id'] = $dados['costcenter']['id'];
            $dados['banco_id'] = $dados['banco']['id'];
            $dados['fornecedor_id'] = $dados['fornecedor']['id'];
            $dados['status'] = 'Aguardando Pagamento';
            $newGroup = Contaspagar::create($dados);

            return $newGroup;

        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }


    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Contaspagar::findOrFail($id);

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' deletou o Contas a Pagar: '.$id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Contaspagare excluída com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' tentou deletar o Contas a Pagar: '.$id.' ERROR: '.$e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir Contas a Pagar.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
