<?php

namespace App\Models;
use App\Models\User;
use App\Models\Planos;
use App\Models\Locacao;
use App\Models\Emprestimo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public $table = 'companies';

    protected $fillable = [
        'company',
        'razao_social',
        'cnpj',
        'endereco',
        'cidade',
        'estado',
        'cep',
        'representante_nome',
        'representante_cpf',
        'representante_rg',
        'representante_orgao_emissor',
        'representante_cargo',
        'banco_nome',
        'banco_agencia',
        'banco_conta',
        'banco_pix',
        'juros',
        'caixa',
        'caixa_pix',
        'ativo',
        'email',
        'motivo_inativo',
        'plano_id',
        'login',
        'numero_contato',
        'envio_automatico_renovacao',
        'mensagem_audio',
        'token_api_wtz',
        'instance_id',
        'whatsapp_cobranca',
        'whatsapp_cloud_phone_number_id',
        'whatsapp_cloud_token',
    ];

    use HasFactory;

    public function users() {
        return $this->belongsToMany(User::class);
    }

    public function plano()
    {
        return $this->belongsTo(Planos::class);
    }

    public function locacoes()
    {
        return $this->hasMany(Locacao::class);
    }

    public function depositos()
    {
        return $this->hasMany(Deposito::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class);
    }
}
