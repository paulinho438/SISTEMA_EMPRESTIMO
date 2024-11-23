<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoMinimo extends Model
{
    public $table = 'pagamento_minimo';

    public $timestamps = false;

    protected $fillable = [
        'emprestimo_id',
        'valor',
        'dt_baixa',
        'identificador',
        'chave_pix',
    ];

    public function emprestimo()
    {
        return $this->belongsTo(Emprestimo::class, 'emprestimo_id', 'id');

    }

}
