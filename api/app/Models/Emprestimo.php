<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Emprestimo extends Model implements Auditable
{
    public $table = 'emprestimos';

    use \OwenIt\Auditing\Auditable;
    protected $appends = ['count_late_parcels', 'data_quitacao', 'total_pago'];
    protected $fillable = [
        'dt_lancamento',
        'valor',
        'valor_deposito',
        'lucro',
        'juros',
        'costcenter_id',
        'banco_id',
        'client_id',
        'user_id',
        'company_id',
        'tipo_origem',
        'emprestimo_origem_id',
        'hash_locacao',
        'mensagem_renovacao',
        'liberar_minimo',
        'protesto',
        'data_protesto',
        'deve_cobrar_hoje',
        'dt_envio_mensagem_renovacao'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function contaspagar()
    {
        return $this->belongsTo(Contaspagar::class, 'id', 'emprestimo_id');
    }

    public function parcelas()
    {
        return $this->hasMany(Parcela::class, 'emprestimo_id', 'id');
    }

    public function extornos()
    {
        return $this->hasMany(ParcelaExtorno::class, 'emprestimo_id', 'id');
    }

    public function costcenter()
    {
        return $this->belongsTo(Costcenter::class, 'costcenter_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'banco_id', 'id');
    }

    public function emprestimoOrigem()
    {
        return $this->belongsTo(self::class, 'emprestimo_origem_id', 'id');
    }

    public function quitacao()
    {
        return $this->belongsTo(Quitacao::class, 'id', 'emprestimo_id');
    }

    public function pagamentominimo()
    {
        return $this->belongsTo(PagamentoMinimo::class, 'id', 'emprestimo_id');
    }

    public function pagamentosaldopendente()
    {
        return $this->belongsTo(PagamentoSaldoPendente::class, 'id', 'emprestimo_id');
    }

    public function getCountLateParcelsAttribute()
    {
        // Se a query já trouxe o valor (ex.: withCount com alias), evita N+1
        if (array_key_exists('count_late_parcels', $this->attributes)) {
            return (int) $this->attributes['count_late_parcels'];
        }
        return $this->parcelas()->where('atrasadas', '>', 0)->count();
    }

    public function getDataQuitacaoAttribute()
    {
        if ($this->relationLoaded('parcelas') && $this->parcelas->isNotEmpty()) {
            $comBaixa = $this->parcelas->filter(function ($p) {
                return $p->dt_baixa !== null && $p->dt_baixa !== '';
            });
            if ($comBaixa->isNotEmpty()) {
                return $comBaixa->sortByDesc(function ($p) {
                    $d = $p->dt_baixa;
                    if ($d instanceof \DateTimeInterface) {
                        return $d->getTimestamp();
                    }

                    return strtotime((string) $d) ?: 0;
                })->first()->dt_baixa;
            }
        }

        $ultimaParcela = $this->parcelas()
            ->whereNotNull('dt_baixa')
            ->where('dt_baixa', '!=', '')
            ->orderBy('dt_baixa', 'desc')
            ->first();
        if ($ultimaParcela) {
            return $ultimaParcela->dt_baixa;
        }

        if ($this->relationLoaded('quitacao') && $this->quitacao && $this->quitacao->dt_baixa) {
            return $this->quitacao->dt_baixa;
        }

        $quit = Quitacao::where('emprestimo_id', $this->id)->first();

        return $quit?->dt_baixa;
    }

    public function getTotalPagoAttribute()
    {
        return $this->parcelas()->with('movimentacao')->get()->sum(function ($parcela) {
            return $parcela->movimentacao->sum('valor');
        });
    }
}
