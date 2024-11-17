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
use Illuminate\Support\Str;

use App\Mail\EmailCobrancaPlataforma;
use Illuminate\Support\Facades\Mail;


class LocacaoController extends Controller
{

    protected $custom_log;

    protected $bcodexService;

    public function __construct(Customlog $custom_log, BcodexService $bcodexService)
    {
        $this->custom_log = $custom_log;
        $this->bcodexService = $bcodexService;
    }

    public function all(Request $request, $id)
    {
        return Locacao::orderBy('id', 'desc')->get();
    }

    public function dataCorte(Request $request, $id)
    {
        $company = Company::where('id', $id)->first();

        $quantidade = Emprestimo::where('company_id', $id)
        ->whereNull('hash_locacao')
        ->count();

        $valor = 0;

        if($company->plano->id == 1){
            $valor = 49.90;

            if($quantidade > 35){
                $valor = 49.90 + ($quantidade - 35) * 1.99;
            }
        }

        if($company->plano->id == 2){
            $valor = 100;

            if($quantidade > 100){
                $valor = 100 + ($quantidade - 100) * 1.99;
            }
        }

        if($company->plano->id == 3){
            $valor = 150;

            if($quantidade > 150){
                $valor = 150 + ($quantidade - 150) * 1.99;
            }
        }

        $emprestimos = Emprestimo::where('company_id', $id)
            ->whereNull('hash_locacao')
            ->get();


        $dataVencimento = Carbon::create(null, null, 15)->toDateString();

        $response = $this->bcodexService->criarCobranca($valor, '55439708000135');

        if($response->successful()){
            $response = $response->json();
        }
        $hashId = md5(now()->format('YmdHis'));

        $locacaoInsert = [
            'id' => $hashId,
            'type' => $company->plano->nome,
            'data_vencimento'=> $dataVencimento,
            'valor'=> $valor,
            'company_id'=> $id,
            'chave_pix' => $response['pixCopiaECola'] ?? null,
            'identificador' => $response['txid'] ?? null
        ];

        $locacao = Locacao::create($locacaoInsert);

        foreach($emprestimos as $emprestimo){
            $emprestimo->hash_locacao = $hashId;
            $emprestimo->save();
        }

        $details = [
            'title' => 'Cobrança de Plataforma',
            'body' => 'This is a test email using MailerSend in Laravel.'
        ];

        Mail::to($locacao->company->email)->send(new EmailCobrancaPlataforma($details, $locacao));

        return response()->json($locacao, Response::HTTP_CREATED);

    }

}
