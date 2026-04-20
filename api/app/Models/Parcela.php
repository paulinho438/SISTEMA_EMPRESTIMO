<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Parcela extends Model implements Auditable
{
    public $table = 'parcelas';

    public $timestamps = true;

    use \OwenIt\Auditing\Auditable;

    protected array $auditInclude = [
        'venc_real',
        'dt_baixa',
        'saldo',
        'valor_recebido',
        'valor_recebido_pix',
        'nome_usuario_baixa',
        'nome_usuario_baixa_pix',
        'identificador',
        'chave_pix',
        'venc_real_audit',
    ];

    protected $fillable = [
        'emprestimo_id',
        'parcela',
        'valor',
        'lucro_real',
        'saldo',
        'venc',
        'venc_real',
        'dt_lancamento',
        'dt_baixa',
        'identificador',
        'chave_pix',
        'tentativas',
        'dt_ult_cobranca',
        'valor_recebido_pix',
        'valor_recebido',
        'ult_dt_geracao_pix',
        'nome_usuario_baixa_pix',
        'nome_usuario_baixa',
        'ult_dt_processamento_rotina',
        'venc_real_audit'
    ];

    protected $casts = [
        'venc_real'   => 'date',      // vira Carbon (data sem hora)
        'dt_baixa'    => 'datetime',  // vira Carbon (com hora)
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'atrasadas'   => 'integer',
    ];

    public function emprestimo()
    {
        return $this->belongsTo(Emprestimo::class, 'emprestimo_id', 'id');
    }

    public function contasreceber()
    {
        return $this->hasOne(Contasreceber::class, 'parcela_id', 'id');
    }

    public function movimentacao()
    {
        return $this->hasMany(Movimentacaofinanceira::class, 'parcela_id', 'id');
    }

    // public function totalPago()
    // {
    //     return $this->movimentacao()->sum('valor');
    // }

    public function totalPagoEmprestimo()
    {
        return Movimentacaofinanceira::whereHas('parcela', function ($query) {
            $query->where('emprestimo_id', $this->emprestimo_id);
        })->sum('valor');
    }

    public function totalPagoParcela()
    {
        return Movimentacaofinanceira::where('parcela_id', $this->id)->sum('valor');
    }

    public function totalPendente()
    {
        $totalPendente = Parcela::where('emprestimo_id', $this->emprestimo_id)
            ->where('dt_baixa', null)
            ->sum('saldo');

        // Arredonda o valor para 2 casas decimais e retorna como float
        return round((float) $totalPendente, 2);
    }

    public function countLateParcels()
    {
        // Conta o número de parcelas que tiveram atraso
        $lateParcelsCount = Parcela::where('emprestimo_id', $this->emprestimo_id)
            ->where('atrasadas', '>', 0)
            ->count();

        return $lateParcelsCount;
    }

    public function totalPendenteHoje()
    {
        $hoje = now()->toDateString();

        // Atrasadas ou que vencem hoje (tudo que já deveria ter sido pago até a data atual)
        $totalVencidoOuHoje = (float) Parcela::where('emprestimo_id', $this->emprestimo_id)
            ->whereNull('dt_baixa')
            ->whereNotNull('venc_real')
            ->whereDate('venc_real', '<=', $hoje)
            ->sum('saldo');

        $totalVencidoOuHoje = round($totalVencidoOuHoje, 2);
        if ($totalVencidoOuHoje > 0) {
            return $totalVencidoOuHoje;
        }

        // Empréstimo novo (todas as parcelas com vencimento futuro): exibir saldo da próxima parcela em aberto
        $proxima = Parcela::where('emprestimo_id', $this->emprestimo_id)
            ->whereNull('dt_baixa')
            ->orderBy('parcela')
            ->first();

        return $proxima ? round((float) ($proxima->saldo ?? 0), 2) : 0.0;
    }
}
