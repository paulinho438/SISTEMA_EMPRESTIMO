<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\Client;
use App\Models\Parcela;
use App\Models\CustomLog;
use App\Models\User;

use DateTime;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ParcelaResource;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ClientController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id){
        return new ClientResource(Client::find($id));
    }

    public function parcelasAtrasadas(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Clientes Pendentes no APLICATIVO',
            'operation' => 'index'
        ]);

        $companyId = $request->header('company-id');

        return ParcelaResource::collection(Parcela::where('atrasadas', '>', 0)
            ->where('dt_baixa', null)
            ->where('valor_recebido', null)
            ->where(function($query) {
                $today = Carbon::now()->toDateString();
                $query->whereNull('dt_ult_cobranca')
                      ->orWhereDate('dt_ult_cobranca', '!=', $today);
            })
            ->whereHas('emprestimo', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get()->unique('emprestimo_id'));

    }

    public function all(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Clientes',
            'operation' => 'index'
        ]);

        return ClientResource::collection(Client::where('company_id', $request->header('company-id'))->get());
    }

    public function insert(Request $request){
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'nome_completo' => 'required',
            'cpf' => 'required',
            'rg' => 'required',
            'data_nascimento' => 'required',
            'sexo' => 'required',
            'telefone_celular_1' => 'required',
            'telefone_celular_2' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $dados = $request->all();
        if(!$validator->fails()){

            $dados['company_id'] = $request->header('company-id');
            $dados['data_nascimento'] = (DateTime::createFromFormat('d/m/Y', $dados['data_nascimento']))->format('Y-m-d');
            $dados['password'] = password_hash($dados['password'], PASSWORD_DEFAULT);

            $newGroup = Client::create($dados);

            foreach($dados['address'] as $item){
                $item['client_id'] = $newGroup->id;
                Address::create($item);
            }

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
                'nome_completo' => 'required',
                'cpf' => 'required|unique:clients,cpf',
                'rg' => 'required',
                'data_nascimento' => 'required',
                'sexo' => 'required',
                'telefone_celular_1' => 'required',
                'telefone_celular_2' => 'required',
                'email' => 'required',
                'status' => 'required',
            ]);

            $dados = $request->all();
            if(!$validator->fails()){

                $EditClient = Client::find($id);

                $EditClient->nome_completo = $dados['nome_completo'];
                $EditClient->cpf = $dados['cpf'];
                $EditClient->rg = $dados['rg'];
                $EditClient->data_nascimento = (DateTime::createFromFormat('d/m/Y', $dados['data_nascimento']))->format('Y-m-d');
                $EditClient->sexo = $dados['sexo'];
                $EditClient->telefone_celular_1 = $dados['telefone_celular_1'];
                $EditClient->telefone_celular_2 = $dados['telefone_celular_2'];
                $EditClient->status = $dados['status'];
                $EditClient->status_motivo = $dados['status_motivo'];
                $EditClient->observation = $dados['observation'];
                $EditClient->limit = $dados['limit'];
                $EditClient->observation = $dados['observation'];
                $EditClient->save();

                $ids = [];

                foreach($dados['address'] as $item){
                    if(isset($item['id'])){
                        $ids[] = $item['id'];
                    }
                }

                Address::whereNotIn('id', $ids)->where('client_id', $id)->delete();

                foreach($dados['address'] as $item){

                    if(isset($item['id'])){
                        $EditAddress = Address::find($item['id']);
                        $EditAddress->description   = $item['description'];
                        $EditAddress->address       = $item['address'];
                        $EditAddress->cep           = $item['cep'];
                        $EditAddress->number        = $item['number'];
                        $EditAddress->complement    = $item['complement'];
                        $EditAddress->neighborhood  = $item['neighborhood'];
                        $EditAddress->city          = $item['city'];
                        $EditAddress->latitude      = $item['latitude'];
                        $EditAddress->longitude     = $item['longitude'];
                        $EditAddress->save();
                    }else{
                        $item['client_id'] = $id;
                        Address::create($item);
                    }
                }

            } else {
                $array['error'] = $validator->errors()->first();
                return $array;
            }

            DB::commit();

            return $array;

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar cliente.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }



    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Client::findOrFail($id);

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' deletou o Cliente: '.$id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Cliente excluída com sucesso.']);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: '.auth()->user()->nome_completo.' tentou deletar o Cliente: '.$id.' ERROR: '.$e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir cliente.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
