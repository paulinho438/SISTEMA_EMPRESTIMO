<?php

namespace App\Services;

use App\Models\Movimentacaofinanceira;
use App\Models\Parcela;
use Carbon\Carbon;

class RelatorioLucroRealService
{
    /**
     * Calcula o lucro real recebido no período
     * Baseado no campo lucro_real das parcelas que foram pagas/recebidas
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return array
     */
    public function calcularLucroRealRecebido(int $companyId, string $dataInicio, string $dataFim): array
    {
        // Buscar movimentações de entrada com parcela_id (parcelas recebidas)
        // Excluir refinanciamentos e descontos
        $movimentacoes = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->whereNotNull('parcela_id')
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->with(['parcela.contasreceber', 'parcela.emprestimo.client', 'banco'])
            ->orderBy('dt_movimentacao', 'asc')
            ->get();

        $lucroRealTotal = 0;
        $valorRecebidoTotal = 0;
        $detalhamentoEmprestimos = [];
        $detalhamentoMovimentacoes = [];
        $emprestimosMap = [];

        foreach ($movimentacoes as $movimentacao) {
            if (!$movimentacao->parcela || !$movimentacao->parcela->emprestimo) {
                continue;
            }

            // Excluir parcelas dadas de baixa com desconto, refinanciamento ou renovação
            $formaRecebto = optional($movimentacao->parcela->contasreceber)->forma_recebto ?? '';
            if (in_array($formaRecebto, ['BAIXA COM DESCONTO', 'REFINANCIAMENTO', 'RENOVACAO'])) {
                continue;
            }

            $emprestimo = $movimentacao->parcela->emprestimo;
            $emprestimoId = $emprestimo->id;
            $lucroRealParcela = (float) ($movimentacao->parcela->lucro_real ?? 0);
            $valorRecebido = (float) $movimentacao->valor;

            // Se lucro_real não estiver preenchido, calcular como fallback
            if ($lucroRealParcela == 0) {
                $numParcelas = $emprestimo->parcelas ? $emprestimo->parcelas->count() : 0;
                if ($numParcelas > 0 && $emprestimo->lucro > 0) {
                    $lucroRealParcela = round($emprestimo->lucro / $numParcelas, 2);
                }
            }

            $lucroRealTotal += $lucroRealParcela;
            $valorRecebidoTotal += $valorRecebido;

            // Agrupar por empréstimo
            if (!isset($emprestimosMap[$emprestimoId])) {
                $emprestimosMap[$emprestimoId] = [
                    'emprestimo_id' => $emprestimoId,
                    'cliente' => $emprestimo->client ? $emprestimo->client->nome_completo : 'N/A',
                    'cpf_cliente' => $emprestimo->client ? $emprestimo->client->cpf : 'N/A',
                    'valor_emprestado' => (float) $emprestimo->valor,
                    'lucro_total_emprestimo' => (float) $emprestimo->lucro,
                    'num_parcelas_total' => $emprestimo->parcelas ? $emprestimo->parcelas->count() : 0,
                    'parcelas_recebidas_periodo' => [],
                    'total_valor_recebido' => 0,
                    'total_lucro_real_periodo' => 0,
                ];
            }

            // Adicionar detalhe da parcela recebida
            $emprestimosMap[$emprestimoId]['parcelas_recebidas_periodo'][] = [
                'movimentacao_id' => $movimentacao->id,
                'parcela_id' => $movimentacao->parcela_id,
                'parcela_numero' => $movimentacao->parcela->parcela ?? 'N/A',
                'data_recebimento' => $movimentacao->dt_movimentacao,
                'valor_recebido' => $valorRecebido,
                'lucro_real' => $lucroRealParcela,
                'descricao' => $movimentacao->descricao,
                'banco' => $movimentacao->banco ? $movimentacao->banco->name : 'N/A',
            ];

            $emprestimosMap[$emprestimoId]['total_valor_recebido'] += $valorRecebido;
            $emprestimosMap[$emprestimoId]['total_lucro_real_periodo'] += $lucroRealParcela;

            // Detalhamento de movimentações
            $detalhamentoMovimentacoes[] = [
                'id' => $movimentacao->id,
                'data' => $movimentacao->dt_movimentacao,
                'descricao' => $movimentacao->descricao,
                'valor_recebido' => $valorRecebido,
                'lucro_real' => $lucroRealParcela,
                'parcela' => $movimentacao->parcela->parcela ?? 'N/A',
                'cliente' => $emprestimo->client ? $emprestimo->client->nome_completo : 'N/A',
                'banco' => $movimentacao->banco ? $movimentacao->banco->name : 'N/A',
            ];
        }

        // Buscar outras receitas (sem parcela_id)
        $movimentacoesSemParcela = Movimentacaofinanceira::where('company_id', $companyId)
            ->where('tipomov', 'E')
            ->whereBetween('dt_movimentacao', [$dataInicio, $dataFim])
            ->whereNull('parcela_id')
            ->where(function ($query) {
                $query->where('descricao', 'not like', '%desconto%')
                      ->where('descricao', 'not like', '%Refinanciamento%');
            })
            ->with(['banco'])
            ->orderBy('dt_movimentacao', 'asc')
            ->get();

        $outrasReceitas = 0;
        $detalhamentoOutrasReceitas = [];
        foreach ($movimentacoesSemParcela as $mov) {
            $valor = (float) $mov->valor;
            $outrasReceitas += $valor;
            $detalhamentoOutrasReceitas[] = [
                'id' => $mov->id,
                'data' => $mov->dt_movimentacao,
                'descricao' => $mov->descricao,
                'valor' => $valor,
                'banco' => $mov->banco ? $mov->banco->name : 'N/A',
            ];
        }

        $detalhamentoReceitaBruta = [];
        foreach ($detalhamentoMovimentacoes as $mov) {
            $detalhamentoReceitaBruta[] = [
                'origem' => 'PARCELA',
                'id' => $mov['id'],
                'data' => $mov['data'],
                'descricao' => $mov['descricao'],
                'valor' => $mov['valor_recebido'],
                'banco' => $mov['banco'],
            ];
        }
        foreach ($detalhamentoOutrasReceitas as $mov) {
            $detalhamentoReceitaBruta[] = [
                'origem' => 'OUTRA_RECEITA',
                'id' => $mov['id'],
                'data' => $mov['data'],
                'descricao' => $mov['descricao'],
                'valor' => $mov['valor'],
                'banco' => $mov['banco'],
            ];
        }

        return [
            'lucro_real_total' => round($lucroRealTotal, 2),
            'valor_recebido_total' => round($valorRecebidoTotal, 2),
            'outras_receitas' => round($outrasReceitas, 2),
            'receita_bruta_total' => round($valorRecebidoTotal + $outrasReceitas, 2),
            'detalhamento_emprestimos' => array_values($emprestimosMap),
            'detalhamento_movimentacoes' => $detalhamentoMovimentacoes,
            'detalhamento_outras_receitas' => $detalhamentoOutrasReceitas,
            'detalhamento_receita_bruta' => $detalhamentoReceitaBruta,
            'total_parcelas_processadas' => count($movimentacoes),
            'total_emprestimos' => count($emprestimosMap),
        ];
    }

    /**
     * Gera relatório completo de lucro real
     *
     * @param int $companyId
     * @param string $dataInicio
     * @param string $dataFim
     * @return array
     */
    public function gerarRelatorioLucroReal(int $companyId, string $dataInicio, string $dataFim): array
    {
        $calculo = $this->calcularLucroRealRecebido($companyId, $dataInicio, $dataFim);

        return [
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
            ],
            'resumo' => [
                'lucro_real_total' => $calculo['lucro_real_total'],
                'valor_recebido_total' => $calculo['valor_recebido_total'],
                'outras_receitas' => $calculo['outras_receitas'],
                'receita_bruta_total' => $calculo['receita_bruta_total'],
                'total_parcelas_processadas' => $calculo['total_parcelas_processadas'],
                'total_emprestimos' => $calculo['total_emprestimos'],
            ],
            'detalhamento_emprestimos' => $calculo['detalhamento_emprestimos'],
            'detalhamento_movimentacoes' => $calculo['detalhamento_movimentacoes'],
            'detalhamento_outras_receitas' => $calculo['detalhamento_outras_receitas'],
            'detalhamento_receita_bruta' => $calculo['detalhamento_receita_bruta'],
        ];
    }
}

