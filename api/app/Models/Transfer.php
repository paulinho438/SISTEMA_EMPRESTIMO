<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Transfer.php
class Transfer extends Model
{
    protected $fillable = [
        'wallet_id',
        'client_identifier','webhook_token',
        'amount','discount_fee_of_receiver',
        'pix_type','pix_key',
        'owner_ip','owner_name','owner_document_type','owner_document_number',
        'callback_url',
        'withdraw_id','withdraw_amount','withdraw_fee_amount','withdraw_currency',
        'withdraw_status','withdraw_created_at','withdraw_updated_at',
        'payout_account_id','payout_account_status','payout_pix','payout_pix_type',
        'payout_created_at','payout_updated_at','payout_deleted_at',
        'status','raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_fee_of_receiver' => 'boolean',
        'withdraw_amount' => 'decimal:2',
        'withdraw_fee_amount' => 'decimal:2',
        'withdraw_created_at' => 'datetime',
        'withdraw_updated_at' => 'datetime',
        'payout_created_at' => 'datetime',
        'payout_updated_at' => 'datetime',
        'payout_deleted_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
