<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

use DateTime;

class ParcelaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id"                    => $this->id,
            "emprestimo_id"         => $this->emprestimo_id,
            "parcela"               => $this->parcela,
            "valor"                 => $this->valor,
            "saldo"                 => $this->saldo,
            "venc"                  => (new DateTime($this->venc))->format('d/m/Y'),
            "venc_real"             => (new DateTime($this->venc_real))->format('d/m/Y'),
            "dt_lancamento"         => (new DateTime($this->dt_lancamento))->format('d/m/Y'),
            "dt_baixa"              => ($this->dt_baixa != null) ? (new DateTime($this->dt_baixa))->format('d/m/Y') : '',
            "identificador"         => $this->identificador,
            "chave_pix"             => $this->chave_pix,
        ];
    }
}

