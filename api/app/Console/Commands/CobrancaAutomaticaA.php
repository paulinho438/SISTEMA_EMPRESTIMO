<?php

namespace App\Console\Commands;

use App\Jobs\EnviarMensagemWhatsApp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Services\WAPIService;
use Illuminate\Support\Facades\File;

class CobrancaAutomaticaA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:AutomaticaA';

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

        Log::info("Cobranca Automatica A inicio de rotina");

        $today = Carbon::today()->toDateString();
        $isHoliday = Feriado::where('data_feriado', $today)->exists();

        if ($isHoliday) {
            return 0;
        }

        $todayHoje = now();

        $parcelasQuery = Parcela::whereNull('dt_baixa')->with('emprestimo');

        if (($todayHoje->isSaturday() || $todayHoje->isSunday())) {
            $parcelasQuery->where('atrasadas', '>', 0);
        }

        $parcelasQuery->orderByDesc('id');
        $parcelas = $parcelasQuery->get();

        if (($todayHoje->isSaturday() || $todayHoje->isSunday())) {
            $parcelas = $parcelas->filter(function ($parcela) {
                $dataProtesto = optional($parcela->emprestimo)->data_protesto;

                if (!$dataProtesto) {
                    return true;
                }

                return !Carbon::parse($dataProtesto)->lte(Carbon::now()->subDays(1));
            });
        }

        if (!($todayHoje->isSaturday() || $todayHoje->isSunday())) {
            $parcelas = $parcelas->filter(function ($parcela) use ($todayHoje) {
                $emprestimo = $parcela->emprestimo;

                $deveCobrarHoje = $emprestimo &&
                    !is_null($emprestimo->deve_cobrar_hoje) &&
                    Carbon::parse($emprestimo->deve_cobrar_hoje)->isSameDay($todayHoje);

                $vencimentoHoje = $parcela->venc_real &&
                    Carbon::parse($parcela->venc_real)->isSameDay($todayHoje);

                return $deveCobrarHoje || $vencimentoHoje;
            });
        }

        // Remover duplicados e resetar índices
        $parcelas = $parcelas->unique('emprestimo_id')->values();

        $count = count($parcelas);
        Log::info("Cobranca Automatica A quantidade de clientes: {$count}");
        //$parcelas = Parcela::where('id', 23167)->get();
        foreach ($parcelas as $parcela) {

            if (self::podeProcessarParcela($parcela)) {
                $this->processarParcela($parcela);
                sleep(4);
            }
        }
        Log::info("Cobranca Automatica A finalizada");
        return 0;
    }

    private function processarParcela($parcela)
    {
        if (!$this->deveProcessarParcela($parcela)) {
            return;
        }

        if ($this->emprestimoEmProtesto($parcela)) {
            return;
        }
        $this->enviarMensagem($parcela);
    }

    private function deveProcessarParcela($parcela)
    {
        return isset($parcela->emprestimo->company->whatsapp) &&
            $parcela->emprestimo->contaspagar &&
            $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado";
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
        $wapiService = new WAPIService();

        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
        $telefoneCliente = "55" . $telefone;

        $company = $parcela->emprestimo->company;
        $baseUrl = $company->whatsapp;

        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($parcela, $saudacao);

        if (!is_null($company->token_api_wtz) && !is_null($company->instance_id)) {
            $wapiService->enviarMensagem($company->token_api_wtz, $company->instance_id, [
                "phone" => $telefoneCliente,
                "message" => $mensagem
            ]);
        }

        sleep(1);

        if ($company->mensagem_audio && $parcela->atrasadas > 0) {
            $tipo = match ($parcela->atrasadas) {
                2 => "1.1",
                4 => "2.1",
                6 => "3.1",
                8 => "4.1",
                10 => "5.1",
                15 => "6.1",
                default => "0"
            };

            if ($tipo !== "0") {
                $nomeCliente = $parcela->emprestimo->client->nome_completo;
                $mensagemAudio = match ($tipo) {
                    "1.1" => "Oi $nomeCliente, escute com atenção o áudio abaixo para ficar bem entendido!",
                    "2.1", "3.1", "5.1" => "E aí $nomeCliente, olha só vamos organizar sua questão!",
                    "4.1" => "$nomeCliente, olha só atenção que vamos organizar essa parada agora",
                    "6.1" => "$nomeCliente, seu caso tá sério mesmo",
                    default => ""
                };

                if (!empty($mensagemAudio)) {
                    $wapiService->enviarMensagem($company->token_api_wtz, $company->instance_id, [
                        "phone" => $telefoneCliente,
                        "message" => $mensagemAudio
                    ]);
                }

                $nomeArquivo = match ($tipo) {
                    "1.1" => "mensagem_1_atraso_2d.ogg",
                    "2.1" => "mensagem_1_atraso_4d.ogg",
                    "3.1" => "mensagem_1_atraso_6d.ogg",
                    "4.1" => "mensagem_1_atraso_8d.ogg",
                    "5.1" => "mensagem_1_atraso_10d.ogg",
                    "6.1" => "mensagem_1_atraso_15d.ogg",
                    default => null
                };

                if ($nomeArquivo) {
                    $caminhoArquivo = storage_path('app/public/audios/' . $nomeArquivo);
                    if (File::exists($caminhoArquivo)) {
                        $conteudo = File::get($caminhoArquivo);
                        $base64 = 'data:audio/ogg;base64,' . base64_encode($conteudo);

                        $wapiService->enviarMensagemAudio($company->token_api_wtz, $company->instance_id, [
                            "phone" => $telefoneCliente,
                            "audio" => $base64
                        ]);
                    }
                }
            }
        }

        // Verifica se é o primeiro pagamento
        if (count($parcela->emprestimo->parcelas) === 1 && $parcela->atrasadas === 0) {
            $caminhoArquivo = storage_path('app/public/audios/msginfo1.ogg');
            if (File::exists($caminhoArquivo)) {
                $conteudo = File::get($caminhoArquivo);
                $base64 = 'data:audio/ogg;base64,' . base64_encode($conteudo);

                $wapiService->enviarMensagemAudio($company->token_api_wtz, $company->instance_id, [
                    "phone" => $telefoneCliente,
                    "audio" => $base64
                ]);
            }
        }
    }


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

    private function encontrarPrimeiraParcelaPendente($parcelas)
    {
        foreach ($parcelas as $parcela) {
            if (is_null($parcela->dt_baixa)) {
                return $parcela;
            }
        }

        return null;
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
}
