<?php

namespace App\Jobs;

use App\Models\Contaspagar;
use App\Models\Emprestimo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\BcodexService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CustomLog;
use App\Models\Movimentacaofinanceira;

use App\Services\WAPIService;
use Illuminate\Support\Facades\File;


class EnviarComprovanteFornecedor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contaspagar;
    protected $bcodexService;

    protected $comprovante;
    protected $wapiService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contaspagar $contaspagar, BcodexService $bcodexService, ?array $comprovante = [])
    {
        $this->contaspagar = $contaspagar;
        $this->bcodexService = $bcodexService;
        $this->comprovante = $comprovante;
        $this->wapiService = new WAPIService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            if ($this->comprovante) {
                // Renderizar o HTML da view
                $html = view('comprovante-template', $this->comprovante['dados'])->render();

                // Salvar o HTML em um arquivo temporário
                $htmlFilePath = storage_path('app/public/comprovante.html');
                file_put_contents($htmlFilePath, $html);

                // Caminho para o arquivo PNG de saída
                $pngPath = storage_path('app/public/comprovante.png');

                // Configurações de tamanho, qualidade e zoom
                $width = 800;    // Largura em pixels
                $height = 1600;  // Altura em pixels
                $quality = 85;  // Qualidade máxima
                $zoom = 1.5;     // Zoom de 2x

                // Executar o comando wkhtmltoimage com ajustes
                $command = "xvfb-run wkhtmltoimage --width {$width} --height {$height} --quality {$quality} --zoom {$zoom} {$htmlFilePath} {$pngPath}";
                shell_exec($command);

                // Verificar se o PNG foi gerado
                if (file_exists($pngPath)) {
                    $conteudo = File::get($pngPath);
                    $base64 = 'data:image/png;base64,' . base64_encode($conteudo);
                    $company = $this->contaspagar->company;
                    $telefone = preg_replace('/\D/', '', $this->contaspagar->fornecedor->telefone_celular_1);
                    $numeroCliente = "55" . $telefone;
                    $this->wapiService->enviarMensagemImagem($company->token_api_wtz, $company->instance_id, ["delayMessage" => 1, "phone" => $numeroCliente, "image" => $base64]);

//                    try {
//                        $telefone = preg_replace('/\D/', '', $this->emprestimo->client->telefone_celular_1);
//                        // Enviar o PNG gerado para o endpoint
//                        $response = Http::attach(
//                            'arquivo', // Nome do campo no formulário
//                            fopen($pngPath, 'rb'), // Conteúdo do arquivo
//                            'comprovante.png' // Nome do arquivo enviado
//                        )->post($this->emprestimo->company->whatsapp . '/enviar-pdf', [
//                            'numero' =>  "55" . $telefone,
//                        ]);
//                    } catch (\Exception $e) {
//                    }
                } else {
                }
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar PIX: ' . $e->getMessage());
            throw $e;
        }
    }

}
