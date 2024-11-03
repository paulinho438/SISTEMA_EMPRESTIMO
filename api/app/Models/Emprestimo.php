<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emprestimo extends Model
{
    public $table = 'emprestimos';

    protected $appends = ['count_late_parcels', 'data_quitacao'];
    protected $fillable = [
        'dt_lancamento',
        'valor',
        'lucro',
        'juros',
        'costcenter_id',
        'banco_id',
        'client_id',
        'user_id',
        'company_id',
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

    public function quitacao()
    {
        return $this->belongsTo(Quitacao::class, 'id', 'emprestimo_id');
    }

    public function pagamentominimo()
    {
        return $this->belongsTo(PagamentoMinimo::class, 'id', 'emprestimo_id');
    }

    public function getCountLateParcelsAttribute()
    {
        return $this->parcelas()->where('atrasadas', '>', 0)->count();
    }

    public function getDataQuitacaoAttribute()
    {
        $ultimaParcela = $this->parcelas()->orderBy('dt_baixa', 'desc')->first();
        return $ultimaParcela ? $ultimaParcela->dt_baixa : null;
    }

}
