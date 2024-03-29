<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Emprestimo;

use DateTime;

class EmprestimoResource extends JsonResource
{

    public function porcent($vl1, $vl2)
    {
        return number_format(($vl2 / $vl1) * 100, 1);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id"                => $this->id,
            "dt_lancamento"     => (new DateTime($this->dt_lancamento))->format('d/m/Y'),
            "valor"             => $this->valor,
            "lucro"             => $this->lucro,
            "juros"             => $this->juros,
            "saldoareceber"     => $this->parcelas->sum(function ($parcela) {
                return $parcela->saldo;
            }),
            "porcentagem"       => $this->porcent($this->parcelas->sum(function ($parcela) {return $parcela->saldo;}), $this->parcelas->where('dt_baixa', '<>', null)->sum(function ($parcela) {
                return $parcela->saldo;
            })),
            "saldo_total_parcelas_pagas" => $this->parcelas->where('dt_baixa', '<>', null)->sum(function ($parcela) {
                return $parcela->valor;
            }),
            "costcenter"        => $this->costcenter,
            "banco"             => new BancosResource($this->banco),
            "cliente"           => new ClientResource($this->client),
            "consultor"         => $this->user,
            "parcelas"          => ParcelaResource::collection($this->parcelas),
            "parcelas_pagas"    => $this->parcelas->where('dt_baixa', '<>', null)->values()->all(),


        ];
    }
}
