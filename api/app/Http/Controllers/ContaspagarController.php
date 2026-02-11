<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\Contaspagar;
use App\Models\CustomLog;
use App\Models\User;
use App\Models\Banco;

use DateTime;
use App\Http\Resources\ContaspagarResource;
use App\Http\Resources\ContaspagarAprovacaoResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContaspagarController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log)
    {
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id)
    {
        $contaspagar = Contaspagar::with([
            'banco.company',  // BancosResource precisa de company
            'emprestimo',
            'fornecedor',
            'costcenter'
        ])->findOrFail($id);
        
        return new ContaspagarResource($contaspagar);
    }

    public function all(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Contas a Pagar',
            'operation' => 'index'
        ]);

        // Parâmetros de paginação
        $perPage = $request->input('per_page', 15); // Padrão: 15 itens por página
        $page = $request->input('page', 1);

        // Eager loading de todos os relacionamentos para evitar N+1 queries
        // Usando paginate() em vez de get() para paginação no backend
        $contaspagar = Contaspagar::where('company_id', $request->header('company-id'))
            ->with([
                'banco.company',  // BancosResource precisa de company
                'emprestimo',
                'fornecedor',
                'costcenter'
            ])
            ->orderBy('id', 'desc') // Ordenar por ID descendente (mais recentes primeiro)
            ->paginate($perPage, ['*'], 'page', $page);

        return ContaspagarResource::collection($contaspagar);
    }

    public function pagamentoPendentes(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Emprestimos Pendentes',
            'operation' => 'index'
        ]);

        // Parâmetros de paginação
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        // Eager loading de todos os relacionamentos para evitar N+1 queries
        $contaspagar = Contaspagar::where('company_id', $request->header('company-id'))
            ->where('status', 'Aguardando Pagamento')
            ->with([
                'banco.company',  // BancosResource precisa de company
                'emprestimo',
                'fornecedor',
                'costcenter'
            ])
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return ContaspagarResource::collection($contaspagar);
    }

    public function pagamentoPendentesAplicativo(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Emprestimos Pendentes Aplicativo',
            'operation' => 'index'
        ]);

        // Eager loading de todos os relacionamentos para evitar N+1 queries
        $contaspagar = Contaspagar::where('company_id', $request->header('company-id'))
            ->where('status', 'Aguardando Pagamento')
            ->with([
                'banco.company',  // BancosComSaldoResource precisa de company
                'emprestimo.client',  // ContaspagarAprovacaoResource acessa emprestimo->client
                'emprestimo.parcelas',  // ContaspagarAprovacaoResource acessa emprestimo->parcelas
                'fornecedor'
            ])
            ->orderBy('id', 'desc')
            ->get();

        return ContaspagarAprovacaoResource::collection($contaspagar);
    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'tipodoc' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
        ]);

        $dados = $request->all();

        if (!$validator->fails()) {

            $dados['company_id'] = $request->header('company-id');
            $dados['lanc'] = date('Y-m-d');
            $dados['venc'] = date('Y-m-d');
            $dados['costcenter_id'] = $dados['costcenter']['id'];
            $dados['banco_id'] = $dados['banco']['id'];
            $dados['fornecedor_id'] = $dados['fornecedor']['id'];
            $dados['status'] = 'Aguardando Pagamento';
            $newGroup = Contaspagar::create($dados);

            //conferir depois se vai ser necessário
            // $banco = Banco::find($dados['banco']['id']);

            // if ($banco) {
            //     $banco->saldo = $banco->saldo - $dados['valor'];
            //     $banco->save();
            // }

            return $newGroup;
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => ""
            ], Response::HTTP_FORBIDDEN);
        }

        return $array;
    }


    public function delete(Request $r, $id)
    {
        DB::beginTransaction();

        try {
            $permGroup = Contaspagar::findOrFail($id);

            if ($permGroup->status == "Pagamento Efetuado") {
                return response()->json([
                    "message" => "Erro ao excluir o contas a pagar, pagamento já foi efetuado",
                    "error" => "Erro ao excluir contas a pagar, pagamento já foi efetuado"
                ], Response::HTTP_FORBIDDEN);
            }

            //conferir depois, se vai ser necessário
            // $permGroup->banco->saldo = $permGroup->banco->saldo + $permGroup->valor;
            // $permGroup->banco->save();
            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' deletou o Contas a Pagar: ' . $id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Contaspagar excluída com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' tentou deletar o Contas a Pagar: ' . $id . ' ERROR: ' . $e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir Contas a Pagar.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
