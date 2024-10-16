<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;

class CobrancaAutomaticaC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:AutomaticaC';

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

        $today = Carbon::today()->toDateString();
        // Verificando se hoje é um feriado
        $isHoliday = Feriado::where('data_feriado', $today)->exists();

        $parcelas = collect(); // Coleção vazia se hoje for um feriado

        if (!$isHoliday) {
            $parcelas = Parcela::where('dt_baixa', null)
                ->whereDate('venc_real', $today)
                ->get()
                ->unique('emprestimo_id');
                echo "<pre>" . json_encode($parcelas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>";
        }


        $r = [];
        foreach ($parcelas as $parcela) {
            if (isset($parcela->emprestimo->company->whatsapp) && $parcela->emprestimo->status == "Pagamento Efetuado") {

                try {

                    $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                    if ($response->successful()) {
                        $r = $response->json();
                        if ($r['loggedIn']) {


                            $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                            $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';
                            $saudacao = self::obterSaudacao();

                            $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
                            $fraseInicial = "

Última chamada, Ainda não identificamos seu pagamento, será aplicado multas e entrará na rota de cobrança!

Segue abaixo link para pagamento parcela diária e acesso todo o histórico de parcelas:

https://sistema.rjemprestimos.com.br/#/parcela/{$parcela->id}

 ";




                            // Montagem das parcelas pendentes
//                             $parcelasString = $parcela->emprestimo->parcelas
//                                 ->filter(function ($item) {
//                                     return $item->atrasadas > 0 && is_null($item->dt_baixa);
//                                 })
//                                 ->map(function ($item) {
//                                     return "
// Data: " . Carbon::parse($item->venc)->format('d/m/Y') . "
// Parcela: {$item->parcela}
// Atrasos: {$item->atrasadas}
// Valor: R$ " . number_format($item->valor, 2, ',', '.') . "
// Multa: R$ " . number_format(($item->saldo - $item->valor) ?? 0, 2, ',', '.') . "
// Juros: R$ " . number_format($item->multa ?? 0, 2, ',', '.') . "
// Pago: R$ " . number_format($item->pago ?? 0, 2, ',', '.') . "
// PIX: " . ($item->chave_pix ?? 'Não Contém') . "
// Status: Pendente
// RESTANTE: R$ " . number_format($item->saldo, 2, ',', '.');
//                                 })
//                                 ->implode("\n\n");



                            // Obtenha a saudação baseada na hora atual

                            // $frase = $saudacaoTexto . $fraseInicial . $parcelasString;
                            $frase = $saudacaoTexto . $fraseInicial;

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

            }
        }

        exit;
    }

    function obterSaudacao()
    {
        $hora = date('H');
        $saudacoesManha = ['Bom dia', 'Olá, bom dia', 'Tenha um excelente dia'];
        $saudacoesTarde = ['Boa tarde', 'Olá, boa tarde', 'Espero que sua tarde esteja ótima'];
        $saudacoesNoite = ['Boa noite', 'Olá, boa noite', 'Espero que sua noite esteja ótima'];

        if ($hora < 12) {
            return $saudacoesManha[array_rand($saudacoesManha)];
        } elseif ($hora < 18) {
            return $saudacoesTarde[array_rand($saudacoesTarde)];
        } else {
            return $saudacoesNoite[array_rand($saudacoesNoite)];
        }
    }

}
