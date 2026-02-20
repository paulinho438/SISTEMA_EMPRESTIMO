<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoAssinaturaEvento extends Model
{
    protected $table = 'contrato_assinatura_eventos';

    protected $fillable = [
        'contrato_id',
        'ator_tipo',
        'ator_id',
        'evento_tipo',
        'ip',
        'user_agent',
        'device_json',
        'meta_json',
    ];

    protected $casts = [
        'device_json' => 'array',
        'meta_json' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(SimulacaoEmprestimo::class, 'contrato_id');
    }
}

