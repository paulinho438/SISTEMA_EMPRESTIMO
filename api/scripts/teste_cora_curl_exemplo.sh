#!/bin/bash

# ============================================
# EXEMPLOS DE CURL PARA TESTAR COBRANÇA CORA
# ============================================

# Configurações - AJUSTE ESTES VALORES
API_BASE_URL="https://api.agecontrole.com.br/api"
COMPANY_ID="1"
TOKEN="SEU_TOKEN_AQUI"
BANCO_ID="1"  # ID do banco do tipo Cora
CLIENTE_ID="1"  # ID do cliente
VALOR="100.00"  # Valor em reais

# ============================================
# EXEMPLO 1: Teste via endpoint específico
# ============================================
echo "=== Exemplo 1: Teste via endpoint específico ==="
curl -X POST "${API_BASE_URL}/cobranca/teste-cora" \
  -H "Content-Type: application/json" \
  -H "company-id: ${COMPANY_ID}" \
  -H "Authorization: Bearer ${TOKEN}" \
  -d "{
    \"banco_id\": ${BANCO_ID},
    \"valor\": ${VALOR},
    \"cliente_id\": ${CLIENTE_ID},
    \"due_date\": \"2025-12-31\"
  }" | jq '.'

echo ""
echo ""

# ============================================
# EXEMPLO 2: Teste direto na API Cora (sem passar pelo sistema)
# ============================================
echo "=== Exemplo 2: Teste direto na API Cora ==="
echo "NOTA: Ajuste os caminhos dos certificados abaixo"

CERT_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem"
KEY_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key"
CORA_API_URL="https://api.stage.cora.com.br/v2/invoices/"
IDEMPOTENCY_KEY=$(uuidgen 2>/dev/null || cat /proc/sys/kernel/random/uuid 2>/dev/null || openssl rand -hex 16)

curl -X POST "${CORA_API_URL}" \
  --cert "${CERT_PATH}" \
  --key "${KEY_PATH}" \
  -H "Idempotency-Key: ${IDEMPOTENCY_KEY}" \
  -H "accept: application/json" \
  -H "content-type: application/json" \
  -d '{
    "code": "TESTE_'$(date +%s)'",
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
      "due_date": "'$(date -d "+30 days" +%Y-%m-%d 2>/dev/null || date -v+30d +%Y-%m-%d 2>/dev/null)'",
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
  }' | jq '.'

echo ""
echo ""

# ============================================
# EXEMPLO 3: Teste via parcela existente
# ============================================
echo "=== Exemplo 3: Teste via parcela existente ==="
echo "NOTA: Substitua {ID_PARCELA} pelo ID real de uma parcela vinculada a um banco Cora"

# curl -X POST "${API_BASE_URL}/parcela/{ID_PARCELA}/gerarpixpagamentoparcela" \
#   -H "Content-Type: application/json" \
#   -H "company-id: ${COMPANY_ID}" \
#   -H "Authorization: Bearer ${TOKEN}" \
#   -d '{}' | jq '.'

echo "Comando comentado - descomente e ajuste o ID_PARCELA"

