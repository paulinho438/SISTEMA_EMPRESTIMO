<?php

namespace App\Services;

use App\Models\Contaspagar;
use App\Models\Contasreceber;
use App\Models\Costcenter;
use App\Models\Emprestimo;
use App\Models\Parcela;
use App\Models\SimulacaoEmprestimo;
use App\Models\User;
use Illuminate\Support\Carbon;

class ContratoEfetivacaoService
{
    /**
     * Efetiva contrato de forma idempotente:
     * - cria empréstimo se não existir
     * - gera parcelas/contas a receber se não existirem
     * - cria contas a pagar de liberação se não existir
     * - move contrato para efetivado_aguardando_pagamento (quando aplicável)
     */
    public function efetivarContrato(SimulacaoEmprestimo $simulacao, int $companyId, ?int $userId = null): array
    {
        if (!$simulacao->banco_id) {
            throw new \InvalidArgumentException('Selecione o banco pagador antes de iniciar o contrato.');
        }
        if (!$simulacao->client_id) {
            throw new \InvalidArgumentException('Selecione o cliente antes de iniciar o contrato.');
        }

        $costcenter = $this->resolverCostcenter($simulacao, $companyId);
        if (!$costcenter) {
            throw new \InvalidArgumentException('Nenhum centro de custo encontrado para a empresa.');
        }

        $createdEmprestimo = false;
        $createdContaspagar = false;
        $generatedParcelas = false;
        $situacaoUpdated = false;

        $emprestimo = Emprestimo::where('company_id', $companyId)
            ->where('simulacao_emprestimo_id', $simulacao->id)
            ->first();

        if (!$emprestimo) {
            $valor = (float) $simulacao->valor_solicitado;
            $totalPrazo = (float) $simulacao->total_parcelas;
            $lucro = max(0, $totalPrazo - $valor);
            $userIdEfetivacao = $this->resolverUserIdEfetivacao($simulacao, $companyId, $userId);

            $emprestimo = Emprestimo::create([
                'dt_lancamento' => $simulacao->data_assinatura
                    ? $simulacao->data_assinatura->format('Y-m-d')
                    : Carbon::now()->format('Y-m-d'),
                'valor' => $valor,
                'lucro' => $lucro,
                'juros' => (float) $simulacao->taxa_juros_mensal,
                'costcenter_id' => $costcenter->id,
                'banco_id' => $simulacao->banco_id,
                'client_id' => $simulacao->client_id,
                'user_id' => $userIdEfetivacao,
                'company_id' => $companyId,
                'simulacao_emprestimo_id' => $simulacao->id,
                'liberar_minimo' => 1,
            ]);
            $createdEmprestimo = true;
        }

        $generatedParcelas = $this->garantirParcelasEContasReceber($simulacao, $emprestimo, $companyId);

        $contaspagar = Contaspagar::where('company_id', $companyId)
            ->where('emprestimo_id', $emprestimo->id)
            ->where('tipodoc', 'Empréstimo')
            ->first();

        if (!$contaspagar) {
            Contaspagar::create([
                'banco_id' => $simulacao->banco_id,
                'emprestimo_id' => $emprestimo->id,
                'costcenter_id' => $costcenter->id,
                'status' => 'Aguardando Pagamento',
                'tipodoc' => 'Empréstimo',
                'lanc' => Carbon::now()->format('Y-m-d'),
                'venc' => Carbon::now()->format('Y-m-d'),
                'valor' => (float) $simulacao->valor_solicitado,
                'descricao' => 'Empréstimo (Contrato) Nº ' . $simulacao->id,
                'company_id' => $companyId,
            ]);
            $createdContaspagar = true;
        }

        if (($simulacao->situacao ?? 'em_preenchimento') !== 'pagamento_aprovado'
            && $simulacao->situacao !== 'efetivado_aguardando_pagamento') {
            $simulacao->situacao = 'efetivado_aguardando_pagamento';
            $simulacao->save();
            $situacaoUpdated = true;
        }

        return [
            'emprestimo' => $emprestimo,
            'created_emprestimo' => $createdEmprestimo,
            'generated_parcelas' => $generatedParcelas,
            'created_contaspagar' => $createdContaspagar,
            'situacao_updated' => $situacaoUpdated,
        ];
    }

