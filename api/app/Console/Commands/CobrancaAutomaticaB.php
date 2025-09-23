<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;

use App\Services\WAPIService;
use Illuminate\Support\Facades\File;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class CobrancaAutomaticaB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:AutomaticaB';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cobrança automatica das parcelas em atraso';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Realizando a Cobrança Automatica das Parcelas em Atrasos');
        Log::info("Cobranca Automatica B inicio de rotina");

        $today = Carbon::today()->toDateString();
        $isHoliday = Feriado::where('data_feriado', $today)->exists();
        if ($isHoliday) { return 0; }

        $todayHoje = now();

        /**
         * 1) Mesmos critérios de seleção
         */
        $filtered = Parcela::query()
            ->whereNull('dt_baixa')
            ->when($todayHoje->isWeekend(), function ($q) {
                $q->where('atrasadas', '>', 0)
                    ->whereHas('emprestimo', function ($qq) {
                        $qq->where(function ($s) {
                            $s->whereNull('data_protesto')
                                ->orWhere('data_protesto', '>', Carbon::now()->subDay());
                        });
                    });
            }, function ($q) use ($todayHoje) {
                $q->where(function ($s) use ($todayHoje) {
                    $s->whereHas('emprestimo', function ($qq) use ($todayHoje) {
                        $qq->whereDate('deve_cobrar_hoje', $todayHoje->toDateString());
                    })
                        ->orWhereDate('venc_real', $todayHoje->toDateString());
                });
            });

        /**
         * 2) Primeira pendente por empréstimo (menor nº de parcela)
         */
        $sub = (clone $filtered)
            ->selectRaw('MIN(parcela) as min_parcela, emprestimo_id')
            ->groupBy('emprestimo_id');

        $parcelas = (clone $filtered)
            ->joinSub($sub, 'firsts', function ($join) {
                $join->on('parcelas.emprestimo_id', '=', 'firsts.emprestimo_id')
                    ->on('parcelas.parcela', '=', 'firsts.min_parcela');
            })
            ->with(['emprestimo', 'emprestimo.company', 'emprestimo.client'])
            ->orderBy('parcelas.emprestimo_id')
            ->orderBy('parcelas.parcela')
            ->get();

        /**
         * 3) Log e variáveis finais
         */
        $r = [];
        $count = $parcelas->count();
        Log::info("Cobranca Automatica B quantidade de clientes: {$count}");
        foreach ($parcelas as $parcela) {
            sleep(4);

            if ($this->emprestimoEmProtesto($parcela)) {
                continue;
            }

            if (!self::podeProcessarParcela($parcela)) {
                continue;
            }

            if (
                $parcela->emprestimo &&
                $parcela->emprestimo->contaspagar &&
                $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado"
            ) {

                if($parcela->emprestimo->company->id == 8) {
                    $this->enviarMensagemAPIAntiga($parcela);
                    continue;
                }

                $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';

                $saudacao = self::obterSaudacao();

                $parcelaPendente = self::encontrarPrimeiraParcelaPendente($parcela->emprestimo->parcelas);

                $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
                $fraseInicial = "

🤷‍♂️ Não identificamos seu pagamento na data de hoje, lembrando que é até 40 minutos para processar se não pagou evite multas!

⚠️  *sempre enviar o comprovante para ajudar na conferência não se esqueça*

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$parcela->emprestimo->company->numero_contato}
";

                $frase = $saudacaoTexto . $fraseInicial;

                $telefoneCliente = "55" . $telefone;

                $wapiService = new WAPIService();
                $wapiService->enviarMensagem(
                    $parcela->emprestimo->company->token_api_wtz,
                    $parcela->emprestimo->company->instance_id,
                    [
                        "phone" => $telefoneCliente,
                        "message" => $frase
                    ]
                );

                sleep(1);
                if ($parcela->emprestimo->company->mensagem_audio) {
                    if ($parcela->atrasadas > 0) {
                        $baseUrl = $parcela->emprestimo->company->whatsapp;
                        $tipo = "0";
                        switch ($parcela->atrasadas) {
                            case 2: $tipo = "1.2"; break;
                            case 4: $tipo = "2.2"; break;
                            case 6: $tipo = "3.2"; break;
                            case 8: $tipo = "4.2"; break;
                            case 10: $tipo = "5.2"; break;
                            case 15: $tipo = "6.2"; break;
                        }

                        if ($tipo != "0") {
                            $audioMap = [
                                "1.2" => "mensagem_2_atraso_2d.ogg",
                                "2.2" => "mensagem_2_atraso_4d.ogg",
                                "3.2" => "mensagem_2_atraso_6d.ogg",
                                "4.2" => "mensagem_2_atraso_8d.ogg",
                                "5.2" => "mensagem_2_atraso_10d.ogg",
                                "6.2" => "mensagem_2_atraso_15d.ogg"
                            ];

                            if (isset($audioMap[$tipo])) {
                                $nomeArquivo = $audioMap[$tipo];
                                $caminhoArquivo = storage_path("app/public/audios/{$nomeArquivo}");

                                if (File::exists($caminhoArquivo)) {
                                    $conteudo = File::get($caminhoArquivo);
                                    $base64 = 'data:audio/ogg;base64,' . base64_encode($conteudo);

                                    $wapiService = new WAPIService();
                                    $wapiService->enviarMensagemAudio(
                                        $parcela->emprestimo->company->token_api_wtz,
                                        $parcela->emprestimo->company->instance_id,
                                        [
                                            "phone" => "55" . $telefone,
                                            "audio" => $base64
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }

                //identificar se o emprestimo é mensal
                //identificar se é a primeira cobranca
                if (count($parcela->emprestimo->parcelas) == 1 && $parcela->atrasadas == 0) {
                    $nomeArquivo = "msginfo2.ogg";
                    $caminhoArquivo = storage_path("app/public/audios/{$nomeArquivo}");

                    if (File::exists($caminhoArquivo)) {
                        $conteudo = File::get($caminhoArquivo);
                        $base64 = 'data:audio/ogg;base64,' . base64_encode($conteudo);

                        $wapiService = new WAPIService();
                        $wapiService->enviarMensagemAudio(
                            $parcela->emprestimo->company->token_api_wtz,
                            $parcela->emprestimo->company->instance_id,
                            [
                                "phone" => "55" . $telefone,
                                "audio" => $base64
                            ]
                        );
                    }
                }
            }
        }
        Log::info("Cobranca Automatica B finalizada");
        exit;
    }

    function obterSaudacao()
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

    function encontrarPrimeiraParcelaPendente($parcelas)
    {

        foreach ($parcelas as $parcela) {
            if ($parcela->dt_baixa === '' || $parcela->dt_baixa === null) {
                return $parcela;
            }
        }

        return null;
    }

    private function emprestimoEmProtesto($parcela)
    {
        if (!$parcela->emprestimo || !$parcela->emprestimo->data_protesto) {
            return false;
        }

        return Carbon::parse($parcela->emprestimo->data_protesto)->lte(Carbon::now()->subDays(14));

    }

    private static function podeProcessarParcela($parcela)
    {
        // Se já veio uma instância atualizada, só dá fresh(); senão busca por id
        $parcelaPesquisa = $parcela instanceof \App\Models\Parcela
            ? $parcela->fresh()
            : \App\Models\Parcela::find($parcela->id ?? null);

        if (!$parcelaPesquisa) {
            Log::warning('Parcela não encontrada para processamento.');
            return false;
        }

        // Blindagem extra caso os casts não estejam aplicados por algum motivo
        $vencReal = $parcelaPesquisa->venc_real
            ? ($parcelaPesquisa->venc_real instanceof Carbon
                ? $parcelaPesquisa->venc_real
                : Carbon::parse($parcelaPesquisa->venc_real))
            : null;

        $dtBaixa = $parcelaPesquisa->dt_baixa
            ? ($parcelaPesquisa->dt_baixa instanceof Carbon
                ? $parcelaPesquisa->dt_baixa
                : Carbon::parse($parcelaPesquisa->dt_baixa))
            : null;

        // 1) Se vence hoje e ainda não foi baixada, processa
        if ($vencReal?->isSameDay(today()) && $dtBaixa === null) {
            return true;
        }

        // 2) Já baixada? não processa
        if ($dtBaixa !== null) {
            Log::info("Parcela {$parcelaPesquisa->id} já baixada, não será processada novamente.");
            return false;
        }

        // 3) Não está (mais) atrasada? não processa
        if ((int) $parcelaPesquisa->atrasadas === 0) {
            Log::info("Parcela {$parcelaPesquisa->id} não está mais atrasada, não será processada novamente.");
            return false;
        }

        // 4) Verificação de parcela única — use COUNT direto (evita carregar a coleção inteira)
        $qtdParcelas = \App\Models\Parcela::where('emprestimo_id', $parcelaPesquisa->emprestimo_id)->count();

        if ($qtdParcelas === 1) {
            // Se for única e ainda vai vencer no futuro, não processa
            if ($vencReal && $vencReal->greaterThan(today())) {
                return false;
            }
        }

        return true;
    }

    private function enviarMensagemAPIAntiga($parcela)
    {
        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $baseUrl = $parcela->emprestimo->company->whatsapp;



        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($parcela, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        Http::asJson()->post("$baseUrl/enviar-mensagem", $data);
        sleep(4);
        if($parcela->emprestimo->company->mensagem_audio) {
            if($parcela->atrasadas > 0) {
                $baseUrl = $parcela->emprestimo->company->whatsapp;
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

                if($tipo != "0"){
                    $data2 = [
                        "numero" => "55" . $telefone,
                        "nomeCliente" => $parcela->emprestimo->client->nome_completo,
                        "tipo" => $tipo
                    ];

                    Http::asJson()->post("$baseUrl/enviar-audio", $data2);
                }
            }
        }

        //identificar se o emprestimo é mensal
        //identificar se é a primeira cobranca
        if(count($parcela->emprestimo->parcelas) == 1) {
            if($parcela->atrasadas == 0){
                $data3 = [
                    "numero" => "55" . $telefone,
                    "nomeCliente" => "Sistema",
                    "tipo" => "msginfo1"
                ];

                Http::asJson()->post("$baseUrl/enviar-audio", $data3);
            }
        }

    }
}
