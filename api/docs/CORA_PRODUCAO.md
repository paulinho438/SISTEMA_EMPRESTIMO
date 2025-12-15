# Configuração para Produção - API Cora

## URLs para Integração Direta

### Ambiente Stage (Teste)
- **Token URL**: `https://matls-clients.api.stage.cora.com.br/token`
- **API URL**: `https://api.stage.cora.com.br`

### Ambiente Produção
- **Token URL**: `https://matls-clients.api.cora.com.br/token`
- **API URL**: `https://api.cora.com.br`

## Configuração no .env

Para usar em **produção**, configure no arquivo `.env`:

```env
CORA_API_URL=https://api.cora.com.br
```

Para usar em **stage** (teste), configure:

```env
CORA_API_URL=https://api.stage.cora.com.br
```

## Certificados

⚠️ **IMPORTANTE**: Cada ambiente (stage e produção) possui seu próprio conjunto de credenciais:

- **Certificados de Stage** devem ser usados apenas com URLs de Stage
- **Certificados de Produção** devem ser usados apenas com URLs de Produção
- **NÃO misture** certificados de um ambiente com URLs de outro ambiente

## Verificação

Após configurar, verifique:

1. A variável `CORA_API_URL` no `.env` está correta
2. Os certificados no banco de dados correspondem ao ambiente configurado
3. O Client ID corresponde ao certificado do ambiente correto

## Teste

Para testar em produção, certifique-se de:

1. Ter certificados de produção configurados no banco de dados
2. Ter o Client ID de produção configurado
3. Ter a URL de produção configurada no `.env`
4. Testar primeiro em stage antes de ir para produção

