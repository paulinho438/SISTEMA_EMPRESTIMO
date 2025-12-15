#!/bin/bash

# Script para testar credenciais Cora diretamente com cURL
# Use este script para validar se o certificado e Client ID estão corretos

# Configurações (ajuste conforme necessário)
CERT_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem"
KEY_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key"
CLIENT_ID="int-3g1VYFU7tflsufR9ZrsUXp"  # Ajuste com o Client ID correto

# Ambiente (stage ou production)
ENVIRONMENT="production"  # ou "stage"

if [ "$ENVIRONMENT" = "stage" ]; then
    TOKEN_URL="https://matls-clients.api.stage.cora.com.br/token"
else
    TOKEN_URL="https://matls-clients.api.cora.com.br/token"
fi

echo "=========================================="
echo "Teste de Credenciais Cora"
echo "=========================================="
echo "Ambiente: $ENVIRONMENT"
echo "Token URL: $TOKEN_URL"
echo "Client ID: $CLIENT_ID"
echo "Certificado: $CERT_PATH"
echo "Chave Privada: $KEY_PATH"
echo ""

# Verificar se os arquivos existem
if [ ! -f "$CERT_PATH" ]; then
    echo "ERRO: Certificado não encontrado: $CERT_PATH"
    exit 1
fi

if [ ! -f "$KEY_PATH" ]; then
    echo "ERRO: Chave privada não encontrada: $KEY_PATH"
    exit 1
fi

# Verificar permissões
if [ ! -r "$CERT_PATH" ]; then
    echo "ERRO: Certificado não é legível: $CERT_PATH"
    exit 1
fi

if [ ! -r "$KEY_PATH" ]; then
    echo "ERRO: Chave privada não é legível: $KEY_PATH"
    exit 1
fi

echo "✓ Arquivos encontrados e legíveis"
echo ""

# Verificar formato do certificado
echo "Verificando formato do certificado..."
CERT_HEADER=$(head -1 "$CERT_PATH")
if [[ "$CERT_HEADER" == *"BEGIN CERTIFICATE"* ]]; then
    echo "✓ Certificado está em formato PEM"
else
    echo "✗ Certificado pode não estar em formato PEM correto"
    echo "  Primeira linha: $CERT_HEADER"
fi

# Verificar formato da chave privada
echo "Verificando formato da chave privada..."
KEY_HEADER=$(head -1 "$KEY_PATH")
if [[ "$KEY_HEADER" == *"BEGIN"*"KEY"* ]]; then
    echo "✓ Chave privada está em formato PEM"
else
    echo "✗ Chave privada pode não estar em formato PEM correto"
    echo "  Primeira linha: $KEY_HEADER"
fi

# Verificar correspondência entre certificado e chave
echo ""
echo "Verificando correspondência entre certificado e chave..."
CERT_MODULUS=$(openssl x509 -noout -modulus -in "$CERT_PATH" 2>/dev/null | openssl md5)
KEY_MODULUS=$(openssl rsa -noout -modulus -in "$KEY_PATH" 2>/dev/null | openssl md5)

if [ "$CERT_MODULUS" = "$KEY_MODULUS" ]; then
    echo "✓ Certificado e chave privada correspondem"
else
    echo "✗ ERRO: Certificado e chave privada NÃO correspondem!"
    echo "  Certificado: $CERT_MODULUS"
    echo "  Chave: $KEY_MODULUS"
    exit 1
fi

# Verificar validade do certificado
echo ""
echo "Verificando validade do certificado..."
openssl x509 -in "$CERT_PATH" -noout -dates 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Certificado válido"
else
    echo "✗ Erro ao verificar certificado"
fi

echo ""
echo "=========================================="
echo "Fazendo requisição para obter token..."
echo "=========================================="

# Fazer requisição
RESPONSE=$(curl -s -w "\n%{http_code}" \
    --cert "$CERT_PATH" \
    --key "$KEY_PATH" \
    --header 'Content-Type: application/x-www-form-urlencoded' \
    -X POST "$TOKEN_URL" \
    -d "grant_type=client_credentials&client_id=$CLIENT_ID")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo ""
echo "Status HTTP: $HTTP_CODE"
echo "Resposta:"
echo "$BODY" | jq . 2>/dev/null || echo "$BODY"

if [ "$HTTP_CODE" = "200" ]; then
    echo ""
    echo "✓ SUCESSO! Token obtido com sucesso"
    TOKEN=$(echo "$BODY" | jq -r '.access_token' 2>/dev/null)
    if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
        echo "Token: ${TOKEN:0:50}..."
    fi
else
    echo ""
    echo "✗ ERRO: Falha ao obter token"
    echo ""
    echo "Possíveis causas:"
    echo "1. Client ID incorreto: $CLIENT_ID"
    echo "2. Certificado não corresponde ao Client ID"
    echo "3. Certificado e Client ID são de ambientes diferentes"
    echo "4. Certificado expirado ou inválido"
    echo ""
    echo "Verifique:"
    echo "- Se o Client ID está correto"
    echo "- Se o certificado corresponde ao Client ID"
    echo "- Se ambos são do mesmo ambiente (stage ou production)"
fi

