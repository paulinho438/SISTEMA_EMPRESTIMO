# Testes de Cobrança Cora - Exemplos de cURL

Este documento contém exemplos de cURL para testar a geração de cobranças usando o banco Cora.

## Pré-requisitos

1. Ter um banco cadastrado do tipo `cora` com:
   - `client_id` configurado
   - `certificate_path` configurado
   - `private_key_path` configurado

2. Ter uma parcela/quitacao/pagamento_saldo_pendente existente vinculada a um empréstimo que usa o banco Cora

3. Ter um token de autenticação válido (se necessário)

## Endpoint de Teste Específico para Cora

Este endpoint foi criado especificamente para testar cobranças Cora sem precisar de uma parcela existente:

```bash
curl -X POST https://api.agecontrole.com.br/api/cobranca/teste-cora \
  -H "Content-Type: application/json" \
  -H "company-id: {COMPANY_ID}" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{
    "banco_id": 1,
    "valor": 100.00,
    "cliente_id": 1,
    "due_date": "2025-12-31"
  }'
```

**Parâmetros:**
- `banco_id` (obrigatório): ID do banco do tipo Cora
- `valor` (obrigatório): Valor da cobrança em reais (ex: 100.00)
- `cliente_id` (obrigatório): ID do cliente
- `due_date` (opcional): Data de vencimento no formato YYYY-MM-DD (padrão: 30 dias a partir de hoje)

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Cobrança Cora criada com sucesso",
  "data": {
    "id": "invoice_id_123",
    "code": "TESTE_1234567890_1234",
    "status": "PENDING",
    ...
  },
  "code": "TESTE_1234567890_1234"
}
```

**Resposta de Erro:**
```json
{
  "success": false,
  "error": "Banco não é do tipo Cora",
  "bank_type": "bcodex"
}
```

## Endpoints Disponíveis (Sistema Completo)

### 1. Gerar PIX para Parcela
```bash
curl -X POST https://api.agecontrole.com.br/api/parcela/{ID_PARCELA}/gerarpixpagamentoparcela \
  -H "Content-Type: application/json" \
  -H "company-id: {COMPANY_ID}" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{}'
```

### 2. Gerar PIX para Quitação
```bash
curl -X POST https://api.agecontrole.com.br/api/parcela/{ID_QUITACAO}/gerarpixpagamentoquitacao \
  -H "Content-Type: application/json" \
  -H "company-id: {COMPANY_ID}" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{}'
```

### 3. Gerar PIX para Pagamento Saldo Pendente
```bash
curl -X POST https://api.agecontrole.com.br/api/parcela/{ID_PAGAMENTO_SALDO_PENDENTE}/gerarpixpagamentosaldopendente \
  -H "Content-Type: application/json" \
  -H "company-id: {COMPANY_ID}" \
  -H "Authorization: Bearer {TOKEN}" \
  -d '{}'
```

## Exemplo Completo - Teste Direto da API Cora

Para testar diretamente a API Cora (sem passar pelo sistema):

```bash
curl -X POST https://api.stage.cora.com.br/v2/invoices/ \
  --cert /caminho/completo/certificate.pem \
  --key /caminho/completo/private-key.key \
  -H "Idempotency-Key: $(uuidgen)" \
  -H "accept: application/json" \
  -H "content-type: application/json" \
  -d '{
    "code": "TESTE_001",
    "customer": {
      "name": "Fulano da Silva",
      "email": "fulano@email.com",
      "document": {
        "identity": "12345678901",
        "type": "CPF"
      },
      "address": {
        "street": "Rua Gomes de Carvalho",
        "number": "1629",
        "district": "Vila Olímpia",
        "city": "São Paulo",
        "state": "SP",
        "complement": "N/A",
        "zip_code": "04547006"
      }
    },
    "services": [
      {
        "name": "Serviço de Teste",
        "amount": 10000
      }
    ],
    "payment_terms": {
      "due_date": "2025-12-31",
      "fine": {
        "Amount": 200
      },
      "discount": {
        "type": "PERCENT",
        "value": 1.5
      }
    },
    "notification": {
      "name": "Fulano Ciclano Oliveira",
      "channels": [
        {
          "channel": "EMAIL",
          "contact": "fulano@cora.com.br",
          "rules": [
            "NOTIFY_TWO_DAYS_BEFORE_DUE_DATE",
            "NOTIFY_WHEN_PAID"
          ]
        }
      ]
    },
    "payment_forms": [
      "PIX"
    ]
  }'
