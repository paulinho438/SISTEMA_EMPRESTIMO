<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Services\MTPAYService;
use Illuminate\Http\Request;

class TransferenciaTesteController extends Controller
{
    public function store(Request $request, MTPAYService $mtpayService)
    {
        try {
            // Aqui pegamos uma wallet qualquer para teste — no real, use a wallet do usuário logado
            $wallet = Wallet::firstOrFail();

            // Dados do PIX
            $pix = [
                'type' => 'cpf',
                'key'  => '055.463.561-54',
            ];

            // Dados do dono do PIX
            $owner = [
                'ip'   => $request->ip(),
                'name' => 'Paulo Henrique Alves Peixoto',
                'document' => [
                    'type'   => 'cpf',
                    'number' => '055.463.561-54',
                ],
            ];

            // Chamando o serviço
            $transfer = $mtpayService->criarTransferencia(
                wallet: $wallet,
                amount: 6, // valor de teste
                pix: $pix,
                owner: $owner,
                discountFeeOfReceiver: false
            );

            return response()->json([
                'success' => true,
                'transfer' => $transfer
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function meuIp(){
        return request()->ip();
    }
}
