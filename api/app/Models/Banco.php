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
        'bank_type',
        'document',
        'juros',
        'chavepix',
        'company_id',
        'info_recebedor_pix',
        'accountId',
        'client_id',
        'certificate_path',
        'private_key_path',
        'velana_secret_key'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class, 'banco_id', 'id');
    }

    public function depositos()
    {
        return $this->hasMany(Deposito::class, 'banco_id', 'id');
    }

}