    private function resolverCostcenter(SimulacaoEmprestimo $simulacao, int $companyId): ?Costcenter
    {
        if (!empty($simulacao->costcenter_id)) {
            $costcenterById = Costcenter::where('company_id', $companyId)
                ->find($simulacao->costcenter_id);
            if ($costcenterById) {
                return $costcenterById;
            }
        }

        return Costcenter::where('company_id', $companyId)->first();
    }

    private function resolverUserIdEfetivacao(SimulacaoEmprestimo $simulacao, int $companyId, ?int $userIdInformado): int
    {
        if (!empty($simulacao->user_id)) {
            return (int) $simulacao->user_id;
        }

        if (!empty($userIdInformado)) {
            return (int) $userIdInformado;
        }

        $userEmpresa = User::where('company_id', $companyId)->orderBy('id')->first();
        if ($userEmpresa) {
            return (int) $userEmpresa->id;
        }

        throw new \InvalidArgumentException('Nenhum usuário encontrado para efetivar o contrato.');
    }

    private function garantirParcelasEContasReceber(SimulacaoEmprestimo $simulacao, Emprestimo $emprestimo, int $companyId): bool
    {
        if ($emprestimo->parcelas()->count() > 0) {
            $emprestimo->load('parcelas.contasreceber');
            foreach ($emprestimo->parcelas as $parcela) {
                if ($parcela->contasreceber) {
                    continue;
                }
                Contasreceber::create([
                    'company_id' => $companyId,
                    'parcela_id' => $parcela->id,
                    'client_id' => $emprestimo->client_id,
                    'banco_id' => $emprestimo->banco_id,
                    'descricao' => 'Parcela N° ' . $parcela->parcela . ' do Emprestimo N° ' . $emprestimo->id,
                    'status' => 'Aguardando Pagamento',
                    'tipodoc' => 'Empréstimo',
                    'lanc' => $parcela->dt_lancamento,
                    'venc' => $parcela->venc_real,
                    'valor' => $parcela->valor,
                ]);
            }
            return false;
        }

        if (empty($simulacao->cronograma) || !is_array($simulacao->cronograma)) {
            throw new \InvalidArgumentException('Não foi possível gerar parcelas para este contrato. Cronograma não encontrado no contrato.');
        }

        $n = (int) ($simulacao->quantidade_parcelas ?: count($simulacao->cronograma));
        $lucroRealPorParcela = $n > 0 ? round(((float) $emprestimo->lucro) / $n, 2) : 0;

        foreach ($simulacao->cronograma as $p) {
            $num = (int) ($p['numero'] ?? 0);
            $valorParcela = (float) ($p['parcela'] ?? 0);
            $venc = $this->normalizarDataVencimento($p['vencimento'] ?? null, (string) $emprestimo->dt_lancamento);

            $parcelaModel = Parcela::create([
                'emprestimo_id' => $emprestimo->id,
                'dt_lancamento' => $emprestimo->dt_lancamento,
                'parcela' => ($num > 0 && $n > 0) ? "{$num}/{$n}" : (string) ($p['parcela'] ?? ''),
                'valor' => $valorParcela,
                'saldo' => $valorParcela,
                'lucro_real' => $lucroRealPorParcela,
                'venc' => $venc,
                'venc_real' => $venc,
                'venc_real_audit' => $venc,
            ]);

            Contasreceber::create([
                'company_id' => $companyId,
                'parcela_id' => $parcelaModel->id,
                'client_id' => $emprestimo->client_id,
                'banco_id' => $emprestimo->banco_id,
                'descricao' => 'Parcela N° ' . $parcelaModel->parcela . ' do Emprestimo N° ' . $emprestimo->id,
                'status' => 'Aguardando Pagamento',
                'tipodoc' => 'Empréstimo',
                'lanc' => $parcelaModel->dt_lancamento,
                'venc' => $parcelaModel->venc_real,
                'valor' => $parcelaModel->valor,
            ]);
        }

        return true;
    }

    private function normalizarDataVencimento($vencimento, string $fallback): string
    {
        if (empty($vencimento)) {
            return $fallback;
        }

        try {
            return Carbon::parse($vencimento)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
