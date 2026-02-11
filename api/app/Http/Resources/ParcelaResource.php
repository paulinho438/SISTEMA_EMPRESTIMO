<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Permgroup;

use Carbon\Carbon;

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
        $totalPagoParcela = $this->getTotalPagoParcela();
        $totalPagoEmprestimo = $this->getTotalPagoEmprestimo();
        $totalPendente = $this->getTotalPendente();
        $totalPendenteHoje = $this->getTotalPendenteHoje();
        
        return [
            "id" => $this->id,
            "emprestimo_id" => $this->emprestimo_id,
            "parcela" => $this->parcela,
            "valor" => $this->formatarMoeda($this->valor),
            "saldo" => $this->saldo,
            "lucro_real" => (float) ($this->lucro_real ?? 0),
            "multa" => $this->formatarMoeda(($this->saldo + $totalPagoParcela) - $this->valor),
            "venc" => (new DateTime($this->venc))->format('d/m/Y'),
            "venc_real" => (new DateTime($this->venc_real))->format('d/m/Y'),
            "dt_lancamento" => (new DateTime($this->dt_lancamento))->format('d/m/Y'),
            "dt_baixa" => ($this->dt_baixa != null) ? Carbon::parse($this->dt_baixa, 'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y') : '',
            "dt_ult_cobranca" => $this->dt_ult_cobranca,
            "identificador" => $this->identificador,
            "chave_pix" => ($this->chave_pix != null) ? $this->chave_pix : $this->emprestimo->banco->chavepix,
            "nome_cliente" => $this->emprestimo->client->nome_completo ?? null,
            "cpf" => $this->emprestimo->client->cpf ?? null,
            "telefone_celular_1" => $this->emprestimo->client->telefone_celular_1 ?? null,
            "telefone_celular_2" => $this->emprestimo->client->telefone_celular_2 ?? null,
            "atrasadas" => $this->atrasadas,
            "latitude" => $this->getLatitudeFromAddress(),
            "longitude" => $this->getLongitudeFromAddress(),
            "endereco" => $this->getEnderecoFromAddress(),
            "total_pago_emprestimo" => $this->formatarMoeda($totalPagoEmprestimo),
            "total_pago_parcela" => $this->formatarMoeda($totalPagoParcela),
            "total_pendente" => $this->formatarMoeda($totalPendente),
            "total_pendente_hoje" => $totalPendenteHoje,
            "valor_recebido" => $this->valor_recebido,
            "valor_recebido_pix" => $this->valor_recebido_pix,
            "beneficiario" => $this->emprestimo->banco->info_recebedor_pix,
        ];
    }

    protected function getTotalPagoParcela()
    {
        if ($this->relationLoaded('movimentacao')) {
            return $this->movimentacao->sum('valor');
        }
        return $this->totalPagoParcela();
    }

    protected function getTotalPagoEmprestimo()
    {
        // Sempre usar dados carregados se disponíveis
        if ($this->emprestimo && $this->emprestimo->relationLoaded('parcelas')) {
            $total = 0;
            foreach ($this->emprestimo->parcelas as $p) {
                if ($p->relationLoaded('movimentacao')) {
                    $total += $p->movimentacao->sum('valor');
                } else {
                    // Se movimentação não está carregada, fazer query
                    return $this->totalPagoEmprestimo();
                }
            }
            return $total;
        }
        // Fallback: usar método do modelo (faz query)
        return $this->totalPagoEmprestimo();
    }

    protected function getTotalPendente()
    {
        // Sempre usar dados carregados se disponíveis
        if ($this->emprestimo && $this->emprestimo->relationLoaded('parcelas')) {
            return round((float) $this->emprestimo->parcelas->whereNull('dt_baixa')->sum('saldo'), 2);
        }
        // Fallback: usar método do modelo (faz query)
        return $this->totalPendente();
    }

    protected function getTotalPendenteHoje()
    {
        // Sempre usar dados carregados se disponíveis
        if ($this->emprestimo && $this->emprestimo->relationLoaded('parcelas')) {
            $hoje = now()->toDateString();
            return round((float) $this->emprestimo->parcelas
                ->whereNull('dt_baixa')
                ->filter(function($p) use ($hoje) {
                    if (!$p->venc_real) return false;
                    $vencDate = is_string($p->venc_real) ? $p->venc_real : $p->venc_real->toDateString();
                    return $vencDate === $hoje;
                })
                ->sum('saldo'), 2);
        }
        // Fallback: usar método do modelo (faz query + log)
        return $this->totalPendenteHoje();
    }

    /**
     * Retorna a latitude do endereço.
     *
     * @return string|null
     */
    protected function getLatitudeFromAddress()
    {
        if (isset($this->emprestimo->client->address[0]->latitude)) {
            return $this->emprestimo->client->address[0]->latitude;
        }
        return null;
    }

    /**
     * Retorna a latitude do endereço.
     *
     * @return string|null
     */
    protected function getLongitudeFromAddress()
    {
        if (isset($this->emprestimo->client->address[0]->longitude)) {
            return $this->emprestimo->client->address[0]->longitude;
        }
        return null;
    }

    /**
     * Retorna a latitude do endereço.
     *
     * @return string|null
     */
    protected function getEnderecoFromAddress()
    {
        if (isset($this->emprestimo->client->address[0]->address)) {
            return $this->emprestimo->company->company. ' ' . $this->emprestimo->client->address[0]->neighborhood . ' ' . $this->emprestimo->client->address[0]->address . ' ' . $this->emprestimo->client->address[0]->number. ' ' . $this->emprestimo->client->address[0]->complement;
        }
        return null;
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

