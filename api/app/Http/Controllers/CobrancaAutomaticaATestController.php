<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;

class CobrancaAutomaticaATestController extends Controller
{
    public function dryRun(Request $request)
    {
        $today = Carbon::today();
        $now   = Carbon::now();

        $isWeekend = $now->isSaturday() || $now->isSunday();
        $isHoliday = Feriado::where('data_feriado', $today->toDateString())->exists();

        $meta = [
            'today'      => $today->toDateString(),
            'now'        => $now->toDateTimeString(),
            'isWeekend'  => $isWeekend,
            'isHoliday'  => $isHoliday,
        ];

        if ($isHoliday) {
            return response()->json([
                'meta'   => $meta,
                'counts' => ['candidatas' => 0, 'deduplicadas' => 0, 'would_send' => 0],
                'items'  => [],
                'note'   => 'Hoje é feriado: rotina não executa (sem envios).',
            ]);
        }

        $parcelasQuery = Parcela::query()
            ->whereNull('dt_baixa')
            // se quiser, evite registros sem vínculo:
            // ->whereNotNull('emprestimo_id')
            ->with([
                'emprestimo',
                'emprestimo.company',
                'emprestimo.client',
                'emprestimo.contaspagar',
                'emprestimo.parcelas',
            ])
            ->orderByDesc('id');

        if ($request->filled('id')) {
            $parcelasQuery->where('id', $request->integer('id'));
        }

        if ($isWeekend) {
            $parcelasQuery->where('atrasadas', '>', 0);
        }

        $parcelas = $request->filled('limit')
            ? $parcelasQuery->limit($request->integer('limit', 50))->get()
            : $parcelasQuery->get();

        if ($isWeekend) {
            $parcelas = $parcelas->filter(function ($parcela) {
                $dataProtesto = data_get($parcela, 'emprestimo.data_protesto');
                if (!$dataProtesto) return true;
                return !Carbon::parse($dataProtesto)->lte(Carbon::now()->subDays(1));
            });
        } else {
            $hoje = Carbon::now();
            $parcelas = $parcelas->filter(function ($parcela) use ($hoje) {
                $deveCobrarHoje = ($d = data_get($parcela, 'emprestimo.deve_cobrar_hoje'))
                    ? Carbon::parse($d)->isSameDay($hoje)
                    : false;

                $vencimentoHoje = ($vr = data_get($parcela, 'venc_real'))
                    ? Carbon::parse($vr)->isSameDay($hoje)
                    : false;

                return $deveCobrarHoje || $vencimentoHoje;
            });
        }

        $parcelas = $parcelas->unique('emprestimo_id')->values();

        $items = [];
        $wouldSendCount = 0;

        foreach ($parcelas as $p) {
            $clienteNome = data_get($p, 'emprestimo.client.nome_completo');
            $empresaNome = data_get($p, 'emprestimo.company.nome');
            $whatsapp    = data_get($p, 'emprestimo.company.whatsapp');
            $statusCP    = data_get($p, 'emprestimo.contaspagar.status');

            $trace = [
                'parcela_id'        => $p->id,
                'emprestimo_id'     => $p->emprestimo_id,
                'cliente'           => $clienteNome,
                'empresa'           => $empresaNome,
                'company_whatsapp'  => $whatsapp,
                'contaspagar_status'=> $statusCP,
                'atrasadas'         => (int) $p->atrasadas,
                'venc_real'         => ($p->venc_real ? Carbon::parse($p->venc_real)->toDateString() : null),
                'regras'            => [],
                'blockers'          => [],
                'selected_by'       => $isWeekend ? 'fim_de_semana' : 'dia_util',
                'filters'           => [],
                'would_send'        => false,
            ];

            // 1) podeProcessarParcela()
            $pp = $this->podeProcessarParcelaDry($p, $today);
            $trace['regras']['pode_processar_parcela'] = $pp;
            if (!$pp['ok']) $trace['blockers'][] = 'podeProcessarParcela:false';

            // 2) deveProcessarParcela()
            $dp = $this->deveProcessarParcelaDry($p);
            $trace['regras']['deve_processar_parcela'] = $dp;
            if (!$dp['ok']) $trace['blockers'][] = 'deveProcessarParcela:false';

            // 3) emprestimoEmProtesto()
            $ep = $this->emprestimoEmProtestoDry($p, $now);
            $trace['regras']['emprestimo_em_protesto'] = $ep;
            if ($ep['em_protesto']) $trace['blockers'][] = 'emprestimoEmProtesto:true';

            $trace['would_send'] = $pp['ok'] && $dp['ok'] && !$ep['em_protesto'];
            if ($trace['would_send']) $wouldSendCount++;

            if ($isWeekend) {
                $trace['filters'][] = 'atrasadas>0';
                $dataProtesto = data_get($p, 'emprestimo.data_protesto');
                $okProtesto = !$dataProtesto || Carbon::parse($dataProtesto)->gt(Carbon::now()->subDay());
                $trace['filters'][] = 'data_protesto ' . ($dataProtesto ?: 'null') . ' => ' . ($okProtesto ? 'ok' : 'fail');
            } else {
                $trace['filters'][] = 'deve_cobrar_hoje=' . (data_get($p, 'emprestimo.deve_cobrar_hoje') ? Carbon::parse(data_get($p, 'emprestimo.deve_cobrar_hoje'))->toDateString() : 'null');
                $trace['filters'][] = 'venc_real=' . ($p->venc_real ? Carbon::parse($p->venc_real)->toDateString() : 'null');
            }

            // Ajuda extra: se o relacionamento emprestimo vier nulo, sinalizamos no item
            if (is_null($p->emprestimo)) {
                $trace['blockers'][] = 'emprestimo:null';
            }

            $items[] = $trace;
        }

        return response()->json([
            'meta'   => $meta,
            'counts' => [
                'candidatas'   => $parcelas->count(),
                'deduplicadas' => $parcelas->count(),
                'would_send'   => $wouldSendCount,
            ],
            'items'  => $items,
        ]);
    }

