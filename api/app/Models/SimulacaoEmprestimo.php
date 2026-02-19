<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulacaoEmprestimo extends Model
{
    protected $table = 'simulacoes_emprestimo';

    protected $fillable = [
        'valor_solicitado',
        'periodo_amortizacao',
        'modelo_amortizacao',
        'quantidade_parcelas',
        'taxa_juros_mensal',
        'data_assinatura',
        'data_primeira_parcela',
        'simples_nacional',
        'calcular_iof',
        'garantias',
        'iof_adicional',
        'iof_diario',
        'iof_total',
        'valor_contrato',
        'parcela',
        'total_parcelas',
        'cet_mes',
        'cet_ano',
        'cronograma',
        'client_id',
        'banco_id',
        'costcenter_id',
        'user_id',
        'company_id',
        'situacao',
    ];

    protected $casts = [
        'data_assinatura' => 'date',
        'data_primeira_parcela' => 'date',
        'simples_nacional' => 'boolean',
        'calcular_iof' => 'boolean',
        'garantias' => 'array',
        'cronograma' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id', 'id');
    }

    public function costcenter()
    {
        return $this->belongsTo(Costcenter::class, 'costcenter_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
