<?php

namespace App\Models;

use App\Models\Permgroup;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
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
     * Exclui clientes com empréstimo em andamento na empresa: parcela em aberto OU (sem parcelas e sem linha em quitacao).
     * Usa SQL explícito (whereNotExists) para evitar ambiguidade do Eloquent em whereDoesntHave aninhado.
     */
    public function scopeWhereSemEmprestimoEmAndamentoNaEmpresa($query, $companyId)
    {
        $clientsTable = $query->getModel()->getTable();

        return $query->whereNotExists(function ($sub) use ($companyId, $clientsTable) {
            $sub->select(DB::raw(1))
                ->from('emprestimos')
                ->whereColumn('emprestimos.client_id', $clientsTable.'.id')
                ->where('emprestimos.company_id', '=', $companyId)
                ->where(function ($w) {
                    // Em andamento: parcela em aberto OU (sem parcelas e sem quitação administrativa em quitacao)
                    $w->where(function ($semParcelasOuAberto) {
                        $semParcelasOuAberto->where(function ($semFin) {
                            $semFin->whereNotExists(function ($p) {
                                $p->select(DB::raw(1))
                                    ->from('parcelas')
                                    ->whereColumn('parcelas.emprestimo_id', 'emprestimos.id');
                            })
                                ->whereNotExists(function ($q) {
                                    $q->select(DB::raw(1))
                                        ->from('quitacao')
                                        ->whereColumn('quitacao.emprestimo_id', 'emprestimos.id');
                                });
                        })
                            ->orWhereExists(function ($p) {
                                $p->select(DB::raw(1))
                                    ->from('parcelas')
                                    ->whereColumn('parcelas.emprestimo_id', 'emprestimos.id')
                                    ->where(function ($o) {
                                        $o->whereNull('parcelas.dt_baixa')
                                            ->orWhere('parcelas.dt_baixa', '');
                                    });
                            });
                    });
                });
        });
    }

    /**
     * IDs de clientes com empréstimo em andamento (mesma regra de {@see Client::scopeWhereSemEmprestimoEmAndamentoNaEmpresa}).
     */
    public static function idsDeClientesComEmprestimoEmAndamentoNaEmpresa($companyId)
    {
        return DB::table('emprestimos')
            ->where('emprestimos.company_id', '=', $companyId)
            ->where(function ($w) {
                $w->where(function ($semParcelasOuAberto) {
                    $semParcelasOuAberto->where(function ($semFin) {
                        $semFin->whereNotExists(function ($p) {
                            $p->select(DB::raw(1))
                                ->from('parcelas')
                                ->whereColumn('parcelas.emprestimo_id', 'emprestimos.id');
                        })
                            ->whereNotExists(function ($q) {
                                $q->select(DB::raw(1))
                                    ->from('quitacao')
                                    ->whereColumn('quitacao.emprestimo_id', 'emprestimos.id');
                            });
                    })
                        ->orWhereExists(function ($p) {
                            $p->select(DB::raw(1))
                                ->from('parcelas')
                                ->whereColumn('parcelas.emprestimo_id', 'emprestimos.id')
                                ->where(function ($o) {
                                    $o->whereNull('parcelas.dt_baixa')
                                        ->orWhere('parcelas.dt_baixa', '');
                                });
                        });
                });
            })
            ->distinct()
            ->pluck('client_id');
    }

    /**
     * Remove da coleção clientes cujo CPF coincide com o de outro cadastro na empresa que tenha empréstimo ativo.
     */
    public static function filtrarColecaoRemovendoCpfComEmprestimoAtivoEmOutroCadastro($clients, $companyId)
    {
        if ($clients->isEmpty()) {
            return $clients;
        }

        $idsAtivos = self::idsDeClientesComEmprestimoEmAndamentoNaEmpresa($companyId);
        if ($idsAtivos->isEmpty()) {
            return $clients;
        }

        $cpfBloqueados = self::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $idsAtivos->all())
            ->pluck('cpf')
            ->map(function ($cpf) {
                return preg_replace('/\D/', '', (string) $cpf);
            })
            ->filter()
            ->unique()
            ->all();

        if ($cpfBloqueados === []) {
            return $clients;
        }

        $bloqueado = array_fill_keys($cpfBloqueados, true);

        return $clients->filter(function (Client $c) use ($bloqueado) {
            $n = preg_replace('/\D/', '', (string) ($c->cpf ?? ''));
            if ($n === '') {
                return true;
            }

            return empty($bloqueado[$n]);
        })->values();
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
