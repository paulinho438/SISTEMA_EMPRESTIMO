<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Costcenter;
use App\Models\CustomLog;
use App\Models\User;

use App\Http\Resources\CostcenterResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class CostcenterController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id){
        return new CostcenterResource(Costcenter::find($id));
    }

    public function all(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Centro de Custo',
            'operation' => 'index'
        ]);

        return CostcenterResource::collection(Costcenter::where('company_id', $request->header('company-id'))->get());
    }

    public function insert(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        $dados = $request->all();
        if(!$validator->fails()){

            $dados['company_id'] = $request->header('company-id');

            $newGroup = Costcenter::create($dados);

            return $array;

        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function update(Request $request, $id){


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'description' => 'required',
            ]);

            $dados = $request->all();
            if(!$validator->fails()){

                $EditCostcenter = Costcenter::find($id);

                $EditCostcenter->name = $dados['name'];
                $EditCostcenter->description = $dados['description'];

                $EditCostcenter->save();

            } else {
                $array['error'] = $validator->errors()->first();
                return $array;
            }

            DB::commit();

            return $array;

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar Centro de Custo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }



    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $costCenter = Costcenter::findOrFail($id);

            $costCenter->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' deletou o Centro de Custo: '.$id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Centro de Custo excluído com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' tentou deletar o Centro de Custo: '.$id.' ERROR: '.$e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir Centro de Custo.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
