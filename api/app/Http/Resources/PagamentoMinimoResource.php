<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

use DateTime;
use Carbon\Carbon;

class PagamentoMinimoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $dtLancamentoHoje = $this->dt_ult_cobranca ? (new DateTime($this->dt_ult_cobranca))->format('Y-m-d') === Carbon::now()->format('Y-m-d') : false;

        // Definindo chave_pix com base na lógica fornecida
        $chave_pix = null;
        if ($dtLancamentoHoje) {
            $chave_pix = $this->chave_pix;
        } elseif ($this->emprestimo->banco->wallet == 1) {
            $chave_pix = '';
        } else {
            $chave_pix = $this->emprestimo->banco->chavepix;
        }

        return [
            "id"                    => $this->id,
            "emprestimo_id"         => $this->emprestimo_id,
            "valor"                 => $this->formatarMoeda($this->valor),
            "valorSemFormatacao"    => $this->valor,
            "dt_baixa"              => ($this->dt_baixa != null) ? (new DateTime($this->dt_baixa))->format('d/m/Y') : '',
            "identificador"         => $this->identificador,
            "chave_pix"             => $chave_pix,
            "ult_dt_geracao_pix" => $this->ult_dt_geracao_pix ? (new DateTime($this->ult_dt_geracao_pix))->format('d/m/Y') : null,
        ];
    }

    /**
     * Formata um valor decimal como uma string formatada no formato de moeda brasileira (R$).
     *
     * @param float $valor O valor decimal a ser formatado.
     * @return string A string formatada no formato de moeda brasileira (R$).
     */
    function formatarMoeda(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

