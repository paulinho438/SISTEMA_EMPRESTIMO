<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Client;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;

class MensagemAutomaticaRenovacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mensagem:AutomaticaRenovacao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mensagem Automática para Renovação';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Realizando a Mensagem Renovacao Automatica');

        // Buscar clientes e seus empréstimos
        $clients = Client::whereDoesntHave('emprestimos', function ($query) {
            $query->whereHas('parcelas', function ($query) {
                $query->whereNull('dt_baixa'); // Filtra empréstimos com parcelas pendentes
            });
        })
            ->with(['emprestimos' => function ($query) {
                $query->whereDoesntHave('parcelas', function ($query) {
                    $query->whereNull('dt_baixa'); // Carrega apenas empréstimos sem parcelas pendentes
                });
            }])
            ->get();

        foreach ($clients as $client) {
            if ($client->emprestimos) {
                foreach ($client->emprestimos as $emprestimo) {
                    if ($emprestimo->company) {
                        if ($emprestimo->company->envio_automatico_renovacao == 1 && $emprestimo->mensagem_renovacao == 0) {
                            if ($emprestimo->count_late_parcels <= 2) {
                                $this->enviarMensagem($client, 'Olá ' . $client->nome_completo . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($emprestimo->valor + 100) . ' Gostaria de contratar?');
                            } elseif ($emprestimo->count_late_parcels >= 3 && $emprestimo->count_late_parcels <= 5) {
                                $this->enviarMensagem($client, 'Olá ' . $client->nome_completo . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($emprestimo->valor) . ' Gostaria de contratar?');
                            } elseif ($emprestimo->count_late_parcels >= 6) {
                                $this->enviarMensagem($client, 'Olá ' . $client->nome_completo . ', estamos entrando em contato para informar sobre seu empréstimo. Temos uma ótima notícia: você possui um valor pré-aprovado de R$ ' . ($emprestimo->valor - 100) . ' Gostaria de contratar?');
                            }

                            $emprestimo->mensagem_renovacao = 1;
                            $emprestimo->save();
                        }
                    }
                }
            }
        }

        exit;
    }

    public function enviarMensagem($cliente, $frase)
    {
        try {

            $response = Http::get($cliente->emprestimos->company->whatsapp . '/logar');

            if ($response->successful()) {
                $r = $response->json();
                if ($r['loggedIn']) {

                    $telefone = preg_replace('/\D/', '', $cliente->telefone_celular_1);
                    $baseUrl = $cliente->emprestimos->company->whatsapp . '/enviar-mensagem';

                    $data = [
                        "numero" => "55" . $telefone,
                        "mensagem" => $frase
                    ];

                    $response = Http::asJson()->post($baseUrl, $data);
                    sleep(8);
                }
            }
        } catch (\Throwable $th) {
            dd($th);
        }

        return true;
    }
}
