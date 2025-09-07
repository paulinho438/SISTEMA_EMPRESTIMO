<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;

class CobrancaAutomaticaATestController extends Controller
{
    /**
     * GET /api/cobrancas/teste
     * Params opcionais:
     *   - id: testa uma parcela específica (ignora o resto)
     *   - limit: limita a quantidade avaliada (ex.: 50)
     */
    public function dryRun(Request $request)
    {
        $today = Carbon::today();
        $now   = Carbon::now(); // usa timezone da app (ajuste no config/app.php se precisar)

        $isWeekend = $now->isSaturday() || $now->isSunday();
        $isHoliday = Feriado::where('data_feriado', $today->toDateString())->exists();

        $meta = [
            'today'      => $today->toDateString(),
            'now'        => $now->toDateTimeString(),
            'isWeekend'  => $isWeekend,
            'isHoliday'  => $isHoliday,
        ];

        // Se for feriado, replica o early return da Command
        if ($isHoliday) {
            return response()->json([
                'meta'   => $meta,
                'counts' => ['candidatas' => 0, 'deduplicadas' => 0, 'would_send' => 0],
                'items'  => [],
                'note'   => 'Hoje é feriado: rotina não executa (sem envios).',
            ]);
        }

        // Base da consulta
        $parcelasQuery = Parcela::query()
            ->whereNull('dt_baixa')
            ->with([
                'emprestimo',
                'emprestimo.company',
                'emprestimo.client',
                'emprestimo.contaspagar',
                'emprestimo.parcelas',
            ])
            ->orderByDesc('id');

        // Param opcional: testar apenas uma parcela específica
        if ($request->filled('id')) {
            $parcelasQuery->where('id', $request->integer('id'));
        }

        // Fim de semana: só atrasadas > 0
        if ($isWeekend) {
            $parcelasQuery->where('atrasadas', '>', 0);
        }

        if ($request->filled('limit')) {
            $parcelas = $parcelasQuery->limit($request->integer('limit', 50))->get();
        } else {
            $parcelas = $parcelasQuery->get();
        }

        // Filtros pós-consulta, reproduzindo a Command
        if ($isWeekend) {
            $parcelas = $parcelas->filter(function ($parcela) {
                $dataProtesto = optional($parcela->emprestimo)->data_protesto;
                if (!$dataProtesto) return true;
                return !Carbon::parse($dataProtesto)->lte(Carbon::now()->subDays(1));
            });
        } else {
            $hoje = Carbon::now();
            $parcelas = $parcelas->filter(function ($parcela) use ($hoje) {
                $emp = $parcela->emprestimo;

                $deveCobrarHoje = $emp
                    && !is_null($emp->deve_cobrar_hoje)
                    && Carbon::parse($emp->deve_cobrar_hoje)->isSameDay($hoje);

                $vencimentoHoje = $parcela->venc_real
                    && Carbon::parse($parcela->venc_real)->isSameDay($hoje);

                return $deveCobrarHoje || $vencimentoHoje;
            });
        }

        // Deduplicar por emprestimo_id (mantendo a parcela de maior id)
        $parcelas = $parcelas->unique('emprestimo_id')->values();

        // Avaliar item a item sem enviar
        $items = [];
        $wouldSendCount = 0;

        foreach ($parcelas as $p) {
            $trace = [
                'parcela_id'        => $p->id,
                'emprestimo_id'     => $p->emprestimo_id,
                'cliente'           => optional($p->emprestimo->client)->nome_completo,
                'atrasadas'         => (int) $p->atrasadas,
                'venc_real'         => optional($p->venc_real) ? Carbon::parse($p->venc_real)->toDateString() : null,
                'regras'            => [],
                'blockers'          => [],
                'selected_by'       => $isWeekend ? 'fim_de_semana' : 'dia_util',
                'filters'           => [],
                'would_send'        => false,
            ];

            // === mesmos gates da Command ===

            // 1) podeProcessarParcela()
            $pp = $this->podeProcessarParcelaDry($p, $today);
            $trace['regras']['pode_processar_parcela'] = $pp;

            if (!$pp['ok']) {
                $trace['blockers'][] = 'podeProcessarParcela:false';
            }

            // 2) deveProcessarParcela()
            $dp = $this->deveProcessarParcelaDry($p);
            $trace['regras']['deve_processar_parcela'] = $dp;

            if (!$dp['ok']) {
                $trace['blockers'][] = 'deveProcessarParcela:false';
            }

            // 3) emprestimoEmProtesto()
            $ep = $this->emprestimoEmProtestoDry($p, $now);
            $trace['regras']['emprestimo_em_protesto'] = $ep;

            if ($ep['em_protesto']) {
                $trace['blockers'][] = 'emprestimoEmProtesto:true';
            }

            // Resultado final (se passaria para enviarMensagem)
            $trace['would_send'] = $pp['ok'] && $dp['ok'] && !$ep['em_protesto'];
            if ($trace['would_send']) $wouldSendCount++;

            // detalhes dos filtros de seleção do início
            if ($isWeekend) {
                $trace['filters'][] = 'atrasadas>0';
                $dataProtesto = optional($p->emprestimo)->data_protesto;
                $trace['filters'][] = is_null($dataProtesto)
                    ? 'data_protesto:null'
                    : ('data_protesto>' . Carbon::parse($dataProtesto)->subDay()->toDateTimeString() . '? ' .
                        (Carbon::parse($dataProtesto)->gt(Carbon::now()->subDay()) ? 'ok' : 'fail'));
            } else {
                $emp = $p->emprestimo;
                $trace['filters'][] = 'deve_cobrar_hoje=' . ($emp && $emp->deve_cobrar_hoje
                    ? (Carbon::parse($emp->deve_cobrar_hoje)->toDateString()) : 'null');
                $trace['filters'][] = 'venc_real=' . ($p->venc_real ? Carbon::parse($p->venc_real)->toDateString() : 'null');
            }

            $items[] = $trace;
        }

        return response()->json([
            'meta'   => $meta,
            'counts' => [
                'candidatas'   => $parcelas->count(),
                'deduplicadas' => $parcelas->count(), // já deduplicado acima
                'would_send'   => $wouldSendCount,
            ],
            'items'  => $items,
        ]);
    }

