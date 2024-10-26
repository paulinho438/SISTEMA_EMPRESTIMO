<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelaExtorno extends Model
{
    public $table = 'parcela_extorno';

    public $timestamps = false;

    protected $fillable = [
        'parcela_id',
        'hash_extorno',
        'emprestimo_id',
        'parcela',
        'valor',
        'saldo',
        'venc',
        'venc_real',
        'dt_lancamento',
        'dt_baixa',
        'identificador',
        'chave_pix',
        'dt_ult_cobranca'
    ];

    public function parcela()
    {
        return $this->belongsTo(Parcela::class, 'parcela_id', 'id');
    }





}
