<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoAssinaturaEvidencia extends Model
{
    protected $table = 'contrato_assinatura_evidencias';

    protected $fillable = [
        'contrato_id',
        'tipo',
        'path',
        'sha256',
        'mime',
        'size',
        'captured_at',
        'uploaded_at',
        'meta_json',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function contrato()
    {
        return $this->belongsTo(SimulacaoEmprestimo::class, 'contrato_id');
    }
}

