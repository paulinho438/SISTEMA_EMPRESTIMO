<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Charge extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'gateway_transaction_id',
        'external_transaction_id',
        'order_id',
        'status',
        'amount',
        'valor_servico',
        'taxa_cliente',
        'valor_bruto',
        'taxa_gateway',
        'valor_liquido',
        'pix_code',
        'pix_base64',
        'webhook_token',
        'paid_at',
    ];

    protected $casts = [
        'valor_servico' => 'decimal:2',
        'taxa_cliente' => 'decimal:2',
        'valor_bruto' => 'decimal:2',
        'taxa_gateway' => 'decimal:2',
        'valor_liquido' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
