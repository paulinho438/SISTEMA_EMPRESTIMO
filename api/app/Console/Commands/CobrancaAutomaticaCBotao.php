<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Juros;
use App\Models\Parcela;
use App\Models\Feriado;
use App\Models\BotaoCobranca;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Carbon\Carbon;

class CobrancaAutomaticaCBotao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cobranca:AutomaticaCBotao';

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

        $presseds = BotaoCobranca::where('is_active', true)->where('click_count', 3)->get();

        foreach ($presseds as $pressed) {
            $pressed->update([
                'is_active' => false
            ]);

            $today = Carbon::today()->toDateString();
            // Verificando se hoje é um feriado
            $isHoliday = Feriado::where('data_feriado', $today)->exists();

            $parcelas = collect(); // Coleção vazia se hoje for um feriado

            if (!$isHoliday) {
                $parcelas = Parcela::where('dt_baixa', null)
                    ->whereNull('valor_recebido_pix')
                    ->whereNull('valor_recebido')
                    ->whereDate('venc_real', $today)
                    ->whereHas('emprestimo', function ($query) use ($pressed) {
                        $query->where('company_id', $pressed->company_id);
                    })
                    ->get()->unique('emprestimo_id');
            }


            $r = [];
            foreach ($parcelas as $parcela) {
                if (isset($parcela->emprestimo->company->whatsapp) && $parcela->emprestimo->contaspagar && $parcela->emprestimo->contaspagar->status == "Pagamento Efetuado") {

                    try {

                        $response = Http::get($parcela->emprestimo->company->whatsapp . '/logar');

                        if ($response->successful()) {
                            $r = $response->json();
                            if ($r['loggedIn']) {


                                $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1);
                                $baseUrl = $parcela->emprestimo->company->whatsapp . '/enviar-mensagem';
                                $saudacao = self::obterSaudacao();
                            $parcelaPendente = self::encontrarPrimeiraParcelaPendente($parcela->emprestimo->parcelas);
                            $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . "!";
                            $fraseInicial = "

🤷‍♂️ Última chamada, Ainda não identificamos seu pagamento, será aplicado multas e entrará na rota de cobrança!

Segue abaixo link para pagamento parcela diária e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}
";

$valorJuros = $parcelaPendente->saldo - $parcelaPendente->emprestimo->valor;
if(count($parcela->emprestimo->parcelas) == 1){
if(!$parcelaPendente->emprestimo->pagamentominimo){
    $fraseInicial .= "Copie e cole abaixo a chave pix

Beneficiário: {$parcelaPendente->emprestimo->banco->info_recebedor_pix}
Chave pix: {$parcela->emprestimo->banco->chavepix}

📲 Entre em contato pelo WhatsApp {$parcelaPendente->emprestimo->company->numero_contato}
";
}else{
    $fraseInicial .= "
💸 Pagamento Total R$ {$parcelaPendente->saldo}

Pagamento mínimo - Juros R$ {$valorJuros}

Para pagamento de demais valores



    ";
}



}


if($parcelaPendente !=  null && $parcelaPendente->chave_pix != ''){
    $fraseInicial .= "Copie e cole abaixo a chave pix e faça o pagamento de R$ ".$parcelaPendente->saldo." referente a parcela do dia:

{$parcelaPendente->chave_pix}

📲 Para mais informações WhatsApp {$parcelaPendente->emprestimo->company->numero_contato}
";
}else if(count($parcela->emprestimo->parcelas) > 1){
    $fraseInicial .= "Copie e cole abaixo a chave pix e faça o pagamento referente ao saldo pendente de R$ ".$parcelaPendente->totalPendenteHoje()."

Beneficiário: {$parcelaPendente->emprestimo->banco->info_recebedor_pix}
Chave pix: {$parcela->emprestimo->banco->chavepix}
";
}




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

    function encontrarPrimeiraParcelaPendente($parcelas) {

        foreach($parcelas as $parcela){
            if($parcela->dt_baixa === '' || $parcela->dt_baixa === null){
                return $parcela;
            }
        }

        return null;
    }
}
