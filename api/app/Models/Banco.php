<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    public $table = 'bancos';

    protected $fillable = [
        'name',
        'agencia',
        'conta',
        'saldo',
        'wallet',
        'document',
        'juros',
        'chavepix',
        'company_id',
        'info_recebedor_pix',
        'accountId'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