    // ======= DRY helpers =======

    private function podeProcessarParcelaDry($parcela, Carbon $today): array
    {
        // (replica a lógica da sua static podeProcessarParcela)
        $motivos = [];

        // carrega do banco novamente na sua versão original usa Parcela::find()
        // aqui usamos o próprio objeto e cuidamos das datas
        $dtBaixa   = $parcela->dt_baixa;
        $vencReal  = $parcela->venc_real ? Carbon::parse($parcela->venc_real) : null;

        if ($vencReal && $vencReal->isSameDay($today) && is_null($dtBaixa)) {
            return ['ok' => true, 'motivos' => ['venc_real=hoje && dt_baixa=null']];
        }

        if (!is_null($dtBaixa)) {
            $motivos[] = 'parcela_ja_baixada';
            return ['ok' => false, 'motivos' => $motivos];
        }

        if ((int)$parcela->atrasadas === 0) {
            $motivos[] = 'parcela_nao_atrasada';
            return ['ok' => false, 'motivos' => $motivos];
        }

        $qtdParcelasEmp = Parcela::where('emprestimo_id', $parcela->emprestimo_id)->count();
        if ($qtdParcelasEmp === 1) {
            if ($vencReal && $vencReal->greaterThan($today)) {
                $motivos[] = 'parcela_unica_e_venc_real_futuro';
                return ['ok' => false, 'motivos' => $motivos];
            }
        }

        return ['ok' => true, 'motivos' => $motivos ?: ['pode_processar=true']];
    }

    private function deveProcessarParcelaDry($parcela): array
    {
        $emp = $parcela->emprestimo;
        $okWhats = isset($emp->company->whatsapp);
        $okCP    = $emp->contaspagar ?? null;
        $okStat  = $okCP && $emp->contaspagar->status === "Pagamento Efetuado";

        $ok = $okWhats && $okCP && $okStat;

        $motivos = [];
        if (!$okWhats) $motivos[] = 'company.whatsapp_inexistente';
        if (!$okCP)    $motivos[] = 'contaspagar_inexistente';
        if ($okCP && !$okStat) $motivos[] = 'contaspagar.status!=' . ($emp->contaspagar->status ?? 'null');

        return ['ok' => $ok, 'motivos' => $motivos ?: ['deve_processar=true']];
    }

    private function emprestimoEmProtestoDry($parcela, Carbon $now): array
    {
        $emp = $parcela->emprestimo;
        if (!$emp || !$emp->data_protesto) {
            return ['em_protesto' => false, 'motivos' => ['sem_data_protesto']];
        }

        $dp = Carbon::parse($emp->data_protesto);
        $limite = $now->copy()->subDays(14);

        $emProtesto = $dp->lte($limite);
        return [
            'em_protesto' => $emProtesto,
            'motivos'     => [$emProtesto ? 'data_protesto<=now-14d' : 'data_protesto_recente'],
            'data_protesto' => $dp->toDateString(),
            'limite'        => $limite->toDateString(),
        ];
    }
}
