# Troubleshooting: Erro 401 Unauthorized na API Cora

## Possíveis Causas

O erro 401 (Unauthorized) indica que a autenticação falhou. As causas mais comuns são:

### 1. Certificado ou Chave Privada Incorretos
- Certificado expirado
- Certificado não corresponde ao Client ID
- Chave privada não corresponde ao certificado
- Certificado do ambiente errado (stage vs production)

**Solução:**
- Verifique se o certificado e a chave privada são do mesmo par
- Confirme que está usando certificados do ambiente correto (stage ou production)
- Verifique a data de expiração do certificado

### 2. Client ID Incorreto ou Ausente
- Client ID não corresponde ao certificado
- Client ID não está sendo enviado no header correto

**Solução:**
- Verifique se o `client_id` no banco de dados está correto
- Confirme o formato esperado pela API Cora (pode ser `X-Client-Id`, `Client-Id`, ou outro)

### 3. Caminhos dos Arquivos Incorretos
- Caminhos relativos em vez de absolutos
- Arquivos não existem no caminho especificado
- Permissões incorretas nos arquivos

**Solução:**
- Use caminhos absolutos no banco de dados
- Verifique se os arquivos existem: `file_exists()` deve retornar `true`
- Verifique permissões: `chmod 600` para certificado e chave privada
- Verifique se o usuário do servidor web tem permissão de leitura

### 4. Formato de Autenticação mTLS
- Guzzle pode precisar do certificado em formato diferente
- Certificado e chave podem precisar estar no mesmo arquivo

**Solução:**
- Verifique os logs para ver se o certificado está sendo carregado
- Teste com `curl` diretamente para validar o certificado

### 5. URL da API Incorreta
- Usando URL de stage em produção ou vice-versa
- URL base incorreta

**Solução:**
- Verifique a variável de ambiente `CORA_API_URL`
- Stage: `https://api.stage.cora.com.br`
- Production: `https://api.cora.com.br` (confirmar com documentação)

## Como Diagnosticar

### 1. Verificar Logs
Os logs do Laravel devem mostrar informações detalhadas:

```bash
tail -f storage/logs/laravel.log | grep -i cora
```

Procure por:
- `Autenticação mTLS Cora configurada` - confirma que certificados foram carregados
- `Certificados Cora não configurados` - indica problema de configuração
- Detalhes do erro 401 na resposta da API

### 2. Verificar Configuração no Banco de Dados

```sql
SELECT id, name, bank_type, client_id, certificate_path, private_key_path 
FROM bancos 
WHERE bank_type = 'cora';
```

Verifique:
- `client_id` não está NULL
- `certificate_path` é um caminho absoluto válido
- `private_key_path` é um caminho absoluto válido

### 3. Testar Certificados com cURL

Teste diretamente com cURL para validar os certificados:

```bash
curl -X POST "https://api.stage.cora.com.br/v2/invoices/" \
  --cert /caminho/completo/certificate.pem \
  --key /caminho/completo/private-key.key \
  -H "X-Client-Id: seu-client-id-aqui" \
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

Se o cURL funcionar mas o Laravel não, o problema está na configuração do Guzzle.

### 4. Verificar Permissões dos Arquivos

```bash
# No servidor Linux
ls -la /caminho/completo/certificate.pem
ls -la /caminho/completo/private-key.key

# Deve mostrar permissões 600 (rw-------)
# E o dono deve ser o usuário do servidor web (www-data, nginx, etc.)
```

### 5. Testar Endpoint de Teste

Use o endpoint de teste criado:

```bash
POST /api/teste-cobranca-cora
{
  "banco_id": 1,
  "valor": 100.00,
  "cliente_id": 1
}
```

A resposta incluirá informações detalhadas sobre:
- Status dos certificados
- Headers enviados
- Resposta completa da API Cora

## Soluções Comuns

### Solução 1: Corrigir Caminhos dos Arquivos

No banco de dados, atualize para caminhos absolutos:

```sql
UPDATE bancos 
SET 
  certificate_path = '/caminho/absoluto/certificate.pem',
  private_key_path = '/caminho/absoluto/private-key.key'
WHERE bank_type = 'cora';
```

### Solução 2: Corrigir Permissões

```bash
chmod 600 /caminho/completo/certificate.pem
chmod 600 /caminho/completo/private-key.key
chown www-data:www-data /caminho/completo/certificate.pem
chown www-data:www-data /caminho/completo/private-key.key
```

### Solução 3: Verificar Client ID

Confirme com a Cora qual é o Client ID correto e atualize no banco:

```sql
UPDATE bancos 
SET client_id = 'client-id-correto'
WHERE bank_type = 'cora' AND id = X;
```

### Solução 4: Desabilitar Verificação SSL (Apenas para Debug)

No `.env`:

```
CORA_VERIFY_SSL=false
```

**⚠️ ATENÇÃO:** Use apenas para debug. Nunca em produção!

## Contato com Suporte Cora

Se nenhuma das soluções acima funcionar:

1. Verifique a documentação oficial da API Cora
2. Entre em contato com o suporte técnico da Cora
3. Forneça:
   - Client ID
   - Ambiente (stage/production)
   - Logs detalhados do erro
   - Resultado do teste com cURL

## Logs Úteis

Os logs do Laravel incluem:
- Caminhos dos certificados
- Status de existência e legibilidade dos arquivos
- Client ID usado
- Headers enviados
- Resposta completa da API (incluindo corpo do erro)

Revise os logs para identificar o problema específico.

