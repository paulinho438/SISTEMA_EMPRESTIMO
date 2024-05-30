<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Permgroup;

use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $hidden = [
        'password'
    ];

    public $fillable = [
        'nome_completo',
        'rg',
        'cpf',
        'data_nascimento',
        'sexo',
        'telefone_celular',
        'email',
        'status',
        'status_motivo',
        'tentativas',
        'password'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function companies(){
        return $this->belongsToMany(Company::class);
    }

    public function groups() {
        return $this->belongsToMany(Permgroup::class);
    }

    public function getCompaniesAsString()
    {
        return $this->companies()->pluck('company')->implode(', ');
    }
}

