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

        /**
         * 1) Filtros iguais aos atuais
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
         * 2) Pegar SEMPRE a primeira pendente por empréstimo (menor nº de parcela)
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

        $count = $parcelas->count();
        Log::info("Cobranca Automatica A quantidade de clientes: {$count}");

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
            Log::info('CobrancaAutomaticaA: deveProcessarParcela retornou false', [
                'parcela_id'   => $parcela->id ?? null,
                'emprestimo_id'=> $parcela->emprestimo->id ?? null,
                'company_id'   => $parcela->emprestimo->company->id ?? null,
            ]);
            return;
        }

        $company = $parcela->emprestimo->company;

        if ($this->emprestimoEmProtesto($parcela)) {
            Log::info('CobrancaAutomaticaA: empréstimo em protesto, ignorando parcela', [
                'parcela_id'   => $parcela->id ?? null,
                'emprestimo_id'=> $parcela->emprestimo->id ?? null,
                'company_id'   => $company->id ?? null,
            ]);
            return;
        }

        /**
         * Se a empresa tiver configurado o WhatsApp Cloud (Facebook Graph),
         * usamos essa API. Caso contrário, mantemos o fluxo antigo.
         */
        if (!empty($company->whatsapp_cloud_phone_number_id) && !empty($company->whatsapp_cloud_token)) {
            Log::info('CobrancaAutomaticaA: usando WhatsApp Cloud', [
                'parcela_id'     => $parcela->id ?? null,
                'emprestimo_id'  => $parcela->emprestimo->id ?? null,
                'company_id'     => $company->id ?? null,
                'phone_number_id'=> $company->whatsapp_cloud_phone_number_id,
            ]);
            $this->enviarMensagemWhatsAppCloud($parcela);
        } else {
            Log::info('CobrancaAutomaticaA: usando API antiga de WhatsApp', [
                'parcela_id'    => $parcela->id ?? null,
                'emprestimo_id' => $parcela->emprestimo->id ?? null,
                'company_id'    => $company->id ?? null,
                'whatsapp'      => $company->whatsapp ?? null,
                'whatsapp_cobranca' => $company->whatsapp_cobranca ?? null,
            ]);
            $this->enviarMensagemAPIAntiga($parcela);
        }
    }

    private function deveProcessarParcela($parcela)
    {
        $company = $parcela->emprestimo->company ?? null;

        // Só processa se houver alguma configuração de WhatsApp (antiga ou Cloud)
        $temWhatsappAntigo = isset($company->whatsapp) || isset($company->whatsapp_cobranca);
        $temWhatsappCloud  = !empty($company->whatsapp_cloud_phone_number_id) && !empty($company->whatsapp_cloud_token);

        $pode = ($temWhatsappAntigo || $temWhatsappCloud) &&
            $parcela->emprestimo->contaspagar &&
            $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado";

        if (!$pode) {
            Log::info('CobrancaAutomaticaA: parcela não será processada (sem config WhatsApp ou sem contaspagar pago)', [
                'parcela_id'          => $parcela->id ?? null,
                'emprestimo_id'       => $parcela->emprestimo->id ?? null,
                'company_id'          => $company->id ?? null,
                'tem_whatsapp_antigo' => $temWhatsappAntigo,
                'tem_whatsapp_cloud'  => $temWhatsappCloud,
                'tem_contaspagar'     => (bool)($parcela->emprestimo->contaspagar ?? null),
                'status_contaspagar'  => $parcela->emprestimo->contaspagar->status ?? null,
            ]);
        }

        return $pode;
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
        // Usa whatsapp_cobranca se disponível, senão usa whatsapp padrão
        $baseUrl = $company->whatsapp_cobranca ?? $company->whatsapp;

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
        if ((int)$parcelaPesquisa->atrasadas === 0) {
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
        // Usa whatsapp_cobranca se disponível, senão usa whatsapp padrão
        $baseUrl = $parcela->emprestimo->company->whatsapp_cobranca ?? $parcela->emprestimo->company->whatsapp;

        Log::info('CobrancaAutomaticaA: enviarMensagemAPIAntiga - início', [
            'parcela_id'    => $parcela->id ?? null,
            'emprestimo_id' => $parcela->emprestimo->id ?? null,
            'company_id'    => $parcela->emprestimo->company->id ?? null,
            'telefone'      => $telefone,
            'base_url'      => $baseUrl,
        ]);



        $saudacao = $this->obterSaudacao();
        $mensagem = $this->montarMensagem($parcela, $saudacao);

        $data = [
            "numero" => "55" . $telefone,
            "mensagem" => $mensagem
        ];

        try {
            $response = Http::asJson()->post("$baseUrl/enviar-mensagem", $data);
            Log::info('CobrancaAutomaticaA: enviarMensagemAPIAntiga - resposta mensagem', [
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
        } catch (\Throwable $th) {
            Log::error('CobrancaAutomaticaA: erro ao enviar mensagem na API antiga', [
                'exception' => $th->getMessage(),
            ]);
        }
        sleep(4);
        if($parcela->emprestimo->company->mensagem_audio) {
            if($parcela->atrasadas > 0) {
                // Usa whatsapp_cobranca se disponível, senão usa whatsapp padrão
                $baseUrl = $parcela->emprestimo->company->whatsapp_cobranca ?? $parcela->emprestimo->company->whatsapp;
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

                    try {
                        $responseAudio = Http::asJson()->post("$baseUrl/enviar-audio", $data2);
                        Log::info('CobrancaAutomaticaA: enviarMensagemAPIAntiga - resposta audio atraso', [
                            'status' => $responseAudio->status(),
                            'body'   => $responseAudio->body(),
                        ]);
                    } catch (\Throwable $th) {
                        Log::error('CobrancaAutomaticaA: erro ao enviar audio de atraso na API antiga', [
                            'exception' => $th->getMessage(),
                        ]);
                    }
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

                try {
                    $responsePrimeiro = Http::asJson()->post("$baseUrl/enviar-audio", $data3);
                    Log::info('CobrancaAutomaticaA: enviarMensagemAPIAntiga - resposta audio primeira cobrança', [
                        'status' => $responsePrimeiro->status(),
                        'body'   => $responsePrimeiro->body(),
                    ]);
                } catch (\Throwable $th) {
                    Log::error('CobrancaAutomaticaA: erro ao enviar audio de primeira cobrança na API antiga', [
                        'exception' => $th->getMessage(),
                    ]);
                }
            }
        }

    }

    /**
     * Envio via WhatsApp Cloud (Facebook Graph API).
     * Os dados (phone_number_id e token) ficam parametrizados na tabela companies.
     */
    private function enviarMensagemWhatsAppCloud($parcela)
    {
        $company = $parcela->emprestimo->company;

        if (empty($company->whatsapp_cloud_phone_number_id) || empty($company->whatsapp_cloud_token)) {
            Log::warning('CobrancaAutomaticaA: tentar usar WhatsApp Cloud sem configuração', [
                'parcela_id'    => $parcela->id ?? null,
                'emprestimo_id' => $parcela->emprestimo->id ?? null,
                'company_id'    => $company->id ?? null,
            ]);
            return;
        }

        $telefone = preg_replace('/\D/', '', (string)($parcela->emprestimo->client->telefone_celular_1 ?? ''));
        if (!$telefone) {
            Log::warning('CobrancaAutomaticaA: WhatsApp Cloud sem telefone válido', [
                'parcela_id'    => $parcela->id ?? null,
                'emprestimo_id' => $parcela->emprestimo->id ?? null,
                'company_id'    => $company->id ?? null,
            ]);
            return;
        }

        $telefoneCliente = "55" . $telefone;

        // Monta a URL com o phone_number_id parametrizado
        $url = "https://graph.facebook.com/v22.0/{$company->whatsapp_cloud_phone_number_id}/messages";

        Log::info('CobrancaAutomaticaA: enviarMensagemWhatsAppCloud - início', [
            'parcela_id'      => $parcela->id ?? null,
            'emprestimo_id'   => $parcela->emprestimo->id ?? null,
            'company_id'      => $company->id ?? null,
            'telefone'        => $telefoneCliente,
            'phone_number_id' => $company->whatsapp_cloud_phone_number_id,
            'url'             => $url,
        ]);

        // Nome do cliente
        $nomeCliente = $parcela->emprestimo->client->nome_completo ?? 'Cliente';
        
        // Telefone formatado para exibição - usar numero_contato da company
        $telefoneFormatado = $company->numero_contato ?? '';
        // Formatar telefone: (61) 99330 - 5267
        if (strlen($telefoneFormatado) >= 10) {
            $telefoneFormatado = preg_replace('/\D/', '', $telefoneFormatado);
            if (strlen($telefoneFormatado) == 11) {
                $telefoneFormatado = '(' . substr($telefoneFormatado, 0, 2) . ') ' .
                                    substr($telefoneFormatado, 2, 5) . ' - ' .
                                    substr($telefoneFormatado, 7);
            } elseif (strlen($telefoneFormatado) == 10) {
                $telefoneFormatado = '(' . substr($telefoneFormatado, 0, 2) . ') ' .
                                    substr($telefoneFormatado, 2, 4) . ' - ' .
                                    substr($telefoneFormatado, 6);
            }
        }

        // Nome da empresa (header)
        $nomeEmpresa = $company->company ?? 'Empresa';

        // Link para a parcela
        $linkParcela = "#/parcela/{$parcela->id}";

        // Montar payload do template utilidade_cuiabano
        $payload = [
            "messaging_product" => "whatsapp",
            "to"                => $telefoneCliente,
            "type"              => "template",
            "template"          => [
                "name"     => "utilidade_cuiabano",
                "language" => [
                    "code" => "pt_BR",
                ],
                "components" => [
                    [
                        "type" => "header",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $nomeEmpresa
                            ]
                        ]
                    ],
                    [
                        "type"       => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $nomeCliente
                            ],
                            [
                                "type" => "text",
                                "text" => $telefoneFormatado
                            ]
                        ],
                    ],
                    [
                        "type"     => "button",
                        "sub_type" => "url",
                        "index"    => "0",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $linkParcela,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->whatsapp_cloud_token,
                'Content-Type'  => 'application/json',
            ])->post($url, $payload);

            Log::info('CobrancaAutomaticaA: enviarMensagemWhatsAppCloud - resposta', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $th) {
            Log::error('CobrancaAutomaticaA: erro ao enviar mensagem via WhatsApp Cloud', [
                'exception' => $th->getMessage(),
            ]);
        }

        // pequeno intervalo para evitar flood
        sleep(4);
    }
}
