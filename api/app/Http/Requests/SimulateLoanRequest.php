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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Normalizar período de amortização removendo acentos e convertendo para minúsculas
        if ($this->has('periodo_amortizacao')) {
            $periodo = $this->input('periodo_amortizacao');
            $periodoNormalizado = mb_strtolower($this->removeAccents($periodo));
            $this->merge([
                'periodo_amortizacao' => $periodoNormalizado,
            ]);
        }

        // Normalizar modelo de amortização
        if ($this->has('modelo_amortizacao')) {
            $modelo = $this->input('modelo_amortizacao');
            $modeloNormalizado = mb_strtolower($this->removeAccents($modelo));
            $this->merge([
                'modelo_amortizacao' => $modeloNormalizado,
            ]);
        }
        
        // Garantir que cliente_simples_nacional seja sempre boolean
        if ($this->has('cliente_simples_nacional')) {
            $valor = $this->input('cliente_simples_nacional');
            $this->merge([
                'cliente_simples_nacional' => filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        } else {
            // Se não veio no request, definir como false explicitamente
            $this->merge([
                'cliente_simples_nacional' => false,
            ]);
        }
    }

    /**
     * Remove acentos de uma string
     *
     * @param string $string
     * @return string
     */
    private function removeAccents($string)
    {
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $string = preg_replace('/\p{Mn}/u', '', \Normalizer::normalize($string, \Normalizer::FORM_D));
        return $string;
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
            'cliente_simples_nacional' => ['sometimes', 'boolean'],
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
            'periodo_amortizacao.required' => 'O período de amortização é obrigatório.',
            'periodo_amortizacao.in' => 'O período de amortização selecionado é inválido.',
            'modelo_amortizacao.required' => 'O modelo de amortização é obrigatório.',
            'modelo_amortizacao.in' => 'O modelo de amortização selecionado é inválido.',
        ];
    }
}
