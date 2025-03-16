<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Address;
use App\Models\ControleBcodex;
use App\Models\CustomLog;
use App\Models\User;

use DateTime;
use App\Http\Resources\MovimentacaofinanceiraResource;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ControleBcodexController extends Controller
{

    protected $custom_log;

    public function __construct(Customlog $custom_log){
        $this->custom_log = $custom_log;
    }



    public function all(Request $request){

        $this->custom_log->create([
            'user_id' => auth()->user()->id,
            'content' => 'O usuário: '.auth()->user()->nome_completo.' acessou a tela de Controle Bcodes',
            'operation' => 'index'
        ]);

        $itensGeradoNaoPago = ControleBcodex::select(
            DB::raw('COUNT(*) * 0.04 AS total_registros_valor'),
            DB::raw('COUNT(*) AS total_registros')
        )
        ->whereNull('data_pagamento')
        ->first();

        $itensGeradoPago = ControleBcodex::select(
            DB::raw('COUNT(*) * 0.30 AS total_registros_valor'),
            DB::raw('COUNT(*) AS total_registros')
        )
        ->whereNotNull('data_pagamento')
        ->first();

        return [
            'itens_pagos' => $itensGeradoPago,
            'itens_nao_pagos' => $itensGeradoNaoPago
        ];
    }


}
