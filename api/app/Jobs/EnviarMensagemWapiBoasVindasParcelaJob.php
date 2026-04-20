<?php

namespace App\Jobs;

use App\Models\Parcela;
use App\Services\WAPIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Envia o WhatsApp com link da parcela após aprovação/pagamento do empréstimo (W-API).
 * Executado em fila para não bloquear a resposta HTTP do autorizador.
 */
class EnviarMensagemWapiBoasVindasParcelaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(protected int $parcelaId)
    {
    }

    public function handle(): void
    {
        $parcela = Parcela::with(['emprestimo.client', 'emprestimo.company'])->find($this->parcelaId);
        if (!$parcela || !$parcela->emprestimo || !$parcela->emprestimo->client) {
            Log::warning('EnviarMensagemWapiBoasVindasParcelaJob: parcela ou relacionamentos ausentes', [
                'parcela_id' => $this->parcelaId,
            ]);

            return;
        }

        $company = $parcela->emprestimo->company;
        if (is_null($company->token_api_wtz) || is_null($company->instance_id)) {
            Log::warning('EnviarMensagemWapiBoasVindasParcelaJob: empresa sem token_api_wtz ou instance_id', [
                'company_id' => $company->id ?? null,
            ]);

            return;
        }

        $telefone = preg_replace('/\D/', '', $parcela->emprestimo->client->telefone_celular_1 ?? '');
        if ($telefone === '') {
            return;
        }

        $telefoneCliente = '55' . $telefone;
        $saudacao = $this->obterSaudacao();
        $saudacaoTexto = "{$saudacao}, " . $parcela->emprestimo->client->nome_completo . '!';
        $fraseInicial = "

Relatório de Parcelas Pendentes:

Segue abaixo link para pagamento parcela e acesso todo o histórico de parcelas:

https://sistema.agecontrole.com.br/#/parcela/{$parcela->id}

📲 Para mais informações WhatsApp {$company->numero_contato}
";
        $frase = $saudacaoTexto . $fraseInicial;

        $wapi = new WAPIService();
        $wapi->enviarMensagem($company->token_api_wtz, $company->instance_id, [
            'delayMessage' => 1,
            'phone' => $telefoneCliente,
            'message' => $frase,
        ]);
    }

    private function obterSaudacao(): string
    {
        $hora = (int) date('H');
        $saudacoesManha = ['🌤️ Bom dia', '👋 Olá, bom dia', '🌤️ Tenha um excelente dia'];
        $saudacoesTarde = ['🌤️ Boa tarde', '👋 Olá, boa tarde', '🌤️ Espero que sua tarde esteja ótima'];
        $saudacoesNoite = ['🌤️ Boa noite', '👋 Olá, boa noite', '🌤️ Espero que sua noite esteja ótima'];
        if ($hora < 12) {
            return $saudacoesManha[array_rand($saudacoesManha)];
        }
        if ($hora < 18) {
            return $saudacoesTarde[array_rand($saudacoesTarde)];
        }

        return $saudacoesNoite[array_rand($saudacoesNoite)];
    }
}
