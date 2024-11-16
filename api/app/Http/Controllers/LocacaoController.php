<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Locacao;
use App\Models\CustomLog;
use App\Models\User;

use App\Http\Resources\JurosResource;
use App\Models\Company;
use App\Models\Emprestimo;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\BcodexService;

class LocacaoController extends Controller
{

    protected $custom_log;

    protected $bcodexService;

    public function __construct(Customlog $custom_log, BcodexService $bcodexService)
    {
        $this->custom_log = $custom_log;
        $this->bcodexService = $bcodexService;
    }

    public function get(Request $request, $id)
    {
        return Locacao::find($id);
    }

    public function dataCorte(Request $request)
    {
        $company = Company::where('id', $request->header('company-id'))->first();

        $quantidade = Emprestimo::where('company_id', $request->header('company-id'))
        ->whereNull('hash_locacao')
        ->count();

        $valor = 0;

        if($company->plano->id == 1){
            $valor = 50;

            if($quantidade > 50){
                $valor = 50 + ($quantidade - 50) * 1.50;
            }
        }

        if($company->plano->id == 2){
            $valor = 100;

            if($quantidade > 100){
                $valor = 100 + ($quantidade - 100) * 1.50;
            }
        }

        if($company->plano->id == 3){
            $valor = 150;

            if($quantidade > 150){
                $valor = 150 + ($quantidade - 150) * 1.50;
            }
        }

        $emprestimos = Emprestimo::where('company_id', $request->header('company-id'))
            ->whereNull('hash_locacao')
            ->get();


        $dataVencimento = Carbon::create(null, null, 15)->toDateString();

        $response = $this->bcodexService->criarCobranca(18.00, '55439708000135');

        if($response->successful()){
            $response = $response->json();
        }

        $locacaoInsert = [
            'type' => $company->plano->nome,
            'data_vencimento'=> $dataVencimento,
            'valor'=> $valor,
            'company_id'=> $request->header('company-id'),
            'chave_pix' => $response['pixCopiaECola'] ?? null,
        ];
        $locacao = Locacao::create($locacaoInsert);

        foreach($emprestimos as $emprestimo){
            $emprestimo->hash_locacao = $locacao->id;
            $emprestimo->save();
        }



    }

    public function update(Request $request)
    {


        DB::beginTransaction();

        try {
            $array = ['error' => ''];

            $user = auth()->user();

            $validator = Validator::make($request->all(), [
                'juros' => 'required',
            ]);

            $dados = $request->all();
            if (!$validator->fails()) {

                $EditJuros = Juros::where('company_id', $request->header('company-id'))->first();

                $EditJuros->juros = $dados['juros'];

                $EditJuros->save();
            } else {
                $array['error'] = $validator->errors()->first();
                return $array;
            }

            DB::commit();

            return $array;
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "message" => "Erro ao editar o Juros.",
                "error" => $e->getMessage()
            ], Response::HTTP_FORBIDDEN);
        }
    }
}
