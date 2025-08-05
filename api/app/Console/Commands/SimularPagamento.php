<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Charge;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class SimularPagamento extends Command
{
    protected $signature = 'simular:pagamento {charge_id}';
    protected $description = 'Simula a liquidação de uma cobrança e aplica as regras financeiras';

    public function handle()
    {
        $chargeId = $this->argument('charge_id');

        $charge = Charge::find($chargeId);

        if (!$charge) {
            $this->error("Cobrança ID {$chargeId} não encontrada.");
            return Command::FAILURE;
        }

        if ($charge->status === 'paid') {
            $this->warn("Cobrança ID {$chargeId} já está liquidada.");
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($charge) {
            $valorLiquidoEmpresa = $charge->valor_bruto - $charge->taxa_gateway - $charge->taxa_cliente;
            $lucroSistema = $charge->taxa_cliente;

            $charge->update([
                'status'        => 'paid',
                'paid_at'       => now(),
                'valor_liquido' => $valorLiquidoEmpresa,
            ]);

            // Crédito para a empresa
            Transaction::create([
                'wallet_id'     => $charge->wallet_id,
                'valor'         => $valorLiquidoEmpresa,
                'tipo'          => 'credit',
                'descricao'     => 'Simulação de liquidação (Pix)',
                'referencia_id' => $charge->id,
                'origem'        => 'simulado',
            ]);

            $charge->wallet->increment('saldo_atual', $valorLiquidoEmpresa);

            // Crédito para o sistema
            $sistemaWalletId = config('financeiro.wallet_sistema_id');
            if ($sistemaWalletId) {
                Transaction::create([
                    'wallet_id'     => $sistemaWalletId,
                    'valor'         => $lucroSistema,
                    'tipo'          => 'credit',
                    'descricao'     => 'Simulação de taxa recebida do cliente',
                    'referencia_id' => $charge->id,
                    'origem'        => 'simulado',
                ]);

                Wallet::find($sistemaWalletId)?->increment('saldo_atual', $lucroSistema);
            }
        });

        $this->info("Cobrança ID {$chargeId} liquidada com sucesso.");
        return Command::SUCCESS;
    }
}
