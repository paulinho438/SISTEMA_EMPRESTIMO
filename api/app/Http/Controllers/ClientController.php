<?php

namespace App\Http\Controllers;

use App\Models\CobrarAmanhaUltimaLocalizacao;
use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\Client;
use App\Models\Parcela;
use App\Models\CustomLog;
use App\Models\User;
use Illuminate\Validation\Rule;

use DateTime;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ParcelaResource;
use App\Http\Resources\EmprestimoAppResource;


use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ClientController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log)
    {
        $this->custom_log = $custom_log;
    }

    public function id(Request $r, $id)
    {
        return new ClientResource(Client::find($id));
    }

    public function parcelasAtrasadas(Request $request)
    {
        // Log para verificar se a função está sendo chamada
        Log::info('Função parcelasAtrasadas chamada');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Clientes Pendentes no APLICATIVO',
            'operation' => 'index'
        ]);

        $latitude = floatval($request->input('latitude'));
        $longitude = floatval($request->input('longitude'));

        // Obtém o usuário autenticado
        $user = auth()->user();

        // Obtém os IDs das empresas às quais o usuário pertence
        $companyIds = $user->companies->pluck('id')->toArray();

        try {
            $clientes = Parcela::where('dt_baixa', null)
                ->where(function ($query) use ($request) {
                    if (auth()->user()->getGroupNameByEmpresaId($request->header('company-id')) == 'Consultor') {
                        $query->where('atrasadas', '>', 0);
                    }
                })
                ->where(function ($query) use ($request) {
                    if (auth()->user()->getGroupNameByEmpresaId($request->header('company-id')) == 'Consultor') {
                        $today = Carbon::now()->toDateString();
                        $query->whereNull('dt_ult_cobranca')
                            ->orWhereDate('dt_ult_cobranca', '!=', $today);
                    }
                })
                ->where(function ($query) use ($request, $companyIds) {
                    if (auth()->user()->getGroupNameByEmpresaId($request->header('company-id')) == 'Consultor') {
                        $query->whereIn('emprestimos.company_id', $companyIds);
                    } else {
                        $query->where('emprestimos.company_id', $request->header('company-id'));
                    }
                })
                ->join('emprestimos', 'parcelas.emprestimo_id', '=', 'emprestimos.id')
                ->join('clients', 'emprestimos.client_id', '=', 'clients.id')
                ->join('companies', 'emprestimos.company_id', '=', 'companies.id')
                ->leftJoin('address', function ($join) {
                    $join->on('clients.id', '=', 'address.client_id')
                        ->whereRaw('address.id = (SELECT MAX(id) FROM address WHERE address.client_id = clients.id)');
                })
                ->selectRaw("
                parcelas.*,
                clients.nome_completo AS nome_completo,
                clients.telefone_celular_1 AS telefone_celular_1,
                CONCAT('Empresa ',companies.company, ' - ', address.address, ' ', address.neighborhood, ' ' ,address.complement, ' ', address.city, ' ', address.complement ) AS endereco,
                address.latitude,
                address.longitude,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(address.latitude))
                    * cos(radians(address.longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(address.latitude))
                ) / 1000) AS distance,
                (SELECT SUM(valor) FROM movimentacaofinanceira WHERE movimentacaofinanceira.parcela_id IN (SELECT id FROM parcelas WHERE emprestimo_id = emprestimos.id)) AS total_pago_emprestimo,
                (SELECT SUM(saldo) FROM parcelas WHERE emprestimo_id = emprestimos.id AND dt_baixa IS NULL) AS total_pendente
            ", [$latitude, $longitude, $latitude])
                ->orderBy('distance', 'asc')
                ->get()
                ->unique('emprestimo_id');

            // Log para verificar se a consulta retornou resultados
            // Log::info('Consulta executada com sucesso', ['clientes' => $clientes]);

            return response()->json($clientes->values());
        } catch (\Exception $e) {
            // Log para capturar qualquer erro
            Log::error('Erro ao executar a consulta', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro ao buscar parcelas atrasadas'], 500);
        }
    }

    public function mapaClientes(Request $request)
    {

        // return auth()->user()->hasPermission('criar_usuarios');

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Clientes Pendentes no APLICATIVO',
            'operation' => 'index'
        ]);

        $latitude = floatval($request->input('latitude'));
        $longitude = floatval($request->input('longitude'));

        // Obtém o usuário autenticado
        $user = auth()->user();

        // Obtém os IDs das empresas às quais o usuário pertence
        $companyIds = $user->companies->pluck('id')->toArray();


        $clientes = Parcela::where('dt_baixa', null)
            ->where(function ($query) use ($request, $companyIds) {
                $query->whereIn('emprestimos.company_id', $companyIds);
            })
            ->join('emprestimos', 'parcelas.emprestimo_id', '=', 'emprestimos.id')
            ->join('clients', 'emprestimos.client_id', '=', 'clients.id')
            ->join('address', function ($join) {
                $join->on('clients.id', '=', 'address.client_id')
                    ->whereRaw('address.id = (SELECT MIN(id) FROM address WHERE address.client_id = clients.id)');
            })
            ->join('companies', 'emprestimos.company_id', '=', 'companies.id')
            ->selectRaw("
        parcelas.*,
        clients.nome_completo AS nome_completo,
        clients.telefone_celular_1 AS telefone_celular_1,
        emprestimos.company_id AS company_id,
        companies.company AS nome_empresa,
        CONCAT(address.address, ' ', address.neighborhood, ' ', address.complement, ' ', address.city, ' ', address.complement) AS endereco,
        address.latitude,
        address.longitude,
        (6371 * acos(
            cos(radians(?)) * cos(radians(address.latitude))
            * cos(radians(address.longitude) - radians(?))
            + sin(radians(?)) * sin(radians(address.latitude))
        ) / 1000) AS distance,
        (SELECT SUM(valor) FROM movimentacaofinanceira WHERE movimentacaofinanceira.parcela_id IN (SELECT id FROM parcelas WHERE emprestimo_id = emprestimos.id)) AS total_pago_emprestimo,
        (SELECT SUM(saldo) FROM parcelas WHERE emprestimo_id = emprestimos.id AND dt_baixa IS NULL) AS total_pendente
    ", [$latitude, $longitude, $latitude])
            ->orderBy('distance', 'asc')
            ->get()
            ->unique('emprestimo_id');

        return response()->json($clientes->values());



        // return ParcelaResource::collection(Parcela::where('dt_baixa', null)
        //     ->where('valor_recebido', null)
        //     ->where(function ($query) use ($request) {
        //         if (auth()->user()->getGroupNameByEmpresaId($request->header('company-id')) == 'Consultor') {
        //             $query->where('atrasadas', '>', 0);
        //         }
        //     })
        //     ->where(function ($query) use ($request) {
        //         if (auth()->user()->getGroupNameByEmpresaId($request->header('company-id')) == 'Consultor') {
        //             $today = Carbon::now()->toDateString();
        //             $query->whereNull('dt_ult_cobranca')
        //                 ->orWhereDate('dt_ult_cobranca', '!=', $today);
        //         }
        //     })
        //     ->whereHas('emprestimo', function ($query) use ($request) {
        //         $query->where('company_id', $request->header('company-id'));
        //     })
        //     ->get()->unique('emprestimo_id'));
    }

    public function mapaCobrarAmanha(Request $request)
    {

        $data = $request->input('data');

        if (is_null($data)) {
            $data = Carbon::now();
        }

        $localizacoes = CobrarAmanhaUltimaLocalizacao::with(['parcela', 'user'])
            ->where('created_at', '>=', $data)
            ->get();

        $lastLocations = $localizacoes->map(function ($localizacao) {
            $descricao = "FUNC. COBRAR AMANHA - Usuário : {$localizacao->user->nome_completo}  Empréstimo: {$localizacao->parcela->emprestimo->id} Cliente: {$localizacao->parcela->emprestimo->client->nome_completo} Data e hora: ".Carbon::parse($localizacao->created_at)->format('d/m/Y H:i:s');
            return [
                'descricao' => $descricao,
                'latitude' => $localizacao->latitude,
                'longitude' => $localizacao->longitude
            ];
        });
        return response()->json($lastLocations);
    }
    public function mapaConsultor(Request $request)
    {

        // Obtém o usuário autenticado
        $user = auth()->user();

        // Obtém os IDs das empresas às quais o usuário pertence
        $companyIds = $user->companies->pluck('id')->toArray();

        // return User::where("name", "LIKE", "%{$request->name}%")->where('company_id', $request->header('company-id'))->get();
        $users = User::whereHas('groups', function ($query) {
            $query->where('name', 'Consultor');
        })
            ->whereHas('companies', function ($query) use ($companyIds) {
                $query->whereIn('id', $companyIds);
            })
            ->get();

        $lastLocations = $users->map(function ($user) {
            $location = $user->locations()->latest('id')->first();
            return [
                'user_id' => $user->id,
                'user_name' => $user->nome_completo,
                'latitude' => $location ? $location->latitude : null,
                'longitude' => $location ? $location->longitude : null
            ];
        });

        return response()->json($lastLocations);
    }

    public function mapaRotaConsultor(Request $request)
    {
        $dados = $request->all();
        $data = Carbon::parse($dados['data'])->toDateString(); // Converte a data para o formato YYYY-MM-DD

        $user = User::find($dados['consultor']);

        $localizacoes = $user->locations()->whereDate('created_at', $data)->get();

        return response()->json($localizacoes);
    }

    public function all(Request $request)
    {

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Clientes',
            'operation' => 'index'
        ]);

        return ClientResource::collection(Client::where('company_id', $request->header('company-id'))->get());
    }

    public function enviarMensagemMassa(Request $request)
    {
        $dados = $request->all();

        $dtInicio = $request->input('dt_inicio');
        $dtFinal = $request->input('dt_final');

        $dtInicio = Carbon::parse($dtInicio)->format('Y-m-d');
        $dtFinal = Carbon::parse($dtFinal)->format('Y-m-d');

        // Buscar clientes e seus empréstimos
        $clients = Client::whereDoesntHave('emprestimos', function ($query) {
            $query->whereHas('parcelas', function ($query) {
                $query->whereNull('dt_baixa'); // Filtra empréstimos com parcelas pendentes
            });
        })
            ->with(['emprestimos' => function ($query) {
                $query->whereDoesntHave('parcelas', function ($query) {
                    $query->whereNull('dt_baixa'); // Carrega apenas empréstimos sem parcelas pendentes
                });
            }])
            ->whereHas('emprestimos', function ($query) use ($request) {
                $query->where('company_id', $request->header('company-id'));
            })
            ->get();


        // Filtrar os resultados em PHP
        $filteredClients = $clients->filter(function ($client) use ($dtInicio, $dtFinal) {
            $dataQuitacao = $client->emprestimos->data_quitacao;
            return $dataQuitacao >= $dtInicio && $dataQuitacao <= $dtFinal;
        });

        foreach ($filteredClients as $client) {
            if ($dados['status'] == 0) {
                if ($client->emprestimos->count_late_parcels <= 2) {
                    self::enviarMensagem($client, 'Olá ' . $client['nome_completo'] . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($client['emprestimos']['valor'] + 100) . ' Gostaria de contratar?');
                }

                if ($client->emprestimos->count_late_parcels >= 3 && $client->emprestimos->count_late_parcels <= 5) {
                    self::enviarMensagem($client, 'Olá ' . $client['nome_completo'] . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($client['emprestimos']['valor']) . ' Gostaria de contratar?');
                }

                if ($client->emprestimos->count_late_parcels >= 6) {
                    self::enviarMensagem($client, 'Olá ' . $client['nome_completo'] . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($client['emprestimos']['valor'] - 100) . ' Gostaria de contratar?');
                }
            }

            if ($dados['status'] == 1) {
                if ($client->emprestimos->count_late_parcels <= 2) {
                    self::enviarMensagem($client, 'Olá ' . $client['nome_completo'] . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($client['emprestimos']['valor'] + 100) . ' Gostaria de contratar?');
                }
            }

            if ($dados['status'] == 2) {


                if ($client->emprestimos->count_late_parcels >= 3 && $client->emprestimos->count_late_parcels <= 5) {
                    self::enviarMensagem($client, 'Olá ' . $client['nome_completo'] . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($client['emprestimos']['valor']) . ' Gostaria de contratar?');
                }
            }

            if ($dados['status'] == 3) {

                if ($client->emprestimos->count_late_parcels >= 6 && $client->emprestimos->count_late_parcels <= 10) {
                    self::enviarMensagem($client, 'Olá ' . $client['nome_completo'] . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($client['emprestimos']['valor'] - 100) . ' Gostaria de contratar?');
                }
            }
        }

        return response()->json(['message' => 'Mensagens enviadas com sucesso.']);
    }
    public function clientesDisponiveis(Request $request)
    {
        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: ' . auth()->user()->nome_completo . ' acessou a tela de Clientes com Último Empréstimo Finalizado',
            'operation' => 'index'
        ]);

        $clients = Client::where('company_id', $request->header('company-id'))
            ->whereDoesntHave('emprestimos', function ($query) {
                $query->whereHas('parcelas', function ($query) {
                    $query->whereNull('dt_baixa'); // Filtra empréstimos com parcelas pendentes
                });
            })
            ->with(['emprestimos' => function ($query) {
                $query->whereDoesntHave('parcelas', function ($query) {
                    $query->whereNull('dt_baixa'); // Carrega apenas empréstimos sem parcelas pendentes
                });
            }])
            ->get();

        $clients = $clients->sortByDesc(function ($client) {
            return optional($client->emprestimos)->data_quitacao;
        });

        return response()->json($clients->values());
    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'nome_completo' => 'required',
            'cpf' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = DB::table('clients')
                        ->where('cpf', $value)
                        ->where('company_id', $request->header('company-id'))
                        ->exists();

                    if ($exists) {
                        $fail('O CPF já está em uso para esta empresa.');
                    }
                },
            ],
            'rg' => 'required',
            'data_nascimento' => 'required',
            'sexo' => 'required',
            'telefone_celular_1' => 'required',
            'telefone_celular_2' => 'required',
            'email' => 'required',
            'pix_cliente' => 'required'
        ]);

        $dados = $request->all();
        if (!$validator->fails()) {

            $dados['company_id'] = $request->header('company-id');
            $dados['data_nascimento'] = (DateTime::createFromFormat('d/m/Y', $dados['data_nascimento']))->format('Y-m-d');
            $dados['nome_usuario_criacao'] = auth()->user()->nome_completo;

            $newGroup = Client::create($dados);

            foreach ($dados['address'] as $item) {
                $item['client_id'] = $newGroup->id;
                Address::create($item);
            }

            return $array;
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => $validator->errors()->first()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function buscarEmprestimosAndamento(Request $request)
    {
        $user = auth('clientes')->user();

        $array = ['error' => ''];

        $emprestimos = $user->emprestimos()
            ->where('company_id', $request->header('company-id'))
            ->whereHas('parcelas', function ($query) {
                $query->whereNull('dt_baixa'); // Filtra empréstimos com parcelas pendentes
            })
            ->with(['parcelas' => function ($query) {
                $query->whereNull('dt_baixa'); // Carrega apenas parcelas pendentes
            }])
            ->get();

        return response()->json([
            'emprestimos' => EmprestimoAppResource::collection($emprestimos),
            'client' => $user
        ]);
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
                'telefone_celular_1' => 'required',
                'telefone_celular_2' => 'required',
                'email' => 'required',
                'status' => 'required',
                'pix_cliente' => 'required'
            ]);

            $dados = $request->all();
            if (!$validator->fails()) {

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
                $EditClient->pix_cliente = $dados['pix_cliente'];
                $EditClient->save();

                $ids = [];

                foreach ($dados['address'] as $item) {
                    if (isset($item['id'])) {
                        $ids[] = $item['id'];
                    }
                }

                Address::whereNotIn('id', $ids)->where('client_id', $id)->delete();

                foreach ($dados['address'] as $item) {

                    if (isset($item['id'])) {
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
                    } else {
                        $item['client_id'] = $id;
                        Address::create($item);
                    }
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
                "message" => "Erro ao editar cliente.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }



    public function delete(Request $r, $id)
    {


        try {

            $permGroup = Client::withCount('emprestimos')->findOrFail($id);

            if ($permGroup->emprestimos_count > 0) {
                return response()->json([
                    "message" => "Cliente ainda tem empréstimos associados."
                ], Response::HTTP_FORBIDDEN);
            }

            $permGroup->delete();

            DB::commit();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' deletou o Cliente: ' . $id,
                'operation' => 'destroy'
            ]);

            return response()->json(['message' => 'Cliente excluído com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->custom_log->create([
                'user_id' => auth()->user()->id,
                'content' => 'O usuário: ' . auth()->user()->nome_completo . ' tentou deletar o Cliente: ' . $id . ' ERROR: ' . $e->getMessage(),
                'operation' => 'error'
            ]);

            return response()->json([
                "message" => "Erro ao excluir cliente.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function enviarMensagem($cliente, $frase)
    {
        try {

            $response = Http::get($cliente->emprestimos->company->whatsapp . '/logar');

            if ($response->successful()) {
                $r = $response->json();
                if ($r['loggedIn']) {

                    $telefone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                    $baseUrl = $cliente->emprestimos->company->whatsapp . '/enviar-mensagem';

                    $data = [
                        "numero" => "55" . $telefone,
                        "mensagem" => $frase
                    ];

                    $response = Http::asJson()->post($baseUrl, $data);
                    sleep(8);
                }
            }
        } catch (\Throwable $th) {
            dd($th);
        }

        return true;
    }
}
