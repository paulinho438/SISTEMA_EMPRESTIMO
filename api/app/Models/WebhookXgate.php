<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookXgate extends Model
{
    protected $table = 'webhook_xgate';

    protected $fillable = [
        'payload',
        'processado',
        'identificador',
        'qt_identificadores',
        'valor',
        'tipo_evento',
        'status'
    ];

    protected $casts = [
        'payload' => 'array',
        'processado' => 'boolean',
        'valor' => 'float',
    ];
}
