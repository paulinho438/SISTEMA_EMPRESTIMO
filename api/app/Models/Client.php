<?php

namespace App\Models;

use App\Models\Permgroup;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
        'nome_completo',
        'cpf',
        'rg',
        'cnpj',
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
        'pix_cliente',
        'nome_usuario_criacao',
        'usuario',
        'password'
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

    /**
     * Todos os empréstimos do cliente (correto: hasMany por client_id).
     * Antes estava belongsTo com chaves invertidas — quebrava whereDoesntHave e a lista "Finalizados e Renovações".
     */
    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class, 'client_id', 'id');
    }

    /**
     * Após eager load só de empréstimos quitados, reduz a relação ao mais recente (maior id) para APIs que expõem um único objeto em "emprestimos".
     */
    public function definirEmprestimoFinalizadoMaisRecenteCarregado(): void
    {
        if (!$this->relationLoaded('emprestimos')) {
            return;
        }

        $rel = $this->getRelation('emprestimos');

        if ($rel instanceof Emprestimo) {
            return;
        }

        if ($rel instanceof EloquentCollection) {
            if ($rel->isEmpty()) {
                $this->setRelation('emprestimos', null);

                return;
            }

            $this->setRelation('emprestimos', $rel->sortByDesc('id')->first());
        }
    }

    public function locations()
    {
        return $this->hasMany(ClientLocation::class);
    }
}
