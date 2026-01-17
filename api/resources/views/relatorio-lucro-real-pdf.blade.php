<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Lucro Real</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            color: #333;
        }
        .periodo {
            text-align: center;
            margin: 15px 0;
            font-size: 11pt;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            font-size: 11pt;
            border-left: 4px solid #333;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .info-label {
            display: inline-block;
            width: 250px;
            font-weight: bold;
        }
        .currency {
            text-align: right;
        }
        .totals {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        .total-row {
            font-weight: bold;
            font-size: 11pt;
            padding: 5px 0;
            border-top: 1px solid #333;
            margin-top: 5px;
            padding-top: 5px;
        }
        .emprestimo-header {
            background-color: #e8e8e8;
            font-weight: bold;
            padding: 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELATÓRIO DE LUCRO REAL</h1>
        <div class="periodo">
            Período: {{ \Carbon\Carbon::parse($relatorio['periodo']['inicio'])->format('d/m/Y') }} a 
            {{ \Carbon\Carbon::parse($relatorio['periodo']['fim'])->format('d/m/Y') }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">RESUMO FINANCEIRO</div>
        <table>
            <tr>
                <td class="info-label">Receita Bruta Total:</td>
                <td class="currency">R$ {{ number_format($relatorio['resumo']['receita_bruta_total'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Valor Recebido em Parcelas:</td>
                <td class="currency">R$ {{ number_format($relatorio['resumo']['valor_recebido_total'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Outras Receitas:</td>
                <td class="currency">R$ {{ number_format($relatorio['resumo']['outras_receitas'], 2, ',', '.') }}</td>
            </tr>
        </table>
        <div class="totals">
            <div class="total-row">
                <span>LUCRO REAL TOTAL:</span>
                <span style="float: right;">R$ {{ number_format($relatorio['resumo']['lucro_real_total'], 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">ESTATÍSTICAS</div>
        <table>
            <tr>
                <td class="info-label">Total de Parcelas Processadas:</td>
                <td>{{ $relatorio['resumo']['total_parcelas_processadas'] }}</td>
            </tr>
            <tr>
                <td class="info-label">Total de Empréstimos:</td>
                <td>{{ $relatorio['resumo']['total_emprestimos'] }}</td>
            </tr>
        </table>
    </div>

    @if(count($relatorio['detalhamento_emprestimos']) > 0)
    <div class="section">
        <div class="section-title">DETALHAMENTO POR EMPRÉSTIMO</div>
        @foreach($relatorio['detalhamento_emprestimos'] as $emprestimo)
        <table style="margin-bottom: 20px;">
            <tr class="emprestimo-header">
                <td colspan="7">
                    Empréstimo #{{ $emprestimo['emprestimo_id'] }} - {{ $emprestimo['cliente'] }} ({{ $emprestimo['cpf_cliente'] }})
                </td>
            </tr>
            <tr class="emprestimo-header">
                <td colspan="3">Valor Emprestado: R$ {{ number_format($emprestimo['valor_emprestado'], 2, ',', '.') }}</td>
                <td colspan="4">Lucro Total: R$ {{ number_format($emprestimo['lucro_total_emprestimo'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Parcela</th>
                <th>Data Recebimento</th>
                <th>Valor Recebido</th>
                <th>Lucro Real</th>
                <th>Descrição</th>
                <th>Banco</th>
            </tr>
            @foreach($emprestimo['parcelas_recebidas_periodo'] as $parcela)
            <tr>
                <td>{{ $parcela['parcela_numero'] }}</td>
                <td>{{ \Carbon\Carbon::parse($parcela['data_recebimento'])->format('d/m/Y') }}</td>
                <td class="currency">R$ {{ number_format($parcela['valor_recebido'], 2, ',', '.') }}</td>
                <td class="currency">R$ {{ number_format($parcela['lucro_real'], 2, ',', '.') }}</td>
                <td>{{ $parcela['descricao'] }}</td>
                <td>{{ $parcela['banco'] }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td colspan="2">TOTAL</td>
                <td class="currency">R$ {{ number_format($emprestimo['total_valor_recebido'], 2, ',', '.') }}</td>
                <td class="currency">R$ {{ number_format($emprestimo['total_lucro_real_periodo'], 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </table>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>Relatório gerado em {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>NOTA:</strong> Este relatório mostra o lucro real obtido pela empresa, baseado no campo lucro_real das parcelas recebidas.</p>
    </div>
</body>
</html>

