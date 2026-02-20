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
        'inadimplencia',
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
        'assinatura_status',
        'assinatura_versao',
        'aceite_at',
        'finalizado_at',
        'pdf_original_path',
        'pdf_original_sha256',
        'pdf_final_path',
        'pdf_final_sha256',
    ];

    protected $casts = [
        'data_assinatura' => 'date',
        'data_primeira_parcela' => 'date',
        'simples_nacional' => 'boolean',
        'calcular_iof' => 'boolean',
        'garantias' => 'array',
        'inadimplencia' => 'array',
        'cronograma' => 'array',
        'aceite_at' => 'datetime',
        'finalizado_at' => 'datetime',
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

    public function assinaturaEventos()
    {
        return $this->hasMany(ContratoAssinaturaEvento::class, 'contrato_id');
    }

    public function assinaturaEvidencias()
    {
        return $this->hasMany(ContratoAssinaturaEvidencia::class, 'contrato_id');
    }

    public function assinaturaOtps()
    {
        return $this->hasMany(ContratoAssinaturaOtp::class, 'contrato_id');
    }

    public function assinaturaDesafios()
    {
        return $this->hasMany(ContratoAssinaturaDesafio::class, 'contrato_id');
    }
}
