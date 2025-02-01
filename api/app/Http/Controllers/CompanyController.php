<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\User;
use Carbon\Carbon;

use App\Models\Company;

use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\EmpresaResource;

use Illuminate\Support\Facades\DB;

use DateTime;

class CompanyController extends Controller
{
    public function index(Request $r)
    {
        $companies = Company::all();
        return $companies;
    }
    public function get(Request $request, $id)
    {
        $companies = Company::find($id != 'undefined' ? $id : $request->header('company-id'));
        return $companies;
    }

    public function getId(Request $request, $id)
    {
        $companies = Company::find($id);
        return $companies;
    }

    public function getAll(Request $request)
    {
        $companies = EmpresaResource::collection(Company::all());
        return $companies;
    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'company' => 'required',
            'email' => 'required|unique:companies,email',
        ]);

        $dados = $request->all();
        if (!$validator->fails()) {

            $empresa = Company::create($dados);

            $masterGeral = User::where('login', 'MASTERGERAL')->first();

            $usuario = User::create(
                [
                    "nome_completo"             => "MASTER" . $empresa->id,
                    "cpf"                       => "MASTER" . $empresa->id,
                    "rg"                        => "MASTER" . $empresa->id,
                    "login"                     => "MASTER" . $empresa->id,
                    "data_nascimento"           => Carbon::now()->format("Y-m-d"),
                    "sexo"                      => "M",
                    "telefone_celular"          => "(61) 9 9999-9999",
                    "email"                     => "MASTER" . $empresa->id . "@rjemprestimos.combr",
                    "status"                    => "A",
                    "status_motivo"             => "",
                    "tentativas"                => "0",
                    "password"                  => bcrypt("1234"),
                    "created_at"                => Carbon::now()->format("Y-m-d H:i:s"),
                    "updated_at"                => Carbon::now()->format("Y-m-d H:i:s")
                ]
            );

            DB::table("company_user")->insert(
                [
                    "company_id"                => $empresa->id,
                    "user_id"                   => $usuario->id,
                ]
            );

            DB::table("company_user")->insert(
                [
                    "company_id"                => $empresa->id,
                    "user_id"                   => $masterGeral->id,
                ]
            );

            DB::table("costcenter")->insert(
                [
                    "name" => "Default",
                    "description" => "Default",
                    "company_id" => $empresa->id,
                    "created_at" => now(),
                ]
            );

            DB::table("juros")->insert(
                [
                    "juros" => 0.3,
                    "company_id" => $empresa->id,
                ]
            );

            $id = DB::table("permgroups")->insertGetId(
                ["name" => "Super Administrador", "company_id" => $empresa->id]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 1

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 2

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 3

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 4

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 5

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 6

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 7

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 8

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 9

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 10

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 11

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 12

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 13

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 14

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 15

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 16

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 17

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 18

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 19

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 20

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 21

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 22

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 23

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 24

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 25

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 26

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 27

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 28

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 29

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 30

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 31

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 32

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 33

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 34

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 35

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 36

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 37

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 38

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 39

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 40

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 41

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 42

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 43

                ]
            );

            DB::table("permgroup_permitem")->insert(
                [
                    "permgroup_id"     => $id,
                    "permitem_id"      => 44

                ]
            );

            DB::table("permgroup_user")->insert(
                [
                    "permgroup_id"     => $id,
                    "user_id"      => $usuario->id

                ]
            );

            DB::table("permgroup_user")->insert(
                [
                    "permgroup_id"     => $id,
                    "user_id"      => $masterGeral->id

                ]
            );

            DB::table("permgroups")->insert(
                ["name" => "Administrador", "company_id" => $empresa->id]
            );

            DB::table("permgroups")->insert(
                ["name" => "Gerente", "company_id" => $empresa->id]
            );

            DB::table("permgroups")->insert(
                ["name" => "Operador", "company_id" => $empresa->id]
            );

            DB::table("permgroups")->insert(
                ["name" => "Consultor", "company_id" => $empresa->id]
            );



            DB::table("bancos")->insert(
                [
                    "name" => "Banco ITAU",
                    "agencia" => "1234-1",
                    "conta" => "1234-2",
                    "saldo" => 10000,
                    "company_id" => $empresa->id,
                    "created_at" => now(),
                ]
            );

            DB::table("categories")->insert(
                [
                    "name" => "PIX",
                    "description" => "Pagamento Pix",
                    "company_id" => $empresa->id,
                    "created_at" => now(),
                    "standard" => true,
                ]
            );

            DB::table("clients")->insert(
                [
                    "nome_completo" => "Paulo Henrique",
                    "cpf" => "055.463.561-54",
                    "rg" => "2.834.868",
                    "data_nascimento" => "1994-12-09",
                    "sexo" => "M",
                    "telefone_celular_1" => "(61) 9330-5267",
                    "telefone_celular_2" => "(61) 9330-5268",
                    "email" => "paulo.peixoto@gmail.com",
                    "limit" => 1000,
                    "company_id" => $empresa->id,
                    "created_at" => now(),
                    "password" => "1234",
                ]
            );






            return $array;
        } else {
            return response()->json([
                "message" => $validator->errors()->first(),
                "error" => ""
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $dados = $request->all();

            $EditCompany = Company::find($id);

            $EditCompany->ativo = $dados['ativo'];
            $EditCompany->motivo_inativo = $dados['motivo_inativo'] ?? null;
            $EditCompany->whatsapp = $dados['whatsapp'] ?? null;
            $EditCompany->plano_id = $dados['plano_id'] ?? null;
            $EditCompany->juros = $dados['juros'] ?? null;
            $EditCompany->caixa = $dados['caixa'] ?? null;
            $EditCompany->numero_contato = $dados['numero_contato'] ?? null;
            $EditCompany->company = $dados['company'] ?? null;
            $EditCompany->save();


            return $array;
        } catch (\Exception $e) {

            return response()->json([
                "message" => "Erro ao editar Usere.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }

    public function getEnvioAutomaticoRenovacao(Request $request)
    {
        $company = Company::find($request->header('company-id'));

        $company->envio_automatico_renovacao = (bool) $company->envio_automatico_renovacao;

        return $company;
    }

    public function alterEnvioAutomaticoRenovacao(Request $request)
    {
        $company = Company::find($request->header('company-id'));
        $company->envio_automatico_renovacao = !$company->envio_automatico_renovacao;
        $company->save();

        return $company;
    }
}
