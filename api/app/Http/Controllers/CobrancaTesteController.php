<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Charge;
use App\Services\MTPAYService;

class CobrancaTesteController extends Controller
{
    public function store(Request $request, MTPAYService $gateway)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'valor'     => 'required|numeric|min:0.01',
            'name'      => 'required|string',
            'email'     => 'required|email',
            'phone'     => 'required|string',
            'document'  => 'required|string',
        ]);

        $wallet = Wallet::findOrFail($request->wallet_id);

        $cliente = [
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'document' => $this->formatarDocumento($request->document),
        ];

        $valorServico = $request->valor;
        $taxaCliente = 0.20;

        // Cria a cobrança via MTPay
        $charge = $gateway->criarCobranca($wallet, $cliente, $valorServico, $taxaCliente);

        // Simula liquidação imediata (para testes)
        DB::transaction(function () use ($charge) {
            $valorLiquidoEmpresa = $charge->valor_bruto - $charge->taxa_gateway - $charge->taxa_cliente;
            $lucroSistema = $charge->taxa_cliente;

            $charge->update([
                'status'        => 'paid',
                'paid_at'       => now(),
                'valor_liquido' => $valorLiquidoEmpresa,
            ]);

            // Empresa recebe valor líquido
            Transaction::create([
                'wallet_id'     => $charge->wallet_id,
                'valor'         => $valorLiquidoEmpresa,
                'tipo'          => 'credit',
                'descricao'     => 'Simulação de cobrança Pix paga',
                'referencia_id' => $charge->id,
                'origem'        => 'gateway',
            ]);

            $charge->wallet->increment('saldo_atual', $valorLiquidoEmpresa);

            // Plataforma recebe a taxa_cliente
            $sistemaWalletId = config('financeiro.wallet_sistema_id');
            if ($sistemaWalletId) {
                Transaction::create([
                    'wallet_id'     => $sistemaWalletId,
                    'valor'         => $lucroSistema,
                    'tipo'          => 'credit',
                    'descricao'     => 'Lucro (taxa cliente - cobrança de teste)',
                    'referencia_id' => $charge->id,
                    'origem'        => 'gateway',
                ]);

                Wallet::find($sistemaWalletId)?->increment('saldo_atual', $lucroSistema);
            }
        });

        return response()->json([
            'message' => 'Cobrança de teste criada e liquidada com sucesso.',
            'charge'  => $charge,
        ]);
    }

    private function formatarDocumento(string $doc): string
    {
        $doc = preg_replace('/\D/', '', $doc);

        // Se for número: CPF ou CNPJ
        if (is_numeric($doc)) {
            if (strlen($doc) === 11) return $doc;      // CPF
            if (strlen($doc) === 14) return $doc;      // CNPJ
        }

        // Se for e-mail ou chave aleatória, retorna como está
        return $doc;
    }
}
