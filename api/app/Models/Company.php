<?php

namespace App\Models;
use App\Models\User;
use App\Models\Planos;
use App\Models\Locacao;

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
        'login'

    ];

    use HasFactory;

    public function users() {
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
}
