<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Banco extends Model implements Auditable
{
    public $table = 'bancos';

    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'agencia',
        'conta',
        'saldo',
        'wallet',
        'bank_type',
        'document',
        'juros',
        'chavepix',
        'company_id',
        'info_recebedor_pix',
        'accountId',
        'client_id',
        'certificate_path',
        'private_key_path',
        'velana_secret_key',
        'velana_public_key',
        'xgate_email',
        'xgate_password',
        'apix_base_url',
        'apix_api_key',
        'apix_client_id',
        'apix_client_secret',
        'goldpix_api_key',
        'goldpix_base_url',
        'goldpix_webhook_secret',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class, 'banco_id', 'id');
    }

    public function depositos()
    {
        return $this->hasMany(Deposito::class, 'banco_id', 'id');
    }

    /**
     * Tipo efetivo para rotas PIX/API: credenciais XGate (e-mail) indicam integração XGate
     * mesmo se bank_type no banco estiver desatualizado (ex.: bcodex + wallet).
     */
    public function resolvedBankType(): string
    {
        $email = $this->attributes['xgate_email'] ?? null;
        if (is_string($email) && trim($email) !== '') {
            return 'xgate';
        }

        $stored = $this->attributes['bank_type'] ?? null;
        if ($stored !== null && $stored !== '') {
            return (string) $stored;
        }

        if ((int) ($this->attributes['wallet'] ?? 0) === 1) {
            return 'bcodex';
        }

        return 'normal';
    }

}
