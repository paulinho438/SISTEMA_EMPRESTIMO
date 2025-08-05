<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Charge;
use App\Models\Transaction;
use App\Models\WebhookLog;
use App\Models\Wallet;

class PixWebhookController extends Controller
{
    public function handle(Request $request, $token)
    {
        $payload = $request->all();

        // Log do payload completo
        WebhookLog::create(['payload' => $payload]);

        // Confirma se o evento é de pagamento concluído
        if (($payload['event'] ?? '') !== 'TRANSACTION_PAID') {
            return response()->json(['ignored' => true, 'reason' => 'Not a payment event'], 200);
        }

        // Busca o identificador da transação
        $externalId = $payload['transaction']['identifier'] ?? null;

        if (!$externalId) {
            return response()->json(['error' => 'Missing transaction identifier'], 400);
        }

        // Localiza a cobrança no sistema
        $charge = Charge::where('external_transaction_id', $externalId)
            ->where('webhook_token', $token)
            ->first();

        if (!$charge || $charge->status === 'paid') {
            return response()->json(['ignored' => true, 'reason' => 'Already processed or not found'], 200);
        }

        // Liquidação da cobrança
        DB::transaction(function () use ($charge) {
            $valorLiquidoEmpresa = $charge->valor_bruto - $charge->taxa_gateway - $charge->taxa_cliente;
            $lucroSistema = $charge->taxa_cliente;

            $charge->update([
                'status'         => 'paid',
                'paid_at'        => now(),
                'valor_liquido'  => $valorLiquidoEmpresa,
            ]);

            // 💰 Crédito na wallet da empresa
            Transaction::create([
                'wallet_id'     => $charge->wallet_id,
                'valor'         => $valorLiquidoEmpresa,
                'tipo'          => 'credit',
                'descricao'     => 'Liquidação Pix (cliente)',
                'referencia_id' => $charge->id,
                'origem'        => 'gateway',
            ]);

            $charge->wallet->increment('saldo_atual', $valorLiquidoEmpresa);

            // 🏦 Crédito no caixa do sistema
            $sistemaWalletId = config('financeiro.wallet_sistema_id');
            if ($sistemaWalletId) {
                Transaction::create([
                    'wallet_id'     => $sistemaWalletId,
                    'valor'         => $lucroSistema,
                    'tipo'          => 'credit',
                    'descricao'     => 'Taxa cobrada do cliente',
                    'referencia_id' => $charge->id,
                    'origem'        => 'taxa_cliente',
                ]);

                Wallet::find($sistemaWalletId)?->increment('saldo_atual', $lucroSistema);
            }
        });

        return response()->json(['success' => true], 200);
    }
}
