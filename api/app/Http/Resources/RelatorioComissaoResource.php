<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class RelatorioComissaoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $parcelas = $this->parcelas;
        
        // Calcular quantidade de parcelas atrasadas
        $qtParcelasAtrasadas = 0;
        foreach ($parcelas as $parcela) {
            if ($parcela->atrasadas > 0 && $parcela->saldo > 0) {
                $qtParcelasAtrasadas++;
            }
        }
        
        // Calcular status
        $status = $this->calcularStatus($qtParcelasAtrasadas, count($parcelas));
        
        // Calcular quantidade de parcelas pagas
        $qtParcelasPagas = $parcelas->where('dt_baixa', '!=', null)->count();
        $qtParcelasTotal = count($parcelas);

        return [
            "id" => $this->id,
            "dt_lancamento" => Carbon::parse($this->dt_lancamento)->format('d/m/Y'),
            "valor" => $this->valor,
            "valor_deposito" => $this->valor_deposito ?? 0,
            "lucro" => $this->lucro,
            "juros" => $this->juros,
            "cliente" => [
                "id" => $this->client->id ?? null,
                "nome_completo" => $this->client->nome_completo ?? 'N/A',
                "cpf" => $this->client->cpf ?? null
            ],
            "consultor" => [
                "id" => $this->user->id ?? null,
                "nome_completo" => $this->user->nome_completo ?? 'N/A',
                "email" => $this->user->email ?? null
            ],
            "status" => $status,
            "status_class" => $this->getStatusClass($status),
            "qt_parcelas_atrasadas" => $qtParcelasAtrasadas,
            "qt_parcelas_total" => $qtParcelasTotal,
            "qt_parcelas_pagas" => $qtParcelasPagas,
            "saldo_a_receber" => $parcelas->where('dt_baixa', null)->sum('saldo'),
            "valor_total_pago" => $parcelas->where('dt_baixa', '<>', null)->sum('valor')
        ];
    }

    /**
     * Calcula o status do empréstimo baseado na quantidade de parcelas atrasadas
     */
    private function calcularStatus($qtAtrasadas, $qtParcelas)
    {
        $qtPagas = 0;
        foreach ($this->parcelas as $parcela) {
            if ($parcela->dt_baixa != null) {
                $qtPagas++;
            }
        }

        // Se todas as parcelas estão pagas
        if ($qtParcelas == $qtPagas) {
            return 'Pago';
        }

        // Se houver parcelas atrasadas
        if ($qtAtrasadas > 0) {
            if ($qtAtrasadas >= 10) {
                return 'Vencido';
            } elseif ($qtAtrasadas >= 4) {
                return 'Muito Atrasado';
            } else {
                // 1 a 3 parcelas atrasadas
                return 'Atrasado';
            }
        }

        // Nenhuma parcela atrasada
        return 'Em Dias';
    }

    /**
     * Retorna a classe CSS baseada no status
     */
    private function getStatusClass($status)
    {
        switch ($status) {
            case 'Pago':
                return 'p-button-success';
            case 'Em Dias':
                return 'p-button-success';
            case 'Atrasado':
                return 'p-button-info';
            case 'Muito Atrasado':
                return 'p-button-warning';
            case 'Vencido':
                return 'p-button-danger';
            case 'Protesto':
                return 'p-button-warning';
            case 'Protestado':
                return 'p-button-success bg-roxo';
            default:
                return 'p-button-danger';
        }
    }
}

