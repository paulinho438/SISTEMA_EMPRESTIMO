<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locacao extends Model
{
    public $table = 'locacao';

    protected $fillable = [
        'type',
        'data_vencimento',
        'data_pagamento',
        'valor',
        'company_id',
        'chave_pix'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
