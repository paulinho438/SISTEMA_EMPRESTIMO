<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <title>Registro de Assinatura Eletrônica</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 8px 0; }
        h2 { font-size: 13px; margin: 14px 0 6px 0; }
        .muted { color: #555; }
        .box { border: 1px solid #222; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 6px; vertical-align: top; }
        .k { width: 40%; font-weight: bold; }
        .hr { height: 1px; background: #222; margin: 10px 0; }
        .small { font-size: 10px; }
        .mono { font-family: DejaVu Sans Mono, monospace; font-size: 10px; word-break: break-all; }
    </style>
</head>
<body>
    <h1>REGISTRO DE ASSINATURA ELETRÔNICA</h1>
    <div class="muted small">Este registro integra o documento e comprova a manifestação de vontade, integridade e rastreabilidade do processo.</div>

    <div class="hr"></div>

    <div class="box">
        <h2>Identificação do contrato</h2>
        <table>
            <tr>
                <td class="k">ID do contrato</td>
                <td>{{ $contrato->id }}</td>
            </tr>
            <tr>
                <td class="k">Versão</td>
                <td>{{ $contrato->assinatura_versao }}</td>
            </tr>
            <tr>
                <td class="k">Data e hora (com fuso)</td>
                <td>{{ $registro['data_hora'] ?? '' }}</td>
            </tr>
        </table>

        <h2>Assinante</h2>
        <table>
            <tr>
                <td class="k">Nome</td>
                <td>{{ $cliente->nome_completo ?? $cliente->razao_social ?? '' }}</td>
            </tr>
            <tr>
                <td class="k">CPF</td>
                <td>{{ $cliente->cpf ?? '' }}</td>
            </tr>
        </table>

        <h2>Autenticação e ambiente</h2>
        <table>
            <tr>
                <td class="k">Método</td>
                <td>{{ $registro['metodo'] ?? 'WhatsApp (OTP)' }}</td>
            </tr>
            <tr>
                <td class="k">IP</td>
                <td>{{ $registro['ip'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="k">User-Agent</td>
                <td class="small">{{ $registro['user_agent'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="k">Dispositivo (resumo)</td>
                <td class="small">{{ $registro['device_resumo'] ?? '' }}</td>
            </tr>
        </table>

        <h2>Integridade do documento</h2>
        <table>
            <tr>
                <td class="k">Hash do PDF original (SHA-256)</td>
                <td class="mono">{{ $registro['hash_original'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="k">Hash do PDF final (SHA-256)</td>
                <td class="mono">{{ $registro['hash_final'] ?? '' }}</td>
            </tr>
        </table>

        <h2>Evidências coletadas</h2>
        <div class="small muted">IDs internos das evidências vinculadas a este contrato.</div>
        <div class="mono">{{ isset($registro['evidencias']) ? implode(', ', $registro['evidencias']) : '' }}</div>
    </div>

    <div class="hr"></div>
    <div class="small muted">
        Evento: SIGN_FINALIZED | Gerado automaticamente pelo sistema.
    </div>
</body>
</html>

