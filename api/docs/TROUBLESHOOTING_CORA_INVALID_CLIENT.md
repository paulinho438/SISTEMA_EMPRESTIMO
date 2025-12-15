# Troubleshooting: Erro "invalid_client" na API Cora

## Erro

```
"error": "invalid_client"
```

## Possíveis Causas

### 1. Client ID Incorreto

O Client ID `int-3g1VYFU7tflsufR9ZrsUXp` pode estar incorreto ou não corresponder ao certificado.

**Solução:**
- Verifique na documentação/portal da Cora qual é o Client ID correto
- Confirme que o Client ID corresponde ao certificado fornecido
- Certificados e Client IDs são pares específicos e não podem ser misturados

### 2. Certificado não Corresponde ao Client ID

O certificado e o Client ID devem ser um par válido fornecido pela Cora.

**Como verificar:**
- Confirme com a Cora que o certificado corresponde ao Client ID
- Certificados e Client IDs são específicos e não podem ser misturados

**Solução:**
- Verifique na documentação/portal da Cora qual certificado corresponde ao seu Client ID
- Ou qual Client ID corresponde ao seu certificado
- Atualize o `client_id` no banco de dados se necessário

### 3. Ambiente Incorreto (Stage vs Production)

O certificado e Client ID podem ser específicos para um ambiente (stage ou production).

**Como verificar:**
- Verifique a variável de ambiente `CORA_API_URL` no `.env`
- Stage: `https://api.stage.cora.com.br`
- Production: `https://api.cora.com.br`

**Solução:**
- Certifique-se de que está usando a URL correta para o ambiente do seu certificado
- Verifique se o certificado é para stage ou production
- O token URL também muda:
  - Stage: `https://matls-clients.api.stage.cora.com.br/token`
  - Production: `https://matls-clients.api.cora.com.br/token`

### 4. Certificado Expirado ou Inválido

Certificados têm data de validade e podem expirar.

**Como verificar:**
```bash
# No servidor Linux
openssl x509 -in /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem -noout -dates
```

**Solução:**
- Se o certificado estiver expirado, solicite um novo certificado à Cora
- Atualize os arquivos no servidor

### 5. Certificado e Chave Privada não Correspondem

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

## Teste com cURL

Teste diretamente com cURL para validar o certificado e Client ID:

```bash
curl --cert '/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem' \
--key '/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key' \
--header 'Content-Type: application/x-www-form-urlencoded' \
-X POST 'https://matls-clients.api.stage.cora.com.br/token' \
-d 'grant_type=client_credentials&client_id=int-3g1VYFU7tflsufR9ZrsUXp'
```

Se o cURL também retornar "invalid_client", o problema está nas credenciais (certificado ou Client ID).

## Próximos Passos

1. **Verificar os logs do Laravel** para ver mais detalhes:
   ```bash
   tail -f storage/logs/laravel.log | grep -i cora
   ```

2. **Contatar o suporte da Cora** com:
   - Client ID usado: `int-3g1VYFU7tflsufR9ZrsUXp`
   - Ambiente: Stage
   - Erro: `invalid_client`
   - Resultado do teste com cURL

3. **Verificar na documentação/portal da Cora**:
   - Qual Client ID corresponde ao seu certificado
   - Ou qual certificado corresponde ao seu Client ID

## Informações para o Suporte da Cora

Ao contatar o suporte, forneça:

- **Client ID**: `int-3g1VYFU7tflsufR9ZrsUXp`
- **Ambiente**: Stage (`https://matls-clients.api.stage.cora.com.br/token`)
- **Erro**: `invalid_client`
- **Certificados**: Configurados e legíveis
- **Teste cURL**: Resultado do comando acima

