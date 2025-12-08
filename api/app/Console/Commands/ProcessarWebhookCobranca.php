<?php

namespace App\Console\Commands;

use App\Jobs\EnviarMensagemWhatsApp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\WebhookCobranca;
use App\Models\Parcela;
use App\Models\ControleBcodex;
use App\Models\Movimentacaofinanceira;
use App\Models\Locacao;
use App\Models\PagamentoMinimo;
use App\Models\Quitacao;
use App\Models\PagamentoPersonalizado;
use App\Models\PagamentoSaldoPendente;
use App\Models\Deposito;

use App\Mail\ExampleEmail;
use Illuminate\Support\Facades\Mail;

use App\Services\BcodexService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarWebhookCobranca extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:baixaBcodex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cobrança automatica das parcelas em atraso';

    protected $bcodexService;

    public function __construct(BcodexService $bcodexService)
    {
        $this->bcodexService = $bcodexService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Realizando a Cobrança Automatica das Parcelas em Atrasos');
        Log::info("Cobranca Automatica A inicio de rotina");

        WebhookCobranca::where('processado', false)->chunk(50, function ($lotes) {
            foreach ($lotes as $registro) {
                $data = $registro->payload;
                
                // Controle para evitar processar o mesmo txId múltiplas vezes no mesmo processamento
                $txIdsProcessados = [];

                // =============== REFERENTE A PARCELAS ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        // Verifica se este txId já foi processado neste ciclo
                        if (in_array($txId, $txIdsProcessados)) {
                            Log::warning("txId {$txId} já foi processado neste ciclo, pulando duplicidade");
                            continue;
                        }

                        $parcela = Parcela::where('identificador', $txId)->whereNull('dt_baixa')->first();

                        if ($parcela) {
                            // Verifica se já existe movimentação financeira para esta parcela e txId
                            $movExistente = Movimentacaofinanceira::where('parcela_id', $parcela->id)
                                ->where('dt_movimentacao', date('Y-m-d'))
                                ->where('valor', $valor)
                                ->where('tipomov', 'E')
                                ->where('descricao', 'like', '%Baixa automática da parcela Nº ' . $parcela->id . '%')
                                ->first();

                            if ($movExistente) {
                                Log::warning("Movimentação financeira já existe para parcela {$parcela->id} e txId {$txId}, pulando duplicidade");
                                $txIdsProcessados[] = $txId;
                                continue;
                            }

                            $parcela->saldo   = 0;
                            $parcela->dt_baixa = $horario;
                            $parcela->save();

                            if ($parcela->contasreceber) {
                                $parcela->contasreceber->status        = 'Pago';
                                $parcela->contasreceber->dt_baixa      = date('Y-m-d');
                                $parcela->contasreceber->forma_recebto = 'PIX';
                                $parcela->contasreceber->save();

                                // MOVIMENTAÇÃO FINANCEIRA (Entrada)
                                Movimentacaofinanceira::create([
                                    'banco_id'        => $parcela->emprestimo->banco_id,
                                    'company_id'      => $parcela->emprestimo->company_id,
                                    'descricao'       => sprintf(
                                        'Baixa automática da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                                        $parcela->id,
                                        $parcela->emprestimo_id,
                                        $parcela->emprestimo->client->nome_completo,
                                        $pix['pagador']['nome'] ?? 'Não informado'
                                    ),
                                    'tipomov'         => 'E',
                                    'parcela_id'      => $parcela->id,
                                    'dt_movimentacao' => date('Y-m-d'),
                                    'valor'           => $valor,
                                ]);
                                
                                // Marca o txId como processado
                                $txIdsProcessados[] = $txId;

                                // Atualiza saldo do banco
                                $parcela->emprestimo->banco->saldo += $valor;
                                $parcela->emprestimo->banco->save();

                                // Recalcula/recobra quitação, se existir
                                if ($parcela->emprestimo?->quitacao?->chave_pix) {
                                    $totalPendente = $parcela->emprestimo->parcelas[0]->totalPendente() ?? 0;
                                    $parcela->emprestimo->quitacao->valor = $totalPendente;
                                    $parcela->emprestimo->quitacao->saldo = $totalPendente;
                                    $parcela->emprestimo->quitacao->save();

                                    $response = $this->bcodexService->criarCobranca($totalPendente, $parcela->emprestimo->banco->document, null);
                                    if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                        $parcela->emprestimo->quitacao->identificador = $response->json()['txid'] ?? null;
                                        $parcela->emprestimo->quitacao->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                        $parcela->emprestimo->quitacao->save();
                                    }
                                }
                            }

                            // Recalcula/recobra saldo pendente da próxima
                            $proximaParcela = $parcela->emprestimo->parcelas->firstWhere('dt_baixa', null);
                            if ($proximaParcela && $proximaParcela->emprestimo?->pagamentosaldopendente?->chave_pix) {
                                $proximaParcela->emprestimo->pagamentosaldopendente->valor = (float)$proximaParcela->saldo;
                                $proximaParcela->emprestimo->pagamentosaldopendente->save();

                                $response = $this->bcodexService->criarCobranca($proximaParcela->emprestimo->pagamentosaldopendente->valor, $proximaParcela->emprestimo->banco->document, null);
                                if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                    $proximaParcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'] ?? null;
                                    $proximaParcela->emprestimo->pagamentosaldopendente->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                    $proximaParcela->emprestimo->pagamentosaldopendente->save();
                                }
                            }
                        }
                    }
                }

                // =============== REFERENTE A LOCACAO ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        $locacao = Locacao::where('identificador', $txId)->whereNull('data_pagamento')->first();
                        if ($locacao) {
                            $locacao->data_pagamento = $horario;
                            $locacao->save();

                            $details = [
                                'title' => 'Relatório de Emprestimos',
                                'body'  => 'This is a test email using MailerSend in Laravel.'
                            ];

                            if ($locacao->company?->email) {
                                Mail::to($locacao->company->email)->send(new ExampleEmail($details, $locacao));
                            }
                        }
                    }
                }

                // =============== REFERENTE A PAGAMENTO MINIMO ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        // Verifica se este txId já foi processado neste ciclo
                        if (in_array($txId, $txIdsProcessados)) {
                            Log::warning("txId {$txId} já foi processado neste ciclo (PAGAMENTO MINIMO), pulando duplicidade");
                            continue;
                        }

                        $minimo = PagamentoMinimo::where('identificador', $txId)->whereNull('dt_baixa')->first();
                        if ($minimo) {
                            $juros   = 0.0;
                            $parcela = Parcela::where('emprestimo_id', $minimo->emprestimo_id)->first();

                            if ($parcela) {
                                $parcela->saldo -= (float)$minimo->valor;
                                $juros          = ((float)$parcela->emprestimo->juros * (float)$parcela->saldo) / 100.0;
                                $parcela->saldo += $juros;

                                $parcela->venc_real = Carbon::parse($parcela->venc_real)->copy()->addMonth();
                                $parcela->atrasadas = 0;
                                $parcela->save();

                                // Recria cobrança do mínimo (o próprio registro mínimo)
                                $response = $this->bcodexService->criarCobranca((float)$minimo->valor, $parcela->emprestimo->banco->document, null);
                                if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                    $minimo->identificador = $response->json()['txid'] ?? null;
                                    $minimo->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                    $minimo->save();
                                }

                                // Verifica se já existe movimentação financeira para esta parcela
                                $movExistente = Movimentacaofinanceira::where('parcela_id', $parcela->id)
                                    ->where('dt_movimentacao', date('Y-m-d'))
                                    ->where('valor', (float)$minimo->valor)
                                    ->where('tipomov', 'E')
                                    ->where('descricao', 'like', '%Pagamento Minimo da parcela Nº ' . $parcela->id . '%')
                                    ->first();

                                if (!$movExistente) {
                                    // Movimentação financeira de entrada
                                    Movimentacaofinanceira::create([
                                        'banco_id'        => $parcela->emprestimo->banco_id,
                                        'company_id'      => $parcela->emprestimo->company_id,
                                        'descricao'       => sprintf(
                                            'Pagamento Minimo da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                                            $parcela->id,
                                            $parcela->emprestimo_id,
                                            $parcela->emprestimo->client->nome_completo,
                                            $pix['pagador']['nome'] ?? 'Não informado'
                                        ),
                                        'tipomov'         => 'E',
                                        'parcela_id'      => $parcela->id,
                                        'dt_movimentacao' => date('Y-m-d'),
                                        'valor'           => (float)$minimo->valor,
                                    ]);

                                    // Atualiza saldo do banco apenas se a movimentação foi criada
                                    $parcela->emprestimo->banco->saldo += (float)$minimo->valor;
                                    $parcela->emprestimo->banco->save();
                                } else {
                                    Log::warning("Movimentação financeira já existe para parcela {$parcela->id} (PAGAMENTO MINIMO), pulando duplicidade");
                                }

                                // Recalcula/recobra Quitação
                                if ($parcela->emprestimo?->quitacao) {
                                    $saldoTotal = (float)$parcela->totalPendente();
                                    $parcela->emprestimo->quitacao->saldo = $saldoTotal;
                                    $parcela->emprestimo->quitacao->save();

                                    $response = $this->bcodexService->criarCobranca($saldoTotal, $parcela->emprestimo->banco->document, null);
                                    if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                        $parcela->emprestimo->quitacao->identificador = $response->json()['txid'] ?? null;
                                        $parcela->emprestimo->quitacao->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                        $parcela->emprestimo->quitacao->saldo         = $saldoTotal;
                                        $parcela->emprestimo->quitacao->save();
                                    }
                                }

                                // Recalcula/recobra Pagamento Mínimo (juros) se existir o relacionamento
                                if ($parcela->emprestimo?->pagamentominimo) {
                                    $parcela->emprestimo->pagamentominimo->valor = $juros;
                                    $parcela->emprestimo->pagamentominimo->save();

                                    $response = $this->bcodexService->criarCobranca($juros, $parcela->emprestimo->banco->document, null);
                                    if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                        $parcela->emprestimo->pagamentominimo->identificador = $response->json()['txid'] ?? null;
                                        $parcela->emprestimo->pagamentominimo->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                        $parcela->emprestimo->pagamentominimo->save();
                                    }
                                }

                                // Recalcula/recobra Saldo Pendente
                                if ($parcela->emprestimo?->pagamentosaldopendente?->chave_pix) {
                                    $parcela->emprestimo->pagamentosaldopendente->valor = (float)$parcela->saldo;
                                    $parcela->emprestimo->pagamentosaldopendente->save();

                                    $response = $this->bcodexService->criarCobranca($parcela->emprestimo->pagamentosaldopendente->valor, $parcela->emprestimo->banco->document, null);
                                    if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                        $parcela->emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'] ?? null;
                                        $parcela->emprestimo->pagamentosaldopendente->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                        $parcela->emprestimo->pagamentosaldopendente->save();
                                    }
                                }
                            }
                            
                            // Marca o txId como processado após processar PAGAMENTO MINIMO
                            $txIdsProcessados[] = $txId;
                        }
                    }
                }

                // =============== REFERENTE A QUITACAO ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        // Verifica se este txId já foi processado neste ciclo
                        if (in_array($txId, $txIdsProcessados)) {
                            Log::warning("txId {$txId} já foi processado neste ciclo (QUITACAO), pulando duplicidade");
                            continue;
                        }

                        $quitacao = Quitacao::where('identificador', $txId)->whereNull('dt_baixa')->first();
                        if ($quitacao) {
                            $parcelas = Parcela::where('emprestimo_id', $quitacao->emprestimo_id)->get();

                            foreach ($parcelas as $parcela) {
                                $valorParcela   = (float)$parcela->saldo;
                                $parcela->saldo = 0;
                                $parcela->dt_baixa = $horario;
                                $parcela->save();

                                if ($parcela->contasreceber) {
                                    // Verifica se já existe movimentação financeira para esta parcela
                                    $movExistente = Movimentacaofinanceira::where('parcela_id', $parcela->id)
                                        ->where('dt_movimentacao', date('Y-m-d'))
                                        ->where('valor', $valorParcela)
                                        ->where('tipomov', 'E')
                                        ->where('descricao', 'like', '%Quitação da parcela Nº ' . $parcela->id . '%')
                                        ->first();

                                    if (!$movExistente) {
                                        // MOVIMENTAÇÃO FINANCEIRA (Entrada)
                                        Movimentacaofinanceira::create([
                                            'banco_id'        => $parcela->emprestimo->banco_id,
                                            'company_id'      => $parcela->emprestimo->company_id,
                                            'descricao'       => sprintf(
                                                'Quitação da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                                                $parcela->id,
                                                $parcela->emprestimo_id,
                                                $parcela->emprestimo->client->nome_completo,
                                                $pix['pagador']['nome'] ?? 'Não informado'
                                            ),
                                            'tipomov'         => 'E',
                                            'parcela_id'      => $parcela->id,
                                            'dt_movimentacao' => date('Y-m-d'),
                                            'valor'           => $valorParcela,
                                        ]);

                                        // Atualiza saldo do banco apenas se a movimentação foi criada
                                        $parcela->emprestimo->banco->saldo += $valorParcela;
                                        $parcela->emprestimo->banco->save();
                                    } else {
                                        Log::warning("Movimentação financeira já existe para parcela {$parcela->id} (QUITACAO), pulando duplicidade");
                                    }
                                }
                            }
                            
                            // Marca o txId como processado após processar QUITACAO
                            $txIdsProcessados[] = $txId;
                        }
                    }
                }

                // =============== REFERENTE A PAGAMENTO PERSONALIZADO ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        // Verifica se este txId já foi processado neste ciclo
                        if (in_array($txId, $txIdsProcessados)) {
                            Log::warning("txId {$txId} já foi processado neste ciclo (PAGAMENTO PERSONALIZADO), pulando duplicidade");
                            continue;
                        }

                        $pagamento = PagamentoPersonalizado::where('identificador', $txId)->whereNull('dt_baixa')->first();

                        if ($pagamento) {
                            $minimoRel     = $pagamento->emprestimo?->pagamentominimo;        // pode ser null
                            $saldoPendRel  = $pagamento->emprestimo?->pagamentosaldopendente; // pode ser null

                            if (!$minimoRel || !$saldoPendRel) {
                                Log::warning('PagamentoPersonalizado: relacionamento ausente', [
                                    'pagamento_id' => $pagamento->id,
                                    'tem_pagamentominimo' => (bool)$minimoRel,
                                    'tem_pagamentosaldopendente' => (bool)$saldoPendRel,
                                ]);
                            }

                            $valor1 = (float)($minimoRel?->valor ?? 0.0);
                            $valor2 = (float)($saldoPendRel?->valor ?? 0.0) - $valor1;
                            $porcentagem = ($valor2 > 0.0) ? ($valor1 / $valor2) : 0.0;

                            $pagamento->dt_baixa = $horario;
                            $pagamento->save();

                            $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)->whereNull('dt_baixa')->orderBy('parcela', 'asc')->first();
                            if (!$parcela) {
                                Log::warning('PagamentoPersonalizado: nenhuma parcela pendente encontrada', [
                                    'emprestimo_id' => $pagamento->emprestimo_id,
                                    'pagamento_id'  => $pagamento->id,
                                ]);
                                continue;
                            }

                            // Verifica se já existe movimentação financeira para esta parcela
                            $movExistente = Movimentacaofinanceira::where('parcela_id', $parcela->id)
                                ->where('dt_movimentacao', date('Y-m-d'))
                                ->where('valor', $valor)
                                ->where('tipomov', 'E')
                                ->where('descricao', 'like', '%Pagamento personalizado Nº ' . $pagamento->id . '%')
                                ->first();

                            if (!$movExistente) {
                                // MOVIMENTAÇÃO FINANCEIRA (Entrada)
                                Movimentacaofinanceira::create([
                                    'banco_id'        => $parcela->emprestimo->banco_id,
                                    'company_id'      => $parcela->emprestimo->company_id,
                                    'descricao'       => sprintf(
                                        'Pagamento personalizado Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                                        $pagamento->id,
                                        $parcela->emprestimo_id,
                                        $parcela->emprestimo->client->nome_completo,
                                        $pix['pagador']['nome'] ?? 'Não informado'
                                    ),
                                    'tipomov'         => 'E',
                                    'parcela_id'      => $parcela->id,
                                    'dt_movimentacao' => date('Y-m-d'),
                                    'valor'           => $valor,
                                ]);

                                // Atualiza saldo do banco apenas se a movimentação foi criada
                                $parcela->emprestimo->banco->saldo += $valor;
                                $parcela->emprestimo->banco->save();
                            } else {
                                Log::warning("Movimentação financeira já existe para parcela {$parcela->id} (PAGAMENTO PERSONALIZADO), pulando duplicidade");
                            }

                            // Abate valor na parcela
                            $parcela->saldo -= $valor;
                            $parcela->save();

                            if ((float)$parcela->saldo !== 0.0) {
                                $novoAntigo = (float)$parcela->saldo;
                                $novoValor  = $novoAntigo + ($novoAntigo * $porcentagem);

                                $parcela->saldo     = $novoValor;
                                $parcela->atrasadas = 0;
                                $parcela->venc_real = Carbon::parse($parcela->venc_real)->copy()->addMonth();
                                $parcela->save();

                                // Recria cobrança da própria parcela
                                $resp = $this->bcodexService->criarCobranca($parcela->saldo, $pagamento->emprestimo->banco->document, null);
                                if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                    $parcela->identificador = $resp->json()['txid'] ?? null;
                                    $parcela->chave_pix     = $resp->json()['pixCopiaECola'] ?? null;
                                    $parcela->save();
                                }

                                // Atualiza/recobra Saldo Pendente (se existir)
                                if ($saldoPendRel) {
                                    $saldoPendRel->valor = (float)$parcela->saldo;
                                    $saldoPendRel->save();

                                    $resp = $this->bcodexService->criarCobranca($saldoPendRel->valor, $pagamento->emprestimo->banco->document, null);
                                    if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                        $saldoPendRel->identificador = $resp->json()['txid'] ?? null;
                                        $saldoPendRel->chave_pix     = $resp->json()['pixCopiaECola'] ?? null;
                                        $saldoPendRel->save();
                                    }
                                }

                                // Atualiza/recobra Pagamento Mínimo (se existir)
                                if ($minimoRel) {
                                    $minimoRel->valor = max(0, $novoValor - $novoAntigo);
                                    $minimoRel->save();

                                    if ($minimoRel->valor > 0) {
                                        $resp = $this->bcodexService->criarCobranca($minimoRel->valor, $pagamento->emprestimo->banco->document, null);
                                        if (is_object($resp) && method_exists($resp, 'successful') && $resp->successful()) {
                                            $minimoRel->identificador = $resp->json()['txid'] ?? null;
                                            $minimoRel->chave_pix     = $resp->json()['pixCopiaECola'] ?? null;
                                            $minimoRel->save();
                                        }
                                    }
                                }
                            }
                            
                            // Marca o txId como processado após processar PAGAMENTO PERSONALIZADO
                            $txIdsProcessados[] = $txId;
                        }
                    }
                }

                // =============== REFERENTE A PAGAMENTO SALDO PENDENTE ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        // Verifica se este txId já foi processado neste ciclo
                        if (in_array($txId, $txIdsProcessados)) {
                            Log::warning("txId {$txId} já foi processado neste ciclo (PagamentoSaldoPendente), pulando duplicidade");
                            continue;
                        }

                        $pagamento = PagamentoSaldoPendente::where('identificador', $txId)->whereNull('dt_baixa')->first();
                        if ($pagamento) {
                            $emprestimo = $pagamento->emprestimo; // manter ref estável

                            $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)
                                ->whereNull('dt_baixa')
                                ->orderBy('parcela', 'asc')
                                ->first();

                            while ($parcela && $valor > 0) {

                                if ($valor >= (float)$parcela->saldo) {
                                    // Verifica se já existe movimentação financeira para esta parcela
                                    $movExistente = Movimentacaofinanceira::where('parcela_id', $parcela->id)
                                        ->where('dt_movimentacao', date('Y-m-d'))
                                        ->where('valor', (float)$parcela->saldo)
                                        ->where('tipomov', 'E')
                                        ->where('descricao', 'like', '%Baixa automática da parcela Nº ' . $parcela->id . '%')
                                        ->first();

                                    if (!$movExistente) {
                                        // MOV FIN (Entrada) - quitação da parcela
                                        Movimentacaofinanceira::create([
                                            'banco_id'        => $parcela->emprestimo->banco_id,
                                            'company_id'      => $parcela->emprestimo->company_id,
                                            'descricao'       => sprintf(
                                                'Baixa automática da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                                                $parcela->id,
                                                $parcela->emprestimo_id,
                                                $parcela->emprestimo->client->nome_completo,
                                                $pix['pagador']['nome'] ?? 'Não informado'
                                            ),
                                            'tipomov'         => 'E',
                                            'parcela_id'      => $parcela->id,
                                            'dt_movimentacao' => date('Y-m-d'),
                                            'valor'           => (float)$parcela->saldo,
                                        ]);

                                        // Atualiza saldo do banco apenas se a movimentação foi criada
                                        $parcela->emprestimo->banco->saldo += (float)$parcela->saldo;
                                        $parcela->emprestimo->banco->save();
                                    } else {
                                        Log::warning("Movimentação financeira já existe para parcela {$parcela->id} (PagamentoSaldoPendente), pulando duplicidade");
                                    }

                                    // Quita a parcela atual
                                    $valor -= (float)$parcela->saldo;
                                    $valor = round($valor, 2); // 👈 Corrige imprecisões com float

                                    $parcela->saldo = 0;
                                    $parcela->dt_baixa = $horario;
                                    $parcela->save();

                                    // Se o valor restante for praticamente zero, encerra o loop
                                    if ($valor <= 0.00 || $valor < 0.01) {
                                        $valor = 0;
                                        break;
                                    }
                                } else {
                                    // Verifica se já existe movimentação financeira para esta parcela (baixa parcial)
                                    $movExistente = Movimentacaofinanceira::where('parcela_id', $parcela->id)
                                        ->where('dt_movimentacao', date('Y-m-d'))
                                        ->where('valor', $valor)
                                        ->where('tipomov', 'E')
                                        ->where('descricao', 'like', '%Baixa parcial automática da parcela Nº ' . $parcela->id . '%')
                                        ->first();

                                    if (!$movExistente) {
                                        // MOV FIN (Entrada) - baixa parcial
                                        Movimentacaofinanceira::create([
                                            'banco_id'        => $parcela->emprestimo->banco_id,
                                            'company_id'      => $parcela->emprestimo->company_id,
                                            'descricao'       => sprintf(
                                                'Baixa parcial automática da parcela Nº %d do empréstimo Nº %d do cliente %s, pagador: %s',
                                                $parcela->id,
                                                $parcela->emprestimo_id,
                                                $parcela->emprestimo->client->nome_completo,
                                                $pix['pagador']['nome'] ?? 'Não informado'
                                            ),
                                            'tipomov'         => 'E',
                                            'parcela_id'      => $parcela->id,
                                            'dt_movimentacao' => date('Y-m-d'),
                                            'valor'           => $valor,
                                        ]);

                                        // Atualiza saldo do banco apenas se a movimentação foi criada
                                        $parcela->emprestimo->banco->saldo += $valor;
                                        $parcela->emprestimo->banco->save();
                                    } else {
                                        Log::warning("Movimentação financeira já existe para parcela {$parcela->id} (baixa parcial), pulando duplicidade");
                                    }

                                    // Reduz o saldo da parcela atual
                                    $parcela->saldo -= $valor;
                                    $parcela->saldo = round($parcela->saldo, 2);
                                    $parcela->dt_baixa = $horario;
                                    $parcela->save();

                                    $valor = 0;
                                    break;
                                }

                                // Busca a próxima parcela pendente
                                $parcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)
                                    ->whereNull('dt_baixa')
                                    ->orderBy('parcela', 'asc')
                                    ->first();
                            }

                            // Próxima parcela após o pagamento
                            $proximaParcela = Parcela::where('emprestimo_id', $pagamento->emprestimo_id)
                                ->whereNull('dt_baixa')
                                ->orderBy('parcela', 'asc')
                                ->first();

                            if ($proximaParcela) {
                                $pagamento->valor = (float)$proximaParcela->saldo;
                                $pagamento->save();

                                $response = $this->bcodexService->criarCobranca((float)$proximaParcela->saldo, $emprestimo->banco->document, null);
                                if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                    $pagamento->identificador = $response->json()['txid'] ?? null;
                                    $pagamento->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                    $pagamento->save();
                                }

                                if ($proximaParcela->contasreceber) {
                                    $proximaParcela->contasreceber->status        = 'Pago';
                                    $proximaParcela->contasreceber->dt_baixa      = date('Y-m-d');
                                    $proximaParcela->contasreceber->forma_recebto = 'PIX';
                                    $proximaParcela->contasreceber->save();

                                    // Recalcula/recobra Quitação
                                    if ($emprestimo?->quitacao?->chave_pix) {
                                        $totalPendente = $emprestimo->parcelas[0]->totalPendente() ?? 0;
                                        $emprestimo->quitacao->valor = $totalPendente;
                                        $emprestimo->quitacao->saldo = $totalPendente;
                                        $emprestimo->quitacao->save();

                                        $response = $this->bcodexService->criarCobranca($totalPendente, $emprestimo->banco->document, null);
                                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                            $emprestimo->quitacao->identificador = $response->json()['txid'] ?? null;
                                            $emprestimo->quitacao->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                            $emprestimo->quitacao->save();
                                        }
                                    }

                                    // Recalcula/recobra Saldo Pendente
                                    if ($emprestimo?->pagamentosaldopendente?->chave_pix) {
                                        $emprestimo->pagamentosaldopendente->valor = (float)$proximaParcela->saldo;
                                        $emprestimo->pagamentosaldopendente->save();

                                        $response = $this->bcodexService->criarCobranca($emprestimo->pagamentosaldopendente->valor, $emprestimo->banco->document, null);
                                        if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                                            $emprestimo->pagamentosaldopendente->identificador = $response->json()['txid'] ?? null;
                                            $emprestimo->pagamentosaldopendente->chave_pix     = $response->json()['pixCopiaECola'] ?? null;
                                            $emprestimo->pagamentosaldopendente->save();
                                        }
                                    }
                                }
                            }
                            
                            // Marca o txId como processado após processar PagamentoSaldoPendente
                            $txIdsProcessados[] = $txId;
                        }
                    }
                }

                // =============== REFERENTE A DEPOSITO ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId    = $pix['txId']   ?? null;
                        $valor   = (float)($pix['valor'] ?? 0);
                        $horario = isset($pix['horario']) ? Carbon::parse($pix['horario'])->toDateTimeString() : now()->toDateTimeString();

                        if (!$txId) {
                            continue;
                        }

                        $deposito = Deposito::where('identificador', $txId)->whereNull('data_pagamento')->first();
                        if ($deposito) {
                            // Atualiza saldo do banco
                            if ($deposito->banco) {
                                $deposito->banco->saldo += $valor;
                                $deposito->banco->save();
                            }

                            $deposito->data_pagamento = $horario;
                            $deposito->save();

                            // MOVIMENTAÇÃO FINANCEIRA (Entrada)
                            Movimentacaofinanceira::create([
                                'banco_id'        => $deposito->banco_id,
                                'company_id'      => $deposito->company_id,
                                'descricao'       => sprintf('Deposito Pagador: %s', $pix['pagador']['nome'] ?? 'Não informado'),
                                'tipomov'         => 'E',
                                'dt_movimentacao' => date('Y-m-d'),
                                'valor'           => $valor,
                            ]);
                        }
                    }
                }

                // =============== CONTROLE COBRANÇA BCODEX ===============
                if (isset($data['pix']) && is_array($data['pix'])) {
                    foreach ($data['pix'] as $pix) {
                        $txId = $pix['txId'] ?? null;
                        if (!$txId) {
                            continue;
                        }

                        $controle = ControleBcodex::where('identificador', $txId)->first();
                        if ($controle) {
                            $controle->data_pagamento = isset($pix['horario'])
                                ? Carbon::parse($pix['horario'])->toDateTimeString()
                                : now()->toDateTimeString();
                            $controle->save();
                        }
                    }
                }

                // Após sucesso:
                $registro->processado = true;
                $registro->save();
            }
        });
    }

    private function processarParcela($parcela)
    {
        if (!$this->deveProcessarParcela($parcela)) {
            return;
        }

        if ($this->emprestimoEmProtesto($parcela)) {
            return;
        }

        try {
            // Usa whatsapp_cobranca se disponível, senão usa whatsapp padrão
            $whatsappUrl = $parcela->emprestimo->company->whatsapp_cobranca ?? $parcela->emprestimo->company->whatsapp ?? '';
            $response = Http::get($whatsappUrl . '/logar');

            if ($response->successful() && ($response->json()['loggedIn'] ?? false)) {
                $this->enviarMensagem($parcela);
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }

    private function deveProcessarParcela($parcela)
    {
        // Verifica se tem whatsapp ou whatsapp_cobranca configurado
        $temWhatsapp = isset($parcela->emprestimo->company->whatsapp) || isset($parcela->emprestimo->company->whatsapp_cobranca);
        return $temWhatsapp
            && $parcela->emprestimo->contaspagar
            && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado";
    }

    private function emprestimoEmProtesto($parcela)
    {
        if (!$parcela->emprestimo || !$parcela->emprestimo->data_protesto) {
            return false;
        }

        return Carbon::parse($parcela->emprestimo->data_protesto)->lte(Carbon::now()->subDays(14));
    }

    private function enviarMensagem($parcela)
    {
        $telefone = preg_replace('/\D/', '', (string)($parcela->emprestimo->client->telefone_celular_1 ?? ''));
        // Usa whatsapp_cobranca se disponível, senão usa whatsapp padrão
        $baseUrl  = $parcela->emprestimo->company->whatsapp_cobranca ?? $parcela->emprestimo->company->whatsapp ?? null;

        if (!$telefone || !$baseUrl) {
            return;
        }

        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($parcela, $saudacao);

        $data = [
            "numero"   => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Http::asJson()->post("$baseUrl/enviar-mensagem", $data);
        Log::info("MENSAGEM ENVIADA: " . $telefone);

        sleep(4);

        if ($parcela->emprestimo->company->mensagem_audio ?? false) {
            if (($parcela->atrasadas ?? 0) > 0) {
                $tipo = "0";
                switch ($parcela->atrasadas) {
                    case 2:
                        $tipo = "1.1";
                        break;
                    case 4:
                        $tipo = "2.1";
                        break;
                    case 6:
                        $tipo = "3.1";
                        break;
                    case 8:
                        $tipo = "4.1";
                        break;
                    case 10:
                        $tipo = "5.1";
                        break;
                    case 15:
                        $tipo = "6.1";
                        break;
                }

                if ($tipo !== "0") {
                    $data2 = [
                        "numero"      => "55" . $telefone,
                        "nomeCliente" => $parcela->emprestimo->client->nome_completo ?? '',
                        "tipo"        => $tipo
                    ];

                    Http::asJson()->post("$baseUrl/enviar-audio", $data2);
                }
            }
        }

        // 1ª cobrança de empréstimo mensal
        if ((count($parcela->emprestimo->parcelas ?? []) === 1) && (($parcela->atrasadas ?? 0) === 0)) {
            $data3 = [
                "numero"      => "55" . $telefone,
                "nomeCliente" => "Sistema",
                "tipo"        => "msginfo1"
            ];
            Http::asJson()->post("$baseUrl/enviar-audio", $data3);
        }
    }

    private function montarMensagem($parcela, $saudacao)
    {
        $nome   = $parcela->emprestimo->client->nome_completo ?? 'Cliente';
        $whats  = $parcela->emprestimo->company->numero_contato ?? '';
        $link   = "https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}";

        $saudacaoTexto = "{$saudacao}, {$nome}!";
        $fraseInicial = "

Relatório de Parcelas Pendentes:

⚠️ *sempre enviar o comprovante para ajudar na conferência não se esqueça*

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

{$link}

📲 Para mais informações WhatsApp {$whats}
";
        return $saudacaoTexto . $fraseInicial;
    }

    private function obterSaudacao()
    {
        $hora = (int)date('H');
        $saudacoesManha = ['🌤️ Bom dia', '👋 Olá, bom dia', '🌤️ Tenha um excelente dia'];
        $saudacoesTarde = ['🌤️ Boa tarde', '👋 Olá, boa tarde', '🌤️ Espero que sua tarde esteja ótima'];
        $saudacoesNoite = ['🌤️ Boa noite', '👋 Olá, boa noite', '🌤️ Espero que sua noite esteja ótima'];

        if ($hora < 12) {
            return $saudacoesManha[array_rand($saudacoesManha)];
        } elseif ($hora < 18) {
            return $saudacoesTarde[array_rand($saudacoesTarde)];
        } else {
            return $saudacoesNoite[array_rand($saudacoesNoite)];
        }
    }

    private function encontrarPrimeiraParcelaPendente($parcelas)
    {
        foreach ($parcelas as $parcela) {
            if (is_null($parcela->dt_baixa)) {
                return $parcela;
            }
        }
        return null;
    }
}
