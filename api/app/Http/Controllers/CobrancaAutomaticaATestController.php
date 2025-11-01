<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CobrancaAutomaticaATestController extends Controller
{
    /**
     * Envia uma mensagem de teste
     * 
     * @param Request $request 
     * Parâmetros: baseUrl (obrigatório), telefone (obrigatório), mensagem (obrigatório)
     */
    public function enviarMensagemTeste(Request $request)
    {
        $request->validate([
            'baseUrl' => 'required|string',
            'telefone' => 'required|string',
            'mensagem' => 'required|string'
        ]);

        try {
            // Remove caracteres não numéricos do telefone
            $telefone = preg_replace('/\D/', '', $request->telefone);
            
            // Adiciona o código do país se não tiver
            if (!str_starts_with($telefone, '55')) {
                $telefone = '55' . $telefone;
            }

            $baseUrl = rtrim($request->baseUrl, '/');
            $mensagem = $request->mensagem;
            
            $endpoint = "$baseUrl/enviar-mensagem";

            // Prepara os dados para envio
            $data = [
                "numero" => $telefone,
                "mensagem" => $mensagem
            ];

            // Log da tentativa de envio
            Log::info("Teste WhatsApp - Tentando enviar mensagem", [
                'endpoint' => $endpoint,
                'data' => $data
            ]);

            // Envia a mensagem com timeout de 30 segundos
            $response = Http::timeout(30)
                ->asJson()
                ->post($endpoint, $data);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseJson = null;
            
            try {
                $responseJson = $response->json();
            } catch (\Exception $e) {
                // Se não conseguir converter para JSON, mantém o body como string
            }

            // Log da resposta
            Log::info("Teste WhatsApp - Resposta recebida", [
                'status_code' => $statusCode,
                'response_body' => $responseBody
            ]);

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Mensagem enviada com sucesso' : 'Erro ao enviar mensagem',
                'request' => [
                    'endpoint' => $endpoint,
                    'telefone' => $telefone,
                    'mensagem' => $mensagem
                ],
                'response' => [
                    'status_code' => $statusCode,
                    'body' => $responseBody,
                    'json' => $responseJson,
                    'successful' => $response->successful(),
                    'failed' => $response->failed(),
                    'client_error' => $response->clientError(),
                    'server_error' => $response->serverError()
                ]
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Teste WhatsApp - Erro de conexão", [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro de conexão com o servidor WhatsApp',
                'error' => $e->getMessage(),
                'error_type' => 'connection_error'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error("Teste WhatsApp - Erro geral", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar mensagem',
                'error' => $e->getMessage(),
                'error_type' => 'general_error'
            ], 500);
        }
    }

    /**
     * Obtém saudação baseada na hora do dia
     */
    private function obterSaudacao()
    {
        $hora = date('H');
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

    /**
     * Monta a mensagem de cobrança
     */
    private function montarMensagem($parcela, $saudacao)
    {
        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
        $fraseInicial = "

Relatório de Parcelas Pendentes:

⚠️ *sempre enviar o comprovante para ajudar na conferência não se esqueça*

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$parcela->emprestimo->company->numero_contato}
";
        return $saudacaoTexto . $fraseInicial;
    }
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
