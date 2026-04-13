<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebhookGoldpix;
use App\Models\Parcela;
use App\Models\Movimentacaofinanceira;
use App\Models\Locacao;
use App\Models\PagamentoMinimo;
use App\Models\Quitacao;
use App\Models\PagamentoPersonalizado;
use App\Models\PagamentoSaldoPendente;
use App\Models\Deposito;
use App\Services\GoldPixService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarWebhookGoldpix extends Command
{
    private const TAXA_GOLDPIX = 0.30;

    protected $signature = 'webhook:baixaGoldpix';

    protected $description = 'Processa webhooks GoldPix (transaction.approved): baixa parcelas e demais entidades';

    public function handle(): void
    {
        $this->info('Processando webhooks GoldPix');
        Log::channel('goldpix')->info('ProcessarWebhookGoldpix: início da rotina');

        WebhookGoldpix::where('processado', false)->chunk(50, function ($lotes) {
            foreach ($lotes as $registro) {
                $payload = $registro->payload ?? [];
                $tipo = strtolower((string) ($payload['type'] ?? $registro->tipo_evento ?? ''));
                if ($tipo === 'withdraw') {
                    Log::channel('goldpix')->info('Webhook GoldPix tipo withdraw, ignorando fluxo de parcela', [
                        'webhook_id' => $registro->id,
                    ]);
                    $registro->processado = true;
                    $registro->save();
                    continue;
                }

                $dataInner = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                $event = strtolower((string) ($payload['event'] ?? ''));
                $status = strtolower((string) ($payload['status'] ?? $dataInner['status'] ?? $registro->status ?? ''));

                $txId = $registro->identificador
                    ?? ($payload['transaction_id'] ?? null)
                    ?? ($dataInner['id'] ?? null);
                $valor = $registro->valor !== null ? (float) $registro->valor : (float) ($payload['amount'] ?? $dataInner['amount'] ?? $dataInner['paidAmount'] ?? 0);

                if (!$txId) {
                    Log::channel('goldpix')->warning('Webhook GoldPix sem identificador', ['webhook_id' => $registro->id]);
                    $registro->processado = true;
                    $registro->save();
                    continue;
                }

                if ($event !== 'transaction.approved' && $status !== 'approved') {
                    Log::channel('goldpix')->info('Webhook GoldPix ainda não aprovado, aguardando', [
                        'identificador' => $txId,
                        'event' => $event,
                        'status' => $status,
                    ]);
                    continue;
                }

                $horario = now()->toDateTimeString();
                if (!empty($dataInner['paidAt'])) {
                    try {
                        $horario = Carbon::parse($dataInner['paidAt'])->toDateTimeString();
                    } catch (\Throwable $e) {
                    }
                } elseif (!empty($dataInner['updatedAt'])) {
                    try {
                        $horario = Carbon::parse($dataInner['updatedAt'])->toDateTimeString();
                    } catch (\Throwable $e) {
                    }
                }

                $baixaData = $dataInner;
                if (!isset($baixaData['payer']) && isset($baixaData['customer']['name'])) {
                    $baixaData['payer'] = ['name' => $baixaData['customer']['name']];
                }

                $processado = $this->processarPagamento($txId, $valor, $horario, $baixaData, $registro);
                $registro->processado = true;
                $registro->save();
                if ($processado) {
                    Log::channel('goldpix')->info('Webhook GoldPix processado com sucesso', ['identificador' => $txId]);
                }
            }
        });
    }

    /**
     * Tenta dar baixa em alguma entidade associada ao identificador (parcela, quitação, etc.).
     * Usa transaction_id ou external_id para matching. external_id no formato entidade_id_timestamp permite fallback.
     */
    private function processarPagamento(string $txId, float $valor, string $horario, array $data, WebhookGoldpix $registro): bool
    {
        $pagadorNome = $data['payer']['name'] ?? $data['payerName'] ?? $data['customerName'] ?? 'Não informado';

        // 1) Parcela (por identificador = transaction_id)
        $parcela = Parcela::where('identificador', $txId)->whereNull('dt_baixa')->first();
        if ($parcela) {
            $this->baixaParcela($parcela, $valor, $horario, $pagadorNome, $txId);
            return true;
        }

        $payloadFull = $registro->payload ?? [];
        $externalRef = isset($payloadFull['external_id']) ? (string) $payloadFull['external_id'] : (string) ($data['externalRef'] ?? '');

        // 2) Por external_id (GoldPix) ou txId no formato entidade_id_timestamp
        $refParaRegex = $externalRef !== '' ? $externalRef : $txId;
        if (preg_match('/^(\d+)_(\d+)$/', $refParaRegex, $m)) {
            $entityId = (int) $m[1];
            $parcela = Parcela::find($entityId);
            if ($parcela && !$parcela->dt_baixa) {
                $this->baixaParcela($parcela, $valor, $horario, $pagadorNome, $txId);
                return true;
            }
            $quitacao = Quitacao::find($entityId);
            if ($quitacao && !$quitacao->dt_baixa) {
                $this->baixaQuitacao($quitacao, $valor, $horario, $pagadorNome, $txId);
                return true;
            }
            $pagamentoSaldo = PagamentoSaldoPendente::find($entityId);
            if ($pagamentoSaldo && !$pagamentoSaldo->dt_baixa) {
                $this->baixaPagamentoSaldoPendente($pagamentoSaldo, $valor, $horario, $pagadorNome, $txId);
                return true;
            }
        }

        // 3) Locação
        $locacao = Locacao::where('identificador', $txId)->whereNull('data_pagamento')->first();
        if ($locacao) {
            $locacao->data_pagamento = $horario;
            $locacao->save();
            return true;
        }

        // 4) Pagamento Mínimo
        $minimo = PagamentoMinimo::where('identificador', $txId)->whereNull('dt_baixa')->first();
        if ($minimo) {
            $this->baixaPagamentoMinimo($minimo, $valor, $horario, $pagadorNome, $txId);
            return true;
        }

        // 5) Quitação
        $quitacao = Quitacao::where('identificador', $txId)->whereNull('dt_baixa')->first();
        if ($quitacao) {
            $this->baixaQuitacao($quitacao, $valor, $horario, $pagadorNome, $txId);
            return true;
        }

        // 6) Pagamento Personalizado
        $pagamento = PagamentoPersonalizado::where('identificador', $txId)->whereNull('dt_baixa')->first();
        if ($pagamento) {
            $this->baixaPagamentoPersonalizado($pagamento, $valor, $horario, $pagadorNome, $txId);
            return true;
        }

        // 7) Pagamento Saldo Pendente
        $pagamentoSaldo = PagamentoSaldoPendente::where('identificador', $txId)->first();
        if ($pagamentoSaldo) {
            $this->baixaPagamentoSaldoPendente($pagamentoSaldo, $valor, $horario, $pagadorNome, $txId);
            return true;
        }

        // 8) Depósito
        $deposito = Deposito::where('identificador', $txId)->whereNull('data_pagamento')->first();
        if ($deposito) {
            $deposito->data_pagamento = $horario;
            $deposito->save();
            $this->registrarMovimentacao($deposito->banco_id, $deposito->company_id, null, $valor,
                sprintf('Depósito Nº %d - Pagador: %s', $deposito->id, $pagadorNome), $pagadorNome, $txId);
            return true;
        }

        Log::channel('goldpix')->info('Webhook GoldPix sem entidade associada ao pagamento', ['identificador' => $txId]);
        return false;
    }

    /**
     * Registra movimentação financeira. GoldPix sem taxa por padrão (TAXA_GOLDPIX = 0).
     */
    private function registrarMovimentacao(
        ?int $bancoId,
        ?int $companyId,
        ?int $parcelaId,
        float $valor,
        string $descricaoEntrada,
        string $pagadorNome,
        string $identificadorTransacao = ''
    ): void {
        if (!$bancoId || $companyId === null) {
            Log::channel('goldpix')->warning('ProcessarWebhookGoldpix: banco_id ou company_id ausente, movimentação não criada');
            return;
        }

        $banco = \App\Models\Banco::find($bancoId);
        if (!$banco) {
            return;
        }

        $sufixoId = $identificadorTransacao !== '' ? ' | ID transação: ' . $identificadorTransacao : '';

        if ($identificadorTransacao !== '') {
            $jaTemEntrada = Movimentacaofinanceira::where('banco_id', $bancoId)
                ->where('dt_movimentacao', date('Y-m-d'))
                ->where('tipomov', 'E')
                ->where('descricao', 'like', '%ID transação: ' . $identificadorTransacao . '%')
                ->exists();
        } else {
            $jaTemEntrada = Movimentacaofinanceira::where('banco_id', $bancoId)
                ->where('dt_movimentacao', date('Y-m-d'))
                ->where('valor', $valor)
                ->where('tipomov', 'E')
                ->where('descricao', 'like', '%' . substr($descricaoEntrada, 0, 30) . '%')
                ->exists();
        }

        if (!$jaTemEntrada) {
            Movimentacaofinanceira::create([
                'banco_id'        => $bancoId,
                'company_id'      => $companyId,
                'parcela_id'      => $parcelaId,
                'descricao'       => $descricaoEntrada . ', pagador: ' . $pagadorNome . $sufixoId,
                'tipomov'         => 'E',
                'dt_movimentacao' => date('Y-m-d'),
                'valor'           => $valor,
            ]);
        }

        if (self::TAXA_GOLDPIX > 0) {
            $descricaoTaxa = 'Taxa GoldPix (R$ ' . number_format(self::TAXA_GOLDPIX, 2, ',', '.') . ')' . $sufixoId;
            Movimentacaofinanceira::create([
                'banco_id'        => $bancoId,
                'company_id'      => $companyId,
                'parcela_id'      => $parcelaId,
                'descricao'       => $descricaoTaxa,
                'tipomov'         => 'S',
                'dt_movimentacao' => date('Y-m-d'),
                'valor'           => self::TAXA_GOLDPIX,
            ]);
        }

        if (!$jaTemEntrada) {
            $banco->saldo = (float) $banco->saldo + $valor - self::TAXA_GOLDPIX;
            $banco->save();
        }
    }

    private function baixaParcela(Parcela $parcela, float $valor, string $horario, string $pagadorNome, string $identificadorTransacao = ''): void
    {
        $parcela->saldo = 0;
        $parcela->dt_baixa = $horario;
        $parcela->save();

        if ($parcela->contasreceber) {
            $parcela->contasreceber->status = 'Pago';
            $parcela->contasreceber->dt_baixa = date('Y-m-d');
            $parcela->contasreceber->forma_recebto = 'PIX';
            $parcela->contasreceber->save();
        }

        $descricao = sprintf(
            'Baixa automática GoldPix - parcela Nº %d do empréstimo Nº %d do cliente %s',
            $parcela->id,
            $parcela->emprestimo_id,
            $parcela->emprestimo->client->nome_completo ?? 'N/I'
        );
        $this->registrarMovimentacao(
            $parcela->emprestimo->banco_id,
            $parcela->emprestimo->company_id,
            $parcela->id,
            $valor,
            $descricao,
            $pagadorNome,
            $identificadorTransacao
        );

        $this->recriarCobrancaQuitacaoOuSaldoPendente($parcela->emprestimo, $parcela);
    }

    private function baixaPagamentoMinimo(PagamentoMinimo $minimo, float $valor, string $horario, string $pagadorNome, string $identificadorTransacao = ''): void
    {
        $parcela = Parcela::where('emprestimo_id', $minimo->emprestimo_id)->first();
        if (!$parcela) {
            return;
        }

        $juros = 0.0;
        $parcela->saldo -= (float) $minimo->valor;
        $juros = ((float) $parcela->emprestimo->juros * (float) $parcela->saldo) / 100.0;
        $parcela->saldo += $juros;
        $parcela->venc_real = Carbon::parse($parcela->venc_real)->copy()->addMonth();
        $parcela->atrasadas = 0;
        $parcela->save();

        $minimo->dt_baixa = $horario;
        $minimo->save();

        $descricao = sprintf(
            'Pagamento mínimo GoldPix - parcela Nº %d do empréstimo Nº %d do cliente %s',
            $parcela->id,
            $parcela->emprestimo_id,
            $parcela->emprestimo->client->nome_completo ?? 'N/I'
        );
        $this->registrarMovimentacao(
            $parcela->emprestimo->banco_id,
            $parcela->emprestimo->company_id,
            $parcela->id,
            $valor,
            $descricao,
            $pagadorNome,
            $identificadorTransacao
        );

        $this->recriarCobrancaQuitacaoOuSaldoPendente($parcela->emprestimo, $parcela);
    }

    private function baixaQuitacao(Quitacao $quitacao, float $valor, string $horario, string $pagadorNome, string $identificadorTransacao = ''): void
    {
        $parcelas = Parcela::where('emprestimo_id', $quitacao->emprestimo_id)->get();
        $bancoId = null;
        $companyId = null;

        foreach ($parcelas as $parcela) {
            $parcela->saldo = 0;
            $parcela->dt_baixa = $horario;
            $parcela->save();

            if ($parcela->contasreceber) {
                $parcela->contasreceber->status = 'Pago';
                $parcela->contasreceber->dt_baixa = date('Y-m-d');
                $parcela->contasreceber->forma_recebto = 'PIX';
                $parcela->contasreceber->save();
            }

            $bancoId = $parcela->emprestimo->banco_id;
            $companyId = $parcela->emprestimo->company_id;
        }

        if ($bancoId !== null && $companyId !== null) {
            $emprestimo = $quitacao->emprestimo;
            $descricao = sprintf(
                'Quitação GoldPix - empréstimo Nº %d do cliente %s',
                $emprestimo->id,
                $emprestimo->client->nome_completo ?? 'N/I'
            );
            $this->registrarMovimentacao($bancoId, $companyId, null, $valor, $descricao, $pagadorNome, $identificadorTransacao);
        }
    }

    private function baixaPagamentoPersonalizado(PagamentoPersonalizado $pagamento, float $valor, string $horario, string $pagadorNome, string $identificadorTransacao = ''): void
    {
        $minimoRel = $pagamento->emprestimo?->pagamentominimo;
        $saldoPendRel = $pagamento->emprestimo?->pagamentosaldopendente;
        $valor1 = (float) ($minimoRel?->valor ?? 0.0);
        $valor2 = (float) ($saldoPendRel?->valor ?? 0.0) - $valor1;
        $porcentagem = $valor2 > 0.0 ? ($valor1 / $valor2) : 0.0;

        $pagamento->dt_baixa = $horario;
        $pagamento->save();

        $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)->whereNull('dt_baixa')->orderBy('parcela', 'asc')->first();
        if (!$parcela) {
            return;
        }

        $descricao = sprintf(
            'Pagamento personalizado GoldPix Nº %d - empréstimo Nº %d do cliente %s',
            $pagamento->id,
            $parcela->emprestimo_id,
            $parcela->emprestimo->client->nome_completo ?? 'N/I'
        );
        $this->registrarMovimentacao(
            $parcela->emprestimo->banco_id,
            $parcela->emprestimo->company_id,
            $parcela->id,
            $valor,
            $descricao,
            $pagadorNome,
            $identificadorTransacao
        );

        $parcela->saldo -= $valor;
        $parcela->save();

        if ((float) $parcela->saldo !== 0.0) {
            $novoAntigo = (float) $parcela->saldo;
            $novoValor = $novoAntigo + ($novoAntigo * $porcentagem);
            $parcela->saldo = $novoValor;
            $parcela->atrasadas = 0;
            $parcela->venc_real = Carbon::parse($parcela->venc_real)->copy()->addMonth();
            $parcela->save();
            $this->recriarCobrancaQuitacaoOuSaldoPendente($parcela->emprestimo, $parcela);
        }
    }

    private function baixaPagamentoSaldoPendente(PagamentoSaldoPendente $pagamento, float $valor, string $horario, string $pagadorNome, string $identificadorTransacao = ''): void
    {
        $emprestimo = $pagamento->emprestimo;
        $valorRecebido = round((float) $valor, 2);
        $valorRestante = $valorRecebido;

        $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)
            ->whereNull('dt_baixa')
            ->orderBy('parcela', 'asc')
            ->first();

        while ($parcela && $valorRestante > 0) {
            if ($valorRestante >= (float) $parcela->saldo) {
                $valorParcela = (float) $parcela->saldo;
                $valorRestante -= $valorParcela;
                $valorRestante = round($valorRestante, 2);
                $parcela->saldo = 0;
                $parcela->dt_baixa = $horario;
                $parcela->save();
                if ($parcela->contasreceber) {
                    $parcela->contasreceber->status = 'Pago';
                    $parcela->contasreceber->dt_baixa = date('Y-m-d');
                    $parcela->contasreceber->forma_recebto = 'PIX';
                    $parcela->contasreceber->save();
                }
            } else {
                $parcela->saldo -= $valorRestante;
                $parcela->saldo = round((float) $parcela->saldo, 2);
                $parcela->dt_baixa = $horario;
                $parcela->save();
                $valorRestante = 0;
            }

            $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)
                ->whereNull('dt_baixa')
                ->orderBy('parcela', 'asc')
                ->first();
        }

        if ($valorRecebido > 0 && $emprestimo->banco_id && $emprestimo->company_id !== null) {
            $descricao = sprintf(
                'Pagamento saldo pendente GoldPix - empréstimo Nº %d do cliente %s',
                $emprestimo->id,
                $emprestimo->client->nome_completo ?? 'N/I'
            );
            $this->registrarMovimentacao(
                $emprestimo->banco_id,
                $emprestimo->company_id,
                null,
                $valorRecebido,
                $descricao,
                $pagadorNome,
                $identificadorTransacao
            );
        }

        $pagamento->dt_baixa = Carbon::parse($horario)->format('Y-m-d');
        $pagamento->save();

        $proximaParcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)
            ->whereNull('dt_baixa')
            ->orderBy('parcela', 'asc')
            ->first();

        if ($proximaParcela) {
            $pagamento->valor = (float) $proximaParcela->saldo;
            $pagamento->save();
            $this->recriarCobrancaSaldoPendente($emprestimo, $pagamento);
            $this->recriarCobrancaQuitacaoOuSaldoPendente($emprestimo, $proximaParcela);
        }
    }

    private function recriarCobrancaSaldoPendente($emprestimo, PagamentoSaldoPendente $pagamento): void
    {
        $banco = $emprestimo->banco ?? null;
        if (!$banco || ($banco->bank_type ?? 'normal') !== 'goldpix') {
            return;
        }
        try {
            $apix = new GoldPixService($banco);
            $cliente = $emprestimo->client;
            $ref = $pagamento->id . '_' . time();
            $res = $apix->criarCobranca($pagamento->valor, $cliente, $ref, null);
            if (isset($res['success']) && $res['success']) {
                $pagamento->identificador = $res['transaction_id'] ?? $res['txid'] ?? $ref;
                $pagamento->chave_pix = $res['pixCopiaECola'] ?? $res['qr_code'] ?? null;
                $pagamento->save();
            }
        } catch (\Throwable $e) {
            Log::channel('goldpix')->warning('ProcessarWebhookGoldpix: falha ao recriar cobrança saldo pendente GoldPix', [
                'pagamento_id' => $pagamento->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function recriarCobrancaQuitacaoOuSaldoPendente($emprestimo, Parcela $parcela): void
    {
        $banco = $emprestimo->banco ?? null;
        if (!$banco || ($banco->bank_type ?? 'normal') !== 'goldpix') {
            return;
        }

        try {
            $apix = new GoldPixService($banco);
            $cliente = $emprestimo->client;

            if ($emprestimo->quitacao && $emprestimo->quitacao->chave_pix) {
                $totalPendente = $parcela->emprestimo->parcelas[0]->totalPendente() ?? 0;
                $emprestimo->quitacao->valor = $totalPendente;
                $emprestimo->quitacao->saldo = $totalPendente;
                $emprestimo->quitacao->save();
                $ref = $emprestimo->quitacao->id . '_' . time();
                $res = $apix->criarCobranca($totalPendente, $cliente, $ref, null);
                if (isset($res['success']) && $res['success']) {
                    $emprestimo->quitacao->identificador = $res['transaction_id'] ?? $res['txid'] ?? $ref;
                    $emprestimo->quitacao->chave_pix = $res['pixCopiaECola'] ?? $res['qr_code'] ?? null;
                    $emprestimo->quitacao->save();
                }
            }

            $proximaParcela = $emprestimo->parcelas->firstWhere('dt_baixa', null);
            if ($proximaParcela && $emprestimo->pagamentosaldopendente && $emprestimo->pagamentosaldopendente->chave_pix) {
                $emprestimo->pagamentosaldopendente->valor = (float) $proximaParcela->saldo;
                $emprestimo->pagamentosaldopendente->save();
                $ref = $emprestimo->pagamentosaldopendente->id . '_' . time();
                $res = $apix->criarCobranca($emprestimo->pagamentosaldopendente->valor, $cliente, $ref, null);
                if (isset($res['success']) && $res['success']) {
                    $emprestimo->pagamentosaldopendente->identificador = $res['transaction_id'] ?? $res['txid'] ?? $ref;
                    $emprestimo->pagamentosaldopendente->chave_pix = $res['pixCopiaECola'] ?? $res['qr_code'] ?? null;
                    $emprestimo->pagamentosaldopendente->save();
                }
            }
        } catch (\Throwable $e) {
            Log::channel('goldpix')->warning('ProcessarWebhookGoldpix: falha ao recriar cobrança GoldPix', [
                'emprestimo_id' => $emprestimo->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
