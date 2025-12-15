# Troubleshooting Detalhado: Erro 401 na API Cora

## Status Atual

✅ **Certificados estão legíveis** - O problema de permissões foi resolvido
❌ **Erro 401 (Unauthorized)** - Problema de autenticação com a API Cora

## Possíveis Causas do Erro 401

### 1. Certificado não corresponde ao Client ID

O certificado e o Client ID devem ser um par válido fornecido pela Cora.

**Como verificar:**
- Confirme com a Cora que o Client ID `int-3g1VYFU7tflsufR9ZrsUXp` corresponde ao certificado fornecido
- Certificados e Client IDs são específicos e não podem ser misturados

**Solução:**
- Verifique na documentação/portal da Cora qual Client ID corresponde ao seu certificado
- Atualize o `client_id` no banco de dados se necessário

### 2. Certificado Expirado

Certificados têm data de validade e podem expirar.

**Como verificar:**
```bash
# No servidor Linux
openssl x509 -in /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem -noout -dates
```

**Solução:**
- Se o certificado estiver expirado, solicite um novo certificado à Cora
- Atualize os arquivos no servidor

### 3. Certificado e Chave Privada não Correspondem

O certificado e a chave privada devem ser um par válido.

**Como verificar:**
```bash
# No servidor Linux
openssl x509 -noout -modulus -in certificate.pem | openssl md5
openssl rsa -noout -modulus -in private-key.key | openssl md5
```

Os hashes devem ser **idênticos**. Se forem diferentes, os arquivos não correspondem.

**Solução:**
- Verifique se você está usando o par correto de certificado e chave privada
- Solicite novos arquivos à Cora se necessário

### 4. Ambiente Incorreto (Stage vs Production)

O certificado e Client ID podem ser específicos para um ambiente (stage ou production).

**Como verificar:**
- Verifique a variável de ambiente `CORA_API_URL` no `.env`
- Stage: `https://api.stage.cora.com.br`
- Production: `https://api.cora.com.br` (confirmar com documentação)

**Solução:**
- Certifique-se de que está usando a URL correta para o ambiente do seu certificado
- Verifique se o certificado é para stage ou production

### 5. Formato de Header Incorreto

A API Cora pode exigir o Client ID em um header específico.

**Atualmente estamos usando:**
- `X-Client-Id: int-3g1VYFU7tflsufR9ZrsUXp`

**Possíveis alternativas:**
- `Client-Id: int-3g1VYFU7tflsufR9ZrsUXp`
- `Authorization: Bearer int-3g1VYFU7tflsufR9ZrsUXp`
- Ou nenhum header (autenticação apenas via certificado)

**Solução:**
- Consulte a documentação oficial da API Cora
- Teste diferentes formatos de header
- Entre em contato com o suporte da Cora para confirmar o formato correto

### 6. Certificado em Formato Incorreto

O certificado pode precisar estar em um formato específico.

**Como verificar:**
```bash
# Verificar formato do certificado
head -1 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem
# Deve mostrar: -----BEGIN CERTIFICATE-----

# Verificar formato da chave privada
head -1 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key
# Deve mostrar: -----BEGIN PRIVATE KEY----- ou -----BEGIN RSA PRIVATE KEY-----
```

**Solução:**
- Se os formatos estiverem incorretos, converta usando OpenSSL
- Ou solicite os arquivos no formato correto à Cora

## Próximos Passos

1. **Verificar os logs do Laravel** para ver a resposta completa da API:
   ```bash
   tail -f storage/logs/laravel.log | grep -i cora
   ```

2. **Testar com cURL diretamente** para validar o certificado:
   ```bash
   curl -X POST "https://api.stage.cora.com.br/v2/invoices/" \
     --cert /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem \
     --key /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key \
     -H "X-Client-Id: int-3g1VYFU7tflsufR9ZrsUXp" \
     -H "Content-Type: application/json" \
     -H "Idempotency-Key: teste-123" \
     -d '{
       "code": "TESTE_001",
       "customer": {
         "name": "Cliente Teste",
         "email": "teste@example.com",
         "document": {
           "identity": "12345678900",
           "type": "CPF"
         }
       },
       "services": [{
         "name": "Serviço Teste",
         "amount": 10000
       }],
       "payment_terms": {
         "due_date": "2024-12-31"
       }
     }'
   ```

3. **Consultar a documentação oficial da Cora** para confirmar:
   - Formato correto dos headers
   - Formato correto do certificado
   - Se o Client ID deve estar em um header específico

4. **Contatar o suporte da Cora** com:
   - Client ID usado
   - Ambiente (stage/production)
   - Mensagem de erro completa
   - Resultado do teste com cURL

## Informações para o Suporte da Cora

Ao contatar o suporte, forneça:

- **Client ID**: `int-3g1VYFU7tflsufR9ZrsUXp`
- **Ambiente**: Stage (`https://api.stage.cora.com.br`)
- **Erro**: 401 Unauthorized
- **Certificados**: Configurados e legíveis
- **Headers enviados**: 
  - `X-Client-Id: int-3g1VYFU7tflsufR9ZrsUXp`
  - `Idempotency-Key: [gerado]`
  - `Content-Type: application/json`
- **Autenticação**: mTLS com certificado e chave privada

## Verificações Rápidas

Execute estas verificações no servidor:

```bash
# 1. Verificar validade do certificado
openssl x509 -in /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem -noout -dates

# 2. Verificar correspondência entre certificado e chave
openssl x509 -noout -modulus -in /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem | openssl md5
openssl rsa -noout -modulus -in /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key | openssl md5

# 3. Verificar formato dos arquivos
head -1 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem
head -1 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key
```

