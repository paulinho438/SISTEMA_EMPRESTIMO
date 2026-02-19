<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimulacaoEmprestimoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'inputs' => 'required|array',
            'inputs.valor_solicitado' => 'required|numeric|min:0.01',
            'inputs.taxa_juros_mensal' => 'required|numeric|min:0',
            'inputs.quantidade_parcelas' => 'required|integer|min:1|max:999',
            'inputs.periodo_amortizacao' => 'required|string',
            'inputs.data_assinatura' => 'required|date',
            'inputs.data_primeira_parcela' => 'required|date',
            'iof' => 'required|array',
            'iof.total' => 'required|numeric|min:0',
            'iof.adicional' => 'required|numeric|min:0',
            'iof.diario' => 'required|numeric|min:0',
            'valor_contrato' => 'required|numeric|min:0',
            'parcela' => 'required|numeric|min:0',
            'totais' => 'required|array',
            'totais.total_parcelas' => 'required|numeric|min:0',
            'cronograma' => 'required|array',
            'cronograma.*.numero' => 'required',
            'cronograma.*.parcela' => 'required',
            'cronograma.*.vencimento' => 'required',
            'cronograma.*.juros' => 'required',
            'cronograma.*.amortizacao' => 'required',
            'cronograma.*.saldo_devedor' => 'required',
        ];
    }
}
