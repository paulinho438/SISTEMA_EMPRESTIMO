# Configuração de Certificados Cora

## Localização dos Arquivos no Servidor

Os certificados do Cora devem ser armazenados em um local seguro no servidor. Recomenda-se a seguinte estrutura:

### Estrutura de Diretórios

```
api/
└── storage/
    └── app/
        └── certificates/
            └── cora/
                ├── certificate.pem
                └── private-key
```

### Caminho Completo no Servidor

**Linux/Unix:**
```
/path/to/project/api/storage/app/certificates/cora/certificate.pem
/path/to/project/api/storage/app/certificates/cora/private-key
```

**Windows:**
```
C:\path\to\project\api\storage\app\certificates\cora\certificate.pem
C:\path\to\project\api\storage\app\certificates\cora\private-key
```

### Passos para Configuração

1. **Criar o diretório de certificados:**
   ```bash
   mkdir -p api/storage/app/certificates/cora
   ```

2. **Copiar os arquivos de certificado:**
   - Copie `certificate.pem` para `api/storage/app/certificates/cora/certificate.pem`
   - Copie `private-key` para `api/storage/app/certificates/cora/private-key`

3. **Definir permissões adequadas (Linux/Unix):**
   ```bash
   chmod 600 api/storage/app/certificates/cora/certificate.pem
   chmod 600 api/storage/app/certificates/cora/private-key
   chown www-data:www-data api/storage/app/certificates/cora/*
   ```
   (Substitua `www-data` pelo usuário do servidor web se necessário)

4. **Configurar no banco de dados:**
   - Ao cadastrar/editar um banco do tipo Cora, configure:
     - `bank_type`: `cora`
     - `client_id`: O Client ID fornecido pela Cora (ex: `int-3g1VYFU7tflsufR9ZrsUXp`)
     - `certificate_path`: Caminho absoluto do certificado
       - Exemplo Linux: `/var/www/projeto/api/storage/app/certificates/cora/certificate.pem`
       - Exemplo Windows: `C:\projetos\SISTEMA_EMPRESTIMO\api\storage\app\certificates\cora\certificate.pem`
     - `private_key_path`: Caminho absoluto da chave privada
       - Exemplo Linux: `/var/www/projeto/api/storage/app/certificates/cora/private-key`
       - Exemplo Windows: `C:\projetos\SISTEMA_EMPRESTIMO\api\storage\app\certificates\cora\private-key`

### Exemplo de Configuração no Banco de Dados

```sql
UPDATE bancos 
SET 
    bank_type = 'cora',
    client_id = 'int-3g1VYFU7tflsufR9ZrsUXp',
    certificate_path = '/var/www/projeto/api/storage/app/certificates/cora/certificate.pem',
    private_key_path = '/var/www/projeto/api/storage/app/certificates/cora/private-key'
WHERE id = 1;
```

### Segurança

⚠️ **IMPORTANTE:**
- Os arquivos de certificado e chave privada contêm informações sensíveis
- Certifique-se de que o diretório `storage/app/certificates/` está no `.gitignore`
- Use permissões restritas (600) nos arquivos
- Não compartilhe ou exponha esses arquivos publicamente
- Faça backup seguro dos certificados

### Verificação

Para verificar se os arquivos estão no local correto e acessíveis:

```bash
# Verificar se os arquivos existem
ls -la api/storage/app/certificates/cora/

# Verificar permissões
stat api/storage/app/certificates/cora/certificate.pem
stat api/storage/app/certificates/cora/private-key
```

### Troubleshooting

**Erro: "Certificado não encontrado"**
- Verifique se o caminho está correto e é absoluto
- Verifique se o arquivo existe no caminho especificado
- Verifique as permissões do arquivo

**Erro: "Autenticação falhou"**
- Verifique se ambos os arquivos (certificado e chave privada) estão configurados
- Verifique se os arquivos não estão corrompidos
- Verifique se o Client ID está correto

