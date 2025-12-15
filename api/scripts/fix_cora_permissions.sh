#!/bin/bash

# Script para corrigir permissões dos certificados Cora
# Execute este script no servidor Linux com permissões de root ou sudo

CERT_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem"
KEY_PATH="/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key"

# Verificar se os arquivos existem
if [ ! -f "$CERT_PATH" ]; then
    echo "ERRO: Arquivo de certificado não encontrado: $CERT_PATH"
    exit 1
fi

if [ ! -f "$KEY_PATH" ]; then
    echo "ERRO: Arquivo de chave privada não encontrado: $KEY_PATH"
    exit 1
fi

# Detectar usuário do servidor web (comum: www-data, nginx, apache)
WEB_USER="www-data"
if id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
fi

echo "Configurando permissões para certificados Cora..."
echo "Usuário do servidor web detectado: $WEB_USER"
echo ""

# Definir permissões 600 (apenas leitura/escrita para o dono)
chmod 600 "$CERT_PATH"
chmod 600 "$KEY_PATH"

# Alterar dono para o usuário do servidor web
chown "$WEB_USER:$WEB_USER" "$CERT_PATH"
chown "$WEB_USER:$WEB_USER" "$KEY_PATH"

# Verificar permissões
echo "Permissões configuradas:"
ls -la "$CERT_PATH"
ls -la "$KEY_PATH"

echo ""
echo "Verificando se os arquivos são legíveis pelo usuário $WEB_USER:"

# Testar leitura como usuário do servidor web
if sudo -u "$WEB_USER" test -r "$CERT_PATH"; then
    echo "✓ Certificado é legível"
else
    echo "✗ Certificado NÃO é legível"
fi

if sudo -u "$WEB_USER" test -r "$KEY_PATH"; then
    echo "✓ Chave privada é legível"
else
    echo "✗ Chave privada NÃO é legível"
fi

echo ""
echo "Concluído!"

