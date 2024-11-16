<?php

namespace App\Jobs;

use App\Models\Emprestimo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\BcodexService;

class ProcessarPixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emprestimo;
    protected $bcodexService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Emprestimo $emprestimo, BcodexService $bcodexService)
    {
        $this->emprestimo = $emprestimo;
        $this->bcodexService = $bcodexService;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        foreach($this->emprestimo->parcelas as $parcela){
            $response = $this->bcodexService->criarCobranca($parcela['valor'], $this->emprestimo->banco->document);

            if ($response->successful()) {
                $parcela['identificador'] = $response->json()['txid'];
                $parcela['chave_pix'] = $response->json()['pixCopiaECola'];
            }

            $parcela->save();
        }

    }
}
