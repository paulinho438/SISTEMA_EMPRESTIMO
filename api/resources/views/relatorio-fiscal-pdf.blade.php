<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Fiscal - Lucro Presumido</title>
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
        .header h2 {
            margin: 5px 0;
            font-size: 14pt;
            color: #666;
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
        .info-row {
            margin: 5px 0;
        }
        .info-label {
            display: inline-block;
            width: 200px;
            font-weight: bold;
        }
        .info-value {
            display: inline-block;
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
        .currency {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .periodo {
            text-align: center;
            margin: 15px 0;
            font-size: 11pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELATÓRIO FISCAL - LUCRO PRESUMIDO</h1>
        @if($company)
            <h2>{{ $company->company }}</h2>
        @endif
        <div class="periodo">
            Período: {{ \Carbon\Carbon::parse($relatorio['periodo']['inicio'])->format('d/m/Y') }} a 
            {{ \Carbon\Carbon::parse($relatorio['periodo']['fim'])->format('d/m/Y') }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">CONFIGURAÇÃO FISCAL</div>
        <table>
            <tr>
                <td class="info-label">Percentual de Presunção:</td>
                <td>{{ number_format($relatorio['configuracao']['percentual_presuncao'], 2, ',', '.') }}%</td>
            </tr>
            <tr>
                <td class="info-label">Alíquota IRPJ:</td>
                <td>{{ number_format($relatorio['configuracao']['aliquota_irpj'], 2, ',', '.') }}%</td>
            </tr>
            <tr>
                <td class="info-label">Alíquota IRPJ Adicional:</td>
                <td>{{ number_format($relatorio['configuracao']['aliquota_irpj_adicional'], 2, ',', '.') }}%</td>
            </tr>
            <tr>
                <td class="info-label">Alíquota CSLL:</td>
                <td>{{ number_format($relatorio['configuracao']['aliquota_csll'], 2, ',', '.') }}%</td>
            </tr>
            <tr>
                <td class="info-label">Faixa de Isenção IRPJ:</td>
                <td>R$ {{ number_format($relatorio['configuracao']['faixa_isencao_irpj'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">RESUMO FINANCEIRO</div>
        <table>
            <tr>
                <td class="info-label">Receita Bruta:</td>
                <td class="currency">R$ {{ number_format($relatorio['receita_bruta'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Despesas Dedutíveis:</td>
                <td class="currency">R$ {{ number_format($relatorio['despesas_dedutiveis'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">CÁLCULOS TRIBUTÁRIOS</div>
        <table>
            <tr>
                <td class="info-label">Lucro Presumido:</td>
                <td class="currency">R$ {{ number_format($relatorio['lucro_presumido'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">Base Tributável:</td>
                <td class="currency">R$ {{ number_format($relatorio['base_tributavel'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">IMPOSTOS</div>
        <table>
            <tr>
                <td class="info-label">IRPJ Normal (15%):</td>
                <td class="currency">R$ {{ number_format($relatorio['irpj']['normal'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">IRPJ Adicional (10%):</td>
                <td class="currency">R$ {{ number_format($relatorio['irpj']['adicional'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">IRPJ Total:</td>
                <td class="currency">R$ {{ number_format($relatorio['irpj']['total'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="info-label">CSLL (9%):</td>
                <td class="currency">R$ {{ number_format($relatorio['csll'], 2, ',', '.') }}</td>
            </tr>
        </table>
        <div class="totals">
            <div class="total-row">
                <span>TOTAL DE IMPOSTOS:</span>
                <span style="float: right;">R$ {{ number_format($relatorio['total_impostos'], 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if(count($relatorio['movimentacoes']) > 0)
    <div class="section">
        <div class="section-title">MOVIMENTAÇÕES FINANCEIRAS (ENTRADAS)</div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Banco</th>
                    <th class="currency">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio['movimentacoes'] as $movimentacao)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($movimentacao->dt_movimentacao)->format('d/m/Y') }}</td>
                    <td>{{ $movimentacao->descricao ?? '' }}</td>
                    <td>{{ $movimentacao->banco ? ($movimentacao->banco->name ?? '') : '' }}</td>
                    <td class="currency">R$ {{ number_format($movimentacao->valor ?? 0, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(count($relatorio['despesas']) > 0)
    <div class="section">
        <div class="section-title">DESPESAS DEDUTÍVEIS</div>
        <table>
            <thead>
                <tr>
                    <th>Data Pagamento</th>
                    <th>Descrição</th>
                    <th>Fornecedor</th>
                    <th>Tipo Doc.</th>
                    <th class="currency">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatorio['despesas'] as $despesa)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($despesa->dt_baixa)->format('d/m/Y') }}</td>
                    <td>{{ $despesa->descricao ?? '' }}</td>
                    <td>{{ $despesa->fornecedor ? ($despesa->fornecedor->nome_completo ?? '') : '' }}</td>
                    <td>{{ $despesa->tipodoc ?? '' }}</td>
                    <td class="currency">R$ {{ number_format($despesa->valor ?? 0, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Relatório gerado em {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>IMPORTANTE:</strong> Este relatório é baseado em cálculos de lucro presumido e deve ser validado por um contador antes do uso oficial.</p>
    </div>
</body>
</html>

