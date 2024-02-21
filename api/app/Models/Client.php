<?php

namespace App\Models;

use App\Models\Permgroup;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    public $table = 'clients';

    protected $hidden = [
        'password'
    ];

    use SoftDeletes;

    protected $fillable = [
        'nome_completo',
        'cpf',
        'rg',
        'data_nascimento',
        'sexo',
        'telefone_celular_1',
        'telefone_celular_2',
        'email',
        'status',
        'status_motivo',
        'observation',
        'limit',
        'company_id',
        'password'
    ];

    public function address()
    {
        return $this->hasMany(Address::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
