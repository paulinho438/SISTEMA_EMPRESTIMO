<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Http;

use App\Models\Parcela;

use Efi\Exception\EfiException;
use Efi\EfiPay;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class EnvioManual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'envio:EnvioManual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio Manual das Ocorrencias';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciou Envio');

        $parcelas = Parcela::where('dt_baixa', null)->get();

        $primeiroRegistro = Parcela::where('dt_baixa', null)->orderBy('venc_real')->first();

        $ultimoRegistro = Parcela::where('dt_baixa', null)->orderBy('venc_real', 'desc')->first();

        $caminhoAbsoluto = storage_path('app/public/documentos/8fe73da8-28ab-43ce-9768-6aa2680c39e1.p12');
        $conteudoDoCertificado = file_get_contents($caminhoAbsoluto);
        $options = [
            'client_id' => 'Client_Id_3700b7ff6efd2ef2be6fccbff8252e65b20b283f',
            'client_secret' => 'Client_Secret_8309ea2f1426553371867989e8a4a46a9ed29681',
            'certificate' => $caminhoAbsoluto,
            'sandbox' => false,
            'timeout' => 30,
        ];

        $params = [
            "inicio" => $primeiroRegistro->venc_real."T00:00:00Z",
            "fim" => $ultimoRegistro->venc_real."T23:59:59Z",
            "status" => "CONCLUIDA", // "ATIVA","CONCLUIDA", "REMOVIDA_PELO_USUARIO_RECEBEDOR", "REMOVIDA_PELO_PSP"
        ];

        try {
            $api = new EfiPay($options);
            $response = $api->pixListDueCharges($params);

            // Array para armazenar os valores de "id" de "loc"
            $arrayIdsLoc = [];

            // Loop através do array original
            foreach ($response['cobs'] as $item) {
                // Verifica se a chave "loc" existe e se a chave "id" está presente dentro de "loc"
                if (isset($item['loc']['id'])) {
                    // Adiciona o valor de "id" ao novo array
                    $arrayIdsLoc[] = $item['loc']['id'];
                }
            }

            foreach($parcelas as $item){
                if (in_array($item->identificador, $arrayIdsLoc)) {
                    $editParcela = Parcela::find($item->id);
                    $editParcela->dt_baixa = date('Y-m-d');
                    if ($editParcela->contasreceber) {
                        $editParcela->contasreceber->status = 'Pago';
                        $editParcela->contasreceber->dt_baixa = $request->dt_baixa;
                        $editParcela->contasreceber->forma_recebto = 'PIX';
                        $editParcela->contasreceber->save();
                    }
                    $editParcela->save();
                }
            }


            print_r("<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</pre>");
        } catch (EfiException $e) {
            print_r($e->code . "<br>");
            print_r($e->error . "<br>");
            print_r($e->errorDescription) . "<br>";
        } catch (Exception $e) {
            print_r($e->getMessage());
        }


    }
}
