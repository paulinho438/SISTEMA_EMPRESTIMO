# Exemplos de cURL para Testar Cobrança Cora

## 🚀 Exemplo Rápido - Endpoint de Teste

```bash
curl -X POST https://api.agecontrole.com.br/api/cobranca/teste-cora \
  -H "Content-Type: application/json" \
  -H "company-id: 1" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "banco_id": 1,
    "valor": 100.00,
    "cliente_id": 1,
    "due_date": "2025-12-31"
  }'
```

## 📋 Parâmetros

- `banco_id` (obrigatório): ID do banco do tipo Cora
- `valor` (obrigatório): Valor da cobrança em reais (ex: 100.00)
- `cliente_id` (obrigatório): ID do cliente existente
- `due_date` (opcional): Data de vencimento no formato YYYY-MM-DD

## 🔍 Como Obter os IDs Necessários

### 1. Obter ID do Banco Cora:
```bash
curl -X GET https://api.agecontrole.com.br/api/bancos \
  -H "company-id: 1" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" | jq '.[] | select(.bank_type == "cora") | {id, name, bank_type, client_id}'
```

### 2. Obter ID do Cliente:
```bash
curl -X GET https://api.agecontrole.com.br/api/clients \
  -H "company-id: 1" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" | jq '.[] | {id, nome_completo, email}'
```

### 3. Obter Token de Autenticação:
```bash
curl -X POST https://api.agecontrole.com.br/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "seu@email.com",
    "password": "sua_senha"
  }' | jq '.access_token'
```

## ✅ Resposta de Sucesso

```json
{
  "success": true,
  "message": "Cobrança Cora criada com sucesso",
  "data": {
    "id": "invoice_id_123",
    "code": "TESTE_1234567890_1234",
    "status": "PENDING",
    "customer": { ... },
    "services": [ ... ],
    "payment_terms": { ... }
  },
  "code": "TESTE_1234567890_1234"
}
```

## ❌ Respostas de Erro Comuns

### Banco não é do tipo Cora:
```json
{
  "success": false,
  "error": "Banco não é do tipo Cora",
  "bank_type": "bcodex"
}
```

### Banco não configurado:
```json
{
  "success": false,
  "error": "Banco Cora não está configurado corretamente",
  "missing": {
    "client_id": true,
    "certificate_path": false,
    "private_key_path": false
  }
}
```

## 🧪 Teste Completo em Uma Linha

```bash
curl -X POST https://api.agecontrole.com.br/api/cobranca/teste-cora \
  -H "Content-Type: application/json" \
  -H "company-id: 1" \
  -H "Authorization: Bearer $(curl -s -X POST https://api.agecontrole.com.br/api/auth/login -H "Content-Type: application/json" -d '{"email":"seu@email.com","password":"sua_senha"}' | jq -r '.access_token')" \
  -d '{"banco_id":1,"valor":100.00,"cliente_id":1}' | jq '.'
```

## 📝 Exemplo com Variáveis

```bash
# Configurar variáveis
TOKEN="seu_token_aqui"
COMPANY_ID="1"
BANCO_ID="1"
CLIENTE_ID="1"
VALOR="100.00"

# Executar teste
curl -X POST "https://api.agecontrole.com.br/api/cobranca/teste-cora" \
  -H "Content-Type: application/json" \
  -H "company-id: ${COMPANY_ID}" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d "{
    \"banco_id\": ${BANCO_ID},
    \"valor\": ${VALOR},
    \"cliente_id\": ${CLIENTE_ID}
  }" | jq '.'
```

## 🔧 Teste Direto na API Cora (Sem Sistema)

```bash
curl -X POST https://api.stage.cora.com.br/v2/invoices/ \
  --cert /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem \
  --key /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key \
  -H "Idempotency-Key: $(uuidgen)" \
  -H "accept: application/json" \
  -H "content-type: application/json" \
  -d '{
    "code": "TESTE_001",
    "customer": {
      "name": "Cliente Teste",
      "email": "teste@email.com",
      "document": {
        "identity": "12345678901",
        "type": "CPF"
      },
      "address": {
        "street": "Rua Teste",
        "number": "123",
        "district": "Centro",
        "city": "São Paulo",
        "state": "SP",
        "complement": "N/A",
        "zip_code": "01000000"
      }
    },
    "services": [
      {
        "name": "Parcela de Empréstimo",
        "amount": 10000
      }
    ],
    "payment_terms": {
      "due_date": "2025-12-31",
      "fine": {
        "Amount": 0
      },
      "discount": {
        "type": "PERCENT",
        "value": 0
      }
    },
    "notification": {
      "name": "Cliente Teste",
      "channels": [
        {
          "channel": "EMAIL",
          "contact": "teste@email.com",
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

## 📚 Documentação Completa

Para mais detalhes, consulte: `api/docs/TESTE_CORA_CURL.md`