    // ======= DRY helpers =======

    private function podeProcessarParcelaDry($parcela, Carbon $today): array
    {
        $motivos = [];

        $dtBaixa  = $parcela->dt_baixa;
        $vencReal = $parcela->venc_real ? Carbon::parse($parcela->venc_real) : null;

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

        $qtdParcelasEmp = $parcela->emprestimo_id
            ? Parcela::where('emprestimo_id', $parcela->emprestimo_id)->count()
            : 0;

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

        $whats = data_get($emp, 'company.whatsapp');
        $conta = data_get($emp, 'contaspagar');
        $status= data_get($emp, 'contaspagar.status');

        $okWhats = !is_null($whats);
        $okCP    = !is_null($conta);
        $okStat  = $okCP && $status === "Pagamento Efetuado";

        $ok = $okWhats && $okCP && $okStat;

        $motivos = [];
        if (!$okWhats) $motivos[] = 'company.whatsapp_inexistente';
        if (!$okCP)    $motivos[] = 'contaspagar_inexistente';
        if ($okCP && !$okStat) $motivos[] = 'contaspagar.status!=' . ($status ?? 'null');

        // se nem emprestimo existe, já marca
        if (is_null($emp)) $motivos[] = 'emprestimo:null';

        return ['ok' => $ok, 'motivos' => $motivos ?: ['deve_processar=true']];
    }

    private function emprestimoEmProtestoDry($parcela, Carbon $now): array
    {
        $dp = data_get($parcela, 'emprestimo.data_protesto');
        if (!$dp) {
            return ['em_protesto' => false, 'motivos' => ['sem_data_protesto']];
        }

        $dpC    = Carbon::parse($dp);
        $limite = $now->copy()->subDays(14);
        $emProtesto = $dpC->lte($limite);

        return [
            'em_protesto'   => $emProtesto,
            'motivos'       => [$emProtesto ? 'data_protesto<=now-14d' : 'data_protesto_recente'],
            'data_protesto' => $dpC->toDateString(),
            'limite'        => $limite->toDateString(),
        ];
    }
}
