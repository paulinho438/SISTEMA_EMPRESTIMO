<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoAssinaturaOtp extends Model
{
    protected $table = 'contrato_assinatura_otps';

    protected $fillable = [
        'contrato_id',
        'canal',
        'code_hash',
        'expires_at',
        'attempts',
        'verified_at',
        'last_sent_at',
        'meta_json',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(SimulacaoEmprestimo::class, 'contrato_id');
    }
}

