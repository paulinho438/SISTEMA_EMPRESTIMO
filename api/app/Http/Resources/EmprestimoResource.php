<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Emprestimo;

use DateTime;

class EmprestimoResource extends JsonResource
{

    public function porcent($vl1, $vl2)
    {
        if ($vl1 != 0) {
            return number_format(($vl2 / $vl1) * 100, 1);
        } else {
            return 0;
        }
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
            "saldoareceber" => $this->parcelas->where('dt_baixa', null)->sum(function ($parcela) {
                return $parcela->saldo;
            }),
            "saldoatrasado" => $this->parcelas
                ->where('dt_baixa', null)
                ->where('venc_real', now()->toDateString())
                ->sum(function ($parcela) {
                    return $parcela->saldo;
                }),
            "porcentagem"       => $this->porcent($this->parcelas->sum(function ($parcela) {
                return $parcela->saldo;
            }), $this->parcelas->where('dt_baixa', '<>', null)->sum(function ($parcela) {
                return $parcela->saldo;
            })),
            "saldo_total_parcelas_pagas" => $this->parcelas->where('dt_baixa', '<>', null)->sum(function ($parcela) {
                return $parcela->valor;
            }),
            "costcenter"        => $this->costcenter,
            "banco"             => new BancosResource($this->banco),
            "cliente"           => new ClientResource($this->client),
            "consultor"         => $this->user,
            "parcelas_vencidas" => ParcelaResource::collection($this->parcelas->where('dt_baixa', null)
            ->where('venc_real', now()->toDateString())),
            "parcelas"          => ParcelaResource::collection($this->parcelas),
            "quitacao"          => new QuitacaoResource($this->quitacao),
            "pagamentominimo"   => new PagamentoMinimoResource($this->pagamentominimo),
            "parcelas_pagas"    => $this->parcelas->where('dt_baixa', '<>', null)->values()->all(),
            "status"            => $this->getStatus(),
            "telefone_empresa"  => $this->company->numero_contato,


        ];
    }

    // Método para calcular o status das parcelas
    private function getStatus()
    {
        $status = 'Em Dias'; // Padrão
        $qtParcelas = count($this->parcelas);
        $qtPagas = 0;
        $qtAtrasadas = 0;

        foreach ($this->parcelas as $parcela) {
            if ($parcela->atrasadas > 0 && $parcela->saldo > 0) {
                $qtAtrasadas++;
            }
        }

        if ($qtAtrasadas > 0) {
            if ($this->isMaiorQuatro($qtAtrasadas, $qtParcelas)) {
                $status = 'Muito Atrasado';
            } else {
                $status = 'Atrasado';
            }

            if ($qtAtrasadas == $qtParcelas) {
                $status = 'Vencido';
            }
        }

        foreach ($this->parcelas as $parcela) {
            if ($parcela->dt_baixa != null) {
                $qtPagas++;
            }
        }

        if ($qtParcelas == $qtPagas) {
            $status = 'Pago';
        }



        return $status;
    }

    private function isMaiorQuatro($x, $y)
    {
        return $x > 5;
    }
}
