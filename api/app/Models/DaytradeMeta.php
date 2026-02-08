<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaytradeMeta extends Model
{
    use HasFactory;

    protected $table = 'daytrade_meta';

    protected $fillable = [
        'company_id',
        'capital_inicial',
        'meta_diaria_pct',
        'dias',
        'modo_lancamento',
        'regra_dia',
        'dia_atual',
        'lancamentos',
    ];

    protected $casts = [
        'capital_inicial' => 'decimal:2',
        'meta_diaria_pct' => 'decimal:2',
        'lancamentos' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
