<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoAssinaturaDesafio extends Model
{
    protected $table = 'contrato_assinatura_desafios';

    protected $fillable = [
        'contrato_id',
        'tipo',
        'desafio_texto',
        'expires_at',
        'used_at',
        'meta_json',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(SimulacaoEmprestimo::class, 'contrato_id');
    }
}

