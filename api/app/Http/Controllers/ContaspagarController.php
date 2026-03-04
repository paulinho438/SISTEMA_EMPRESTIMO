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
use Illuminate\Support\Facades\Storage;
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

        $dados = $request->all();

        // Trata dados vindos de FormData (objetos JSON stringificados)
        if (is_string($dados['costcenter'] ?? null)) {
            $dados['costcenter'] = json_decode($dados['costcenter'], true);
        }
        if (is_string($dados['banco'] ?? null)) {
            $dados['banco'] = json_decode($dados['banco'], true);
        }
        if (is_string($dados['fornecedor'] ?? null)) {
            $dados['fornecedor'] = json_decode($dados['fornecedor'], true);
        }

        $validator = Validator::make($dados, [
            'tipodoc' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
        ]);

        if (!$validator->fails()) {
            $dados['company_id'] = $request->header('company-id');
            $dados['lanc'] = date('Y-m-d');
            $dados['venc'] = isset($dados['venc']) ? $dados['venc'] : date('Y-m-d');
            $dados['costcenter_id'] = $dados['costcenter']['id'] ?? $dados['costcenter_id'] ?? null;
            $dados['banco_id'] = $dados['banco']['id'] ?? $dados['banco_id'] ?? null;
            $dados['fornecedor_id'] = $dados['fornecedor']['id'] ?? $dados['fornecedor_id'] ?? null;
            $dados['status'] = 'Aguardando Pagamento';

            // Upload dos comprovantes (múltiplos anexos)
            if ($request->hasFile('comprovante')) {
                $files = $request->file('comprovante');
                $files = is_array($files) ? $files : [$files];
                $paths = [];
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $paths[] = $file->store('comprovantes', 'public');
                    }
                }
                if (!empty($paths)) {
                    $dados['anexo'] = $paths;
                }
            }

            $newGroup = Contaspagar::create($dados);

            return $newGroup;
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => ""
            ], Response::HTTP_FORBIDDEN);
        }

        return $array;
    }

    public function update(Request $request, $id)
    {
        $contaspagar = Contaspagar::findOrFail($id);

        $dados = $request->all();

        if (is_string($dados['costcenter'] ?? null)) {
            $dados['costcenter'] = json_decode($dados['costcenter'], true);
        }
        if (is_string($dados['banco'] ?? null)) {
            $dados['banco'] = json_decode($dados['banco'], true);
        }
        if (is_string($dados['fornecedor'] ?? null)) {
            $dados['fornecedor'] = json_decode($dados['fornecedor'], true);
        }

        $validator = Validator::make($dados, [
            'tipodoc' => 'required',
            'descricao' => 'required',
            'valor' => 'required',
        ]);

        if (!$validator->fails()) {
            $contaspagar->tipodoc = $dados['tipodoc'];
            $contaspagar->descricao = $dados['descricao'];
            $contaspagar->valor = $dados['valor'];
            $contaspagar->costcenter_id = $dados['costcenter']['id'] ?? $dados['costcenter_id'] ?? $contaspagar->costcenter_id;
            $contaspagar->banco_id = $dados['banco']['id'] ?? $dados['banco_id'] ?? $contaspagar->banco_id;
            $contaspagar->fornecedor_id = $dados['fornecedor']['id'] ?? $dados['fornecedor_id'] ?? $contaspagar->fornecedor_id;

            if (isset($dados['venc'])) {
                $contaspagar->venc = $dados['venc'];
            }
            if (isset($dados['cod_barras'])) {
                $contaspagar->cod_barras = $dados['cod_barras'];
            }

            if ($request->hasFile('comprovante')) {
                $anexosAtuais = is_array($contaspagar->anexo) ? $contaspagar->anexo : ($contaspagar->anexo ? [$contaspagar->anexo] : []);
                foreach ($anexosAtuais as $path) {
                    Storage::disk('public')->delete($path);
                }
                $files = $request->file('comprovante');
                $files = is_array($files) ? $files : [$files];
                $paths = [];
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $paths[] = $file->store('comprovantes', 'public');
                    }
                }
                if (!empty($paths)) {
                    $contaspagar->anexo = $paths;
                }
            }

            $contaspagar->save();

            return $contaspagar;
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => ""
            ], Response::HTTP_FORBIDDEN);
        }
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
