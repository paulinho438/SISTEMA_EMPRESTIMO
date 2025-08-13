<?php

namespace App\Models;

use App\Models\User;
use App\Models\Planos;
use App\Models\Locacao;
use App\Models\Emprestimo;
use App\Models\Wallet;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $table = 'companies';

    protected $fillable = [
        'company',
        'juros',
        'caixa',
        'caixa_pix',
        'ativo',
        'email',
        'motivo_inativo',
        'plano_id',
        'login',
        'numero_contato',
        'envio_automatico_renovacao',
        'mensagem_audio',
        'token_api_wtz',
        'instance_id'

    ];

    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function plano()
    {
        return $this->belongsTo(Planos::class);
    }

    public function locacoes()
    {
        return $this->hasMany(Locacao::class);
    }

    public function depositos()
    {
        return $this->hasMany(Deposito::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
