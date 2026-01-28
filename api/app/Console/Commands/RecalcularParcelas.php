<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Parcela;
use App\Services\BcodexService;
use App\Services\VelanaService;
use App\Services\XGateService;

use Carbon\Carbon;

class RecalcularParcelas extends Command
{
    /**
     * The name and signature of the console command.
     * Use: php artisan recalcular:Parcelas {slot}
     * Ex.: php artisan recalcular:Parcelas 0   (slots 0..9)
     */
    protected $signature = 'recalcular:Parcelas {slot : de 0 a 9 para particionar}';

    protected $description = 'Recalcular Parcelas em atraso (particionado em 10 slots)';

    public function handle()
    {
        $slot = (int) $this->argument('slot');
        if ($slot < 0 || $slot > 9) {
            $this->error('Slot inválido. Use 0..9.');
            Log::warning("[recalcular:Parcelas] Slot INVÁLIDO recebido: {$slot}");
            return self::FAILURE;
        }

        $inicio = microtime(true);
        $dataExec = now()->toDateTimeString();
        $this->info("Iniciando recalculo (slot {$slot}) às {$dataExec}");
        Log::info("[recalcular:Parcelas] Início | slot={$slot} | ts={$dataExec}");

        // QUERY: filtra no banco e particiona por slot (0..9)
        $query = Parcela::query()
            ->where('venc_real', '<', now()->subDay())
            ->whereNull('dt_baixa')
            ->whereHas('emprestimo', function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('protesto')->orWhere('protesto', 0);
                });
            })
            ->whereRaw('MOD(parcelas.id, 10) = ?', [$slot])
            ->with([
                'emprestimo.company',
                'emprestimo.banco',
                'emprestimo.contaspagar',
                'emprestimo.quitacao',
                'emprestimo.pagamentominimo',
                'emprestimo.pagamentosaldopendente',
                'emprestimo.parcelas',
            ])
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info("Total de parcelas no slot {$slot}: {$total}");
        Log::info("[recalcular:Parcelas] Slot {$slot} | total={$total}");

        // Counters de monitoramento
        $countProcessadas = 0;
        $countAtualizadas = 0;
        $countIgnoradas   = 0;
        $countFalhas      = 0;

        $bcodexService = new BcodexService();
        $hoje = Carbon::today()->toDateString();

        // Processa em blocos para economizar memória
        $query->chunkById(500, function ($parcelas) use (
            &$countProcessadas, &$countAtualizadas, &$countIgnoradas, &$countFalhas,
            $bcodexService, $hoje, $slot
        ) {
            $ids = $parcelas->pluck('id')->all();
            Log::info("[recalcular:Parcelas] Slot {$slot} | chunk iniciando | ids=" . implode(',', $ids));

            foreach ($parcelas as $parcela) {
                $countProcessadas++;

                try {
                    // Pré-atualizações idempotentes
                    if ($parcela->emprestimo) {
                        $emprestimo = $parcela->emprestimo;
                        if ($emprestimo->deve_cobrar_hoje !== $hoje) {
                            $emprestimo->deve_cobrar_hoje = $hoje;
                            $emprestimo->save();
                            Log::debug("[recalcular:Parcelas] parcela={$parcela->id} | deve_cobrar_hoje atualizado p/ {$hoje}");
                        }
                    }

                    $updatedAt = Carbon::parse($parcela->updated_at)->startOfDay();
                    if (!$updatedAt->equalTo(Carbon::today())) {
                        $parcela->atrasadas = $parcela->atrasadas + 1;
                        $parcela->save();
                        Log::debug("[recalcular:Parcelas] parcela={$parcela->id} | atrasadas++ => {$parcela->atrasadas}");
                    }

                    // Regras de processamento
                    if (!($parcela->emprestimo && $parcela->emprestimo->contaspagar && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado")) {
                        $countIgnoradas++;
                        Log::debug("[recalcular:Parcelas] parcela={$parcela->id} | ignorada (contaspagar.status != 'Pagamento Efetuado')");
                        continue;
                    }

                    $juros = $parcela->emprestimo->company->juros ?? 1;
                    $valorJuros = (float) number_format($parcela->emprestimo->valor * ($juros / 100), 2, '.', '');
                    $novoValor = $valorJuros + $parcela->saldo;

                    $qtdParcelasEmp = count($parcela->emprestimo->parcelas ?? []);
                    if ($qtdParcelasEmp === 1) {
                        $novoValor  = $parcela->saldo + (1 * $parcela->saldo / 100);
                        $valorJuros = (1 * $parcela->saldo / 100);
                    }

                    $banco = $parcela->emprestimo->banco;
                    $bankType = $banco ? ($banco->bank_type ?? ($banco->wallet ? 'bcodex' : 'normal')) : 'normal';
                    $isWalletOuVirtual = $banco && ($banco->wallet || $bankType === 'velana' || $bankType === 'xgate');

                    // Cobrança principal (parcela) – Bcodex, Velana ou XGate
                    if ($isWalletOuVirtual) {
                        if (!$this->podeProcessarParcela($parcela)) {
                            $countIgnoradas++;
                            Log::info("[recalcular:Parcelas] parcela={$parcela->id} | ignorada (já baixada)");
                            continue;
                        }

                        $txId = $parcela->identificador ?: null;
                        if ($bankType === 'xgate') {
                            Log::info("[recalcular:Parcelas] Nova cobrança XGate | parcela={$parcela->id} | emprestimo={$parcela->emprestimo_id} | valor_atualizado={$novoValor}");
                        } else {
                            Log::info("[recalcular:Parcelas] Alterando cobrança | parcela={$parcela->id} | emprestimo={$parcela->emprestimo_id} | bankType={$bankType} | valor={$parcela->saldo} | txid={$txId}");
                        }

                        $cobrancaOk = false;

                        if ($bankType === 'velana') {
                            $velanaService = new VelanaService($banco);
                            $cliente = $parcela->emprestimo->client;
                            $referenceId = $parcela->id . '_' . time();
                            $dueDate = $parcela->venc_real ? date('Y-m-d', strtotime($parcela->venc_real)) : null;
                            $response = $velanaService->criarCobranca($parcela->saldo, $cliente, $referenceId, $dueDate);
                            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                $responseData = $response->json();
                                $newTxId = $responseData['id'] ?? $referenceId;
                                $lucroRealAtual = (float) ($parcela->lucro_real ?? 0);
                                $parcela->lucro_real = $lucroRealAtual + $valorJuros;
                                $parcela->saldo = $novoValor;
                                $parcela->venc_real = date('Y-m-d');
                                $parcela->identificador = $newTxId;
                                $parcela->chave_pix = $responseData['pix']['qr_code'] ?? $responseData['pix']['copy_paste'] ?? null;
                                $parcela->save();
                                $cobrancaOk = true;
                            }
                        } elseif ($bankType === 'xgate') {
                            // XGate: sempre criar NOVA cobrança com valor atualizado (saldo + juros/taxa), nunca reutilizar a antiga
                            try {
                                $xgateService = new XGateService($banco);
                                $cliente = $parcela->emprestimo->client;
                                $referenceId = 'parcela_' . $parcela->id . '_' . time();
                                $dueDate = $parcela->venc_real ? date('Y-m-d', strtotime($parcela->venc_real)) : null;
                                $valorCobranca = $novoValor; // valor já com juros/taxa aplicados
                                $response = $xgateService->criarCobranca($valorCobranca, $cliente, $referenceId, $dueDate);
                                if (isset($response['success']) && $response['success']) {
                                    $newTxId = $response['transaction_id'] ?? $referenceId;
                                    $lucroRealAtual = (float) ($parcela->lucro_real ?? 0);
                                    $parcela->lucro_real = $lucroRealAtual + $valorJuros;
                                    $parcela->saldo = $novoValor;
                                    $parcela->venc_real = date('Y-m-d');
                                    $parcela->identificador = $newTxId;
                                    $parcela->chave_pix = $response['pixCopiaECola'] ?? $response['qr_code'] ?? null;
                                    $parcela->save();
                                    $cobrancaOk = true;
                                    Log::info("[recalcular:Parcelas] XGate nova cobrança parcela={$parcela->id} | valor={$valorCobranca} | txId={$newTxId}");
                                }
                            } catch (\Exception $e) {
                                Log::channel('xgate')->error("[recalcular:Parcelas] XGate parcela={$parcela->id} | " . $e->getMessage());
                            }
                        } else {
                            $response = $bcodexService->criarCobranca($parcela->saldo, $banco->document, $txId);
                            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                $newTxId = $response->json()['txid'] ?? null;
                                $lucroRealAtual = (float) ($parcela->lucro_real ?? 0);
                                $parcela->lucro_real = $lucroRealAtual + $valorJuros;
                                $parcela->saldo = $novoValor;
                                $parcela->venc_real = date('Y-m-d');
                                $parcela->identificador = $newTxId;
                                $parcela->chave_pix = $response->json()['pixCopiaECola'] ?? null;
                                $parcela->save();
                                $cobrancaOk = true;
                            }
                        }

                        if ($cobrancaOk) {
                            $countAtualizadas++;
                            Log::info("[recalcular:Parcelas] parcela={$parcela->id} | cobrança OK | bankType={$bankType} | novo_saldo={$parcela->saldo}");
                        } else {
                            $countFalhas++;
                            Log::warning("[recalcular:Parcelas] parcela={$parcela->id} | cobrança FALHOU | bankType={$bankType} | txid={$txId}");
                            continue;
                        }
                    }

                    // Quitação – só atualiza chave quando banco é wallet/velana/xgate
                    if ($isWalletOuVirtual && $parcela->emprestimo->quitacao) {
                        $parcela->emprestimo->quitacao->saldo = $parcela->totalPendente();
                        $parcela->emprestimo->quitacao->save();

                        if ($bankType === 'velana') {
                            $velanaService = new VelanaService($banco);
                            $cliente = $parcela->emprestimo->client;
                            $referenceId = 'quitacao_' . $parcela->emprestimo->quitacao->id . '_' . time();
                            $resp = $velanaService->criarCobranca($parcela->totalPendente(), $cliente, $referenceId, null);
                            if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                $rd = $resp->json();
                                $parcela->emprestimo->quitacao->identificador = $rd['id'] ?? $referenceId;
                                $parcela->emprestimo->quitacao->chave_pix = $rd['pix']['qr_code'] ?? $rd['pix']['copy_paste'] ?? null;
                                $parcela->emprestimo->quitacao->save();
                            }
                        } elseif ($bankType === 'xgate') {
                            // XGate: sempre nova cobrança (atualiza identificador e chave_pix)
                            try {
                                $xgateService = new XGateService($banco);
                                $cliente = $parcela->emprestimo->client;
                                $referenceId = 'quitacao_' . $parcela->emprestimo->quitacao->id . '_' . time();
                                $resp = $xgateService->criarCobranca($parcela->totalPendente(), $cliente, $referenceId, null);
                                if (isset($resp['success']) && $resp['success']) {
                                    $parcela->emprestimo->quitacao->identificador = $resp['transaction_id'] ?? $referenceId;
                                    $parcela->emprestimo->quitacao->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? null;
                                    $parcela->emprestimo->quitacao->save();
                                }
                            } catch (\Exception $e) {
                                Log::channel('xgate')->error("[recalcular:Parcelas] XGate quitação parcela={$parcela->id} | " . $e->getMessage());
                            }
                        } else {
                            $txId = $parcela->emprestimo->quitacao->identificador ?: null;
                            $resp = $bcodexService->criarCobranca($parcela->totalPendente(), $banco->document, $txId);
                            if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                $parcela->emprestimo->quitacao->identificador = $resp->json()['txid'] ?? null;
                                $parcela->emprestimo->quitacao->chave_pix = $resp->json()['pixCopiaECola'] ?? null;
                                $parcela->emprestimo->quitacao->save();
                            }
                        }
                        Log::info("[recalcular:Parcelas] Quitação | parcela={$parcela->id} | bankType={$bankType}");
                    }

                    // Pagamento mínimo – só quando wallet/velana/xgate
                    if ($isWalletOuVirtual && $parcela->emprestimo->pagamentominimo) {
                        $parcela->emprestimo->pagamentominimo->valor += $valorJuros;
                        $parcela->emprestimo->pagamentominimo->save();

                        if ($bankType === 'velana') {
                            $velanaService = new VelanaService($banco);
                            $cliente = $parcela->emprestimo->client;
                            $referenceId = 'pagamento_minimo_' . $parcela->emprestimo->pagamentominimo->id . '_' . time();
                            $resp = $velanaService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $cliente, $referenceId, null);
                            if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                $rd = $resp->json();
                                $parcela->emprestimo->pagamentominimo->identificador = $rd['id'] ?? $referenceId;
                                $parcela->emprestimo->pagamentominimo->chave_pix = $rd['pix']['qr_code'] ?? $rd['pix']['copy_paste'] ?? null;
                                $parcela->emprestimo->pagamentominimo->save();
                            }
                        } elseif ($bankType === 'xgate') {
                            // XGate: sempre nova cobrança com valor atualizado (atualiza identificador e chave_pix)
                            try {
                                $xgateService = new XGateService($banco);
                                $cliente = $parcela->emprestimo->client;
                                $referenceId = 'pagamento_minimo_' . $parcela->emprestimo->pagamentominimo->id . '_' . time();
                                $resp = $xgateService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $cliente, $referenceId, null);
                                if (isset($resp['success']) && $resp['success']) {
                                    $parcela->emprestimo->pagamentominimo->identificador = $resp['transaction_id'] ?? $referenceId;
                                    $parcela->emprestimo->pagamentominimo->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? null;
                                    $parcela->emprestimo->pagamentominimo->save();
                                }
                            } catch (\Exception $e) {
                                Log::channel('xgate')->error("[recalcular:Parcelas] XGate pag. mínimo parcela={$parcela->id} | " . $e->getMessage());
                            }
                        } else {
                            $txId = $parcela->emprestimo->pagamentominimo->identificador ?: null;
                            $resp = $bcodexService->criarCobranca($parcela->emprestimo->pagamentominimo->valor, $banco->document, $txId);
                            if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                $parcela->emprestimo->pagamentominimo->identificador = $resp->json()['txid'] ?? null;
                                $parcela->emprestimo->pagamentominimo->chave_pix = $resp->json()['pixCopiaECola'] ?? null;
                                $parcela->emprestimo->pagamentominimo->save();
                            }
                        }
                        Log::info("[recalcular:Parcelas] Pag. mínimo | parcela={$parcela->id} | bankType={$bankType}");
                    }

                    // Saldo pendente – só quando wallet/velana/xgate
                    if ($isWalletOuVirtual && $parcela->emprestimo->pagamentosaldopendente) {
                        $parcela->emprestimo->pagamentosaldopendente->valor = $parcela->totalPendenteHoje();
                        if ($parcela->emprestimo->pagamentosaldopendente->valor <= 0) {
                            Log::info("[recalcular:Parcelas] Saldo pendente ZERO | parcela={$parcela->id}");
                        } else {
                            $parcela->emprestimo->pagamentosaldopendente->save();
                            if ($bankType === 'velana') {
                                $velanaService = new VelanaService($banco);
                                $cliente = $parcela->emprestimo->client;
                                $referenceId = 'saldo_' . $parcela->emprestimo->pagamentosaldopendente->id . '_' . time();
                                $resp = $velanaService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $cliente, $referenceId, null);
                                if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                    $rd = $resp->json();
                                    $parcela->emprestimo->pagamentosaldopendente->identificador = $rd['id'] ?? $referenceId;
                                    $parcela->emprestimo->pagamentosaldopendente->chave_pix = $rd['pix']['qr_code'] ?? $rd['pix']['copy_paste'] ?? null;
                                    $parcela->emprestimo->pagamentosaldopendente->save();
                                }
                            } elseif ($bankType === 'xgate') {
                                // XGate: sempre nova cobrança (atualiza identificador e chave_pix)
                                try {
                                    $xgateService = new XGateService($banco);
                                    $cliente = $parcela->emprestimo->client;
                                    $referenceId = 'saldo_' . $parcela->emprestimo->pagamentosaldopendente->id . '_' . time();
                                    $resp = $xgateService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $cliente, $referenceId, null);
                                    if (isset($resp['success']) && $resp['success']) {
                                        $parcela->emprestimo->pagamentosaldopendente->identificador = $resp['transaction_id'] ?? $referenceId;
                                        $parcela->emprestimo->pagamentosaldopendente->chave_pix = $resp['pixCopiaECola'] ?? $resp['qr_code'] ?? null;
                                        $parcela->emprestimo->pagamentosaldopendente->save();
                                    }
                                } catch (\Exception $e) {
                                    Log::channel('xgate')->error("[recalcular:Parcelas] XGate saldo pendente parcela={$parcela->id} | " . $e->getMessage());
                                }
                            } else {
                                $txId = $parcela->emprestimo->pagamentosaldopendente->identificador ?: null;
                                $resp = $bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $banco->document, $txId);
                                if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                    $parcela->emprestimo->pagamentosaldopendente->identificador = $resp->json()['txid'] ?? null;
                                    $parcela->emprestimo->pagamentosaldopendente->chave_pix = $resp->json()['pixCopiaECola'] ?? null;
                                    $parcela->emprestimo->pagamentosaldopendente->save();
                                }
                            }
                            Log::info("[recalcular:Parcelas] Saldo pendente | parcela={$parcela->id} | bankType={$bankType}");
                        }
                    }

                } catch (\Throwable $e) {
                    $countFalhas++;
                    Log::error("[recalcular:Parcelas] ERRO parcela={$parcela->id} | {$e->getMessage()} | line={$e->getLine()}");
                }
            }

            Log::info("[recalcular:Parcelas] Slot {$slot} | chunk FINALIZADO");
        });

        $fim = microtime(true);
        $duracao = number_format($fim - $inicio, 2);

        $this->info("Resumo slot {$slot}: processadas={$countProcessadas}, atualizadas={$countAtualizadas}, ignoradas={$countIgnoradas}, falhas={$countFalhas}, tempo={$duracao}s");
        Log::info("[recalcular:Parcelas] Fim | slot={$slot} | processadas={$countProcessadas} | atualizadas={$countAtualizadas} | ignoradas={$countIgnoradas} | falhas={$countFalhas} | duracao={$duracao}s");

        return self::SUCCESS;
    }

    private static function podeProcessarParcela($parcela)
    {
        $parcelaPesquisa = Parcela::find($parcela->id);
        if ($parcelaPesquisa?->dt_baixa !== null) {
            Log::info("Parcela {$parcela->id} já baixada, não será processada novamente.");
            return false;
        }
        return true;
    }
}
