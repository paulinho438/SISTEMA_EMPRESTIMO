<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulateLoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'valor_solicitado' => ['required', 'numeric', 'min:0.01'],
            'quantidade_parcelas' => ['required', 'integer', 'min:1', 'max:999'],
            'taxa_juros_mensal' => ['required', 'numeric', 'min:0.0001'],
            'modelo_amortizacao' => ['required', 'string', 'in:price'],
            'periodo_amortizacao' => ['required', 'string', 'in:diario'],
            'data_assinatura' => ['required', 'date'],
            'data_primeira_parcela' => ['required', 'date', 'after_or_equal:data_assinatura'],
            'calcular_iof' => ['sometimes', 'boolean'],
            'tipo_operacao' => ['sometimes', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'valor_solicitado.required' => 'O valor solicitado é obrigatório.',
            'valor_solicitado.min' => 'O valor solicitado deve ser no mínimo R$ 0,01.',
            'quantidade_parcelas.required' => 'A quantidade de parcelas é obrigatória.',
            'quantidade_parcelas.min' => 'A quantidade de parcelas deve ser no mínimo 1.',
            'quantidade_parcelas.max' => 'A quantidade de parcelas deve ser no máximo 999.',
            'taxa_juros_mensal.required' => 'A taxa de juros mensal é obrigatória.',
            'taxa_juros_mensal.min' => 'A taxa de juros mensal deve ser no mínimo 0,0001%.',
            'data_assinatura.required' => 'A data de assinatura é obrigatória.',
            'data_assinatura.date' => 'A data de assinatura deve ser uma data válida.',
            'data_primeira_parcela.required' => 'A data da primeira parcela é obrigatória.',
            'data_primeira_parcela.date' => 'A data da primeira parcela deve ser uma data válida.',
            'data_primeira_parcela.after_or_equal' => 'A data da primeira parcela deve ser igual ou posterior à data de assinatura.',
        ];
    }
}
