#!/bin/bash

# Script de teste para geração de cobrança Cora
# Uso: ./teste_cora.sh [BANCO_ID] [CLIENTE_ID] [VALOR] [COMPANY_ID] [TOKEN]

# Configurações
API_URL="${API_URL:-https://api.agecontrole.com.br/api/cobranca/teste-cora}"
BANCO_ID="${1:-1}"
CLIENTE_ID="${2:-1}"
VALOR="${3:-100.00}"
COMPANY_ID="${4:-1}"
TOKEN="${5:-}"

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== Teste de Cobrança Cora ===${NC}"
echo ""
echo "Configurações:"
echo "  API URL: $API_URL"
echo "  Banco ID: $BANCO_ID"
echo "  Cliente ID: $CLIENTE_ID"
echo "  Valor: R$ $VALOR"
echo "  Company ID: $COMPANY_ID"
echo ""

if [ -z "$TOKEN" ]; then
    echo -e "${RED}Erro: Token de autenticação não fornecido${NC}"
    echo "Uso: ./teste_cora.sh [BANCO_ID] [CLIENTE_ID] [VALOR] [COMPANY_ID] [TOKEN]"
    exit 1
fi

# Calcular data de vencimento (30 dias a partir de hoje)
DUE_DATE=$(date -d "+30 days" +%Y-%m-%d 2>/dev/null || date -v+30d +%Y-%m-%d 2>/dev/null)

echo "Gerando cobrança..."
echo ""

# Fazer a requisição
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -H "company-id: $COMPANY_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{
    \"banco_id\": $BANCO_ID,
    \"valor\": $VALOR,
    \"cliente_id\": $CLIENTE_ID,
    \"due_date\": \"$DUE_DATE\"
  }")

# Separar body e status code
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo -e "${YELLOW}Status HTTP: $HTTP_CODE${NC}"
echo ""
echo -e "${YELLOW}Resposta:${NC}"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" -eq 201 ] || [ "$HTTP_CODE" -eq 200 ]; then
    echo -e "${GREEN}✓ Cobrança criada com sucesso!${NC}"
    
    # Extrair informações importantes
    INVOICE_ID=$(echo "$BODY" | jq -r '.data.id // .code // "N/A"' 2>/dev/null)
    CODE=$(echo "$BODY" | jq -r '.code // "N/A"' 2>/dev/null)
    
    echo ""
    echo "Informações da cobrança:"
    echo "  Invoice ID: $INVOICE_ID"
    echo "  Code: $CODE"
else
    echo -e "${RED}✗ Erro ao criar cobrança${NC}"
    ERROR=$(echo "$BODY" | jq -r '.error // .message // "Erro desconhecido"' 2>/dev/null)
    echo "  Erro: $ERROR"
fi

