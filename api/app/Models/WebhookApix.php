<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookApix extends Model
{
    protected $table = 'webhook_apix';

    protected $fillable = [
        'payload',
        'raw_body',
        'headers',
        'ip',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
    ];
}