```

## Exemplo com Variáveis de Ambiente

Crie um arquivo `teste_cora.sh`:

```bash
#!/bin/bash

# Configurações
API_URL="https://api.stage.cora.com.br/v2/invoices/"
CERT_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem"
KEY_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key"
IDEMPOTENCY_KEY=$(uuidgen)

# Dados da cobrança
CODE="TESTE_$(date +%s)"
VALOR_CENTAVOS=10000  # R$ 100,00 em centavos
DUE_DATE=$(date -d "+30 days" +%Y-%m-%d)

curl -X POST "$API_URL" \
  --cert "$CERT_PATH" \
  --key "$KEY_PATH" \
  -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
  -H "accept: application/json" \
  -H "content-type: application/json" \
  -d "{
    \"code\": \"$CODE\",
    \"customer\": {
      \"name\": \"Cliente Teste\",
      \"email\": \"teste@email.com\",
      \"document\": {
        \"identity\": \"12345678901\",
        \"type\": \"CPF\"
      },
      \"address\": {
        \"street\": \"Rua Teste\",
        \"number\": \"123\",
        \"district\": \"Centro\",
        \"city\": \"São Paulo\",
        \"state\": \"SP\",
        \"complement\": \"N/A\",
        \"zip_code\": \"01000000\"
      }
    },
    \"services\": [
      {
        \"name\": \"Parcela de Empréstimo\",
        \"amount\": $VALOR_CENTAVOS
      }
    ],
    \"payment_terms\": {
      \"due_date\": \"$DUE_DATE\",
      \"fine\": {
        \"Amount\": 0
      },
      \"discount\": {
        \"type\": \"PERCENT\",
        \"value\": 0
      }
    },
    \"notification\": {
      \"name\": \"Cliente Teste\",
      \"channels\": [
        {
          \"channel\": \"EMAIL\",
          \"contact\": \"teste@email.com\",
          \"rules\": [
            \"NOTIFY_TWO_DAYS_BEFORE_DUE_DATE\",
            \"NOTIFY_WHEN_PAID\"
          ]
        }
      ]
    },
    \"payment_forms\": [
      \"PIX\"
    ]
  }"
```

Tornar executável e rodar:
```bash
chmod +x teste_cora.sh
./teste_cora.sh
```

## Teste via Endpoint do Sistema

Para testar através do sistema (requer autenticação):

```bash
# 1. Fazer login para obter o token
curl -X POST https://api.agecontrole.com.br/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "seu@email.com",
    "password": "sua_senha"
  }'

# 2. Usar o token retornado para gerar cobrança
curl -X POST https://api.agecontrole.com.br/api/parcela/{ID_PARCELA}/gerarpixpagamentoparcela \
  -H "Content-Type: application/json" \
  -H "company-id: {COMPANY_ID}" \
  -H "Authorization: Bearer {TOKEN_RETORNADO}" \
  -d '{}'
```

## Resposta Esperada

### Sucesso (API Cora):
```json
{
  "id": "invoice_id_123",
  "code": "TESTE_001",
  "status": "PENDING",
  "customer": { ... },
  "services": [ ... ],
  "payment_terms": { ... },
  "created_at": "2025-12-15T10:00:00Z"
}
```

### Sucesso (Sistema):
```json
{
  "chave_pix": "Cobrança Cora criada: invoice_id_123"
}
```

### Erro:
```json
{
  "message": "Erro ao gerar cobrança",
  "error": "Detalhes do erro..."
}
```

## Troubleshooting

### Erro: "Certificado não encontrado"
- Verifique se os caminhos estão corretos e absolutos
- Verifique se os arquivos existem no servidor
- Verifique as permissões dos arquivos (chmod 600)

### Erro: "Autenticação falhou"
- Verifique se o Client ID está correto
- Verifique se os certificados não estão corrompidos
- Verifique se está usando a URL correta (stage vs production)

### Erro: "Cliente não encontrado"
- Certifique-se de que a parcela/quitacao está vinculada a um empréstimo
- Certifique-se de que o empréstimo tem um cliente associado
- Verifique se o cliente tem endereço cadastrado (ou será usado valores padrão)

