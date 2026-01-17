<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoFiscal extends Model
{
    public $table = 'configuracao_fiscal';

    protected $fillable = [
        'company_id',
        'percentual_presuncao',
        'aliquota_irpj',
        'aliquota_irpj_adicional',
        'aliquota_csll',
        'faixa_isencao_irpj',
    ];

    protected $casts = [
        'percentual_presuncao' => 'decimal:2',
        'aliquota_irpj' => 'decimal:2',
        'aliquota_irpj_adicional' => 'decimal:2',
        'aliquota_csll' => 'decimal:2',
        'faixa_isencao_irpj' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
