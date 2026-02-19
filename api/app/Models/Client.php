<?php

namespace App\Models;

use App\Models\Permgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Client extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    public $table = 'clients';

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
        'tipo_pessoa',
        'nome_completo',
        'razao_social',
        'nome_fantasia',
        'cpf',
        'rg',
        'orgao_emissor_rg',
        'cnpj',
        'data_nascimento',
        'sexo',
        'estado_civil',
        'regime_bens',
        'telefone_celular_1',
        'telefone_celular_2',
        'email',
        'status',
        'status_motivo',
        'observation',
        'limit',
        'renda_mensal',
        'company_id',
        'pix_cliente',
        'nome_usuario_criacao',
        'usuario',
        'password'
    ];

    protected $casts = [
        'renda_mensal' => 'decimal:2',
    ];

    // JWT Implementation
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function address()
    {
        return $this->hasMany(Address::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function emprestimos()
    {
        return $this->belongsTo(Emprestimo::class, 'id', 'client_id');
    }

    public function locations()
    {
        return $this->hasMany(ClientLocation::class);
    }
}
