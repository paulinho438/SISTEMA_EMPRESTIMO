# Como Corrigir Permissões dos Certificados Cora

## Problema

O erro "Certificados Cora não são legíveis" indica que o usuário do servidor web (PHP-FPM, Apache, Nginx) não tem permissão para ler os arquivos de certificado.

## Solução Rápida

Execute no servidor Linux (com permissões de root ou sudo):

```bash
# Definir permissões 600 (apenas leitura/escrita para o dono)
chmod 600 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem
chmod 600 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key

# Alterar dono para o usuário do servidor web
# No seu caso, o usuário é 'agecontrolecom' (conforme erro)
chown agecontrolecom:agecontrolecom /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem
chown agecontrolecom:agecontrolecom /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key

# OU se o usuário for diferente, descubra qual é:
ps aux | grep php-fpm | head -1
# E use o usuário mostrado na primeira coluna
```

## Como Descobrir o Usuário do Servidor Web

### Para PHP-FPM:

```bash
# Verificar usuário do PHP-FPM
ps aux | grep php-fpm | head -1
# Ou
cat /etc/php-fpm.d/www.conf | grep user
```

### Para Apache:

```bash
# Verificar usuário do Apache
ps aux | grep apache | head -1
# Ou
cat /etc/apache2/envvars | grep APACHE_RUN_USER
```

### Para Nginx + PHP-FPM:

```bash
# Verificar usuário do PHP-FPM
ps aux | grep php-fpm | head -1
```

## Usuários Comuns por Distribuição

- **Ubuntu/Debian**: `www-data`
- **CentOS/RHEL**: `apache` ou `nginx`
- **Fedora**: `apache`
- **Arch Linux**: `http`

## Script Automatizado

Use o script fornecido em `api/scripts/fix_cora_permissions.sh`:

```bash
cd /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/scripts
chmod +x fix_cora_permissions.sh
sudo ./fix_cora_permissions.sh
```

## Verificar Permissões

Após corrigir, verifique:

```bash
ls -la /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/
```

Deve mostrar algo como:

```
-rw------- 1 www-data www-data 1234 Dec 15 10:00 certificate.pem
-rw------- 1 www-data www-data 1234 Dec 15 10:00 private-key.key
```

Onde:
- `-rw-------` = permissões 600 (apenas o dono pode ler/escrever)
- `www-data www-data` = usuário e grupo do servidor web

## Testar Leitura

Teste se o usuário do servidor web consegue ler os arquivos:

```bash
# Substitua 'www-data' pelo usuário correto
sudo -u www-data cat /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/certificate.pem > /dev/null && echo "OK" || echo "ERRO"
sudo -u www-data cat /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/app/certificates/cora/private-key.key > /dev/null && echo "OK" || echo "ERRO"
```

## Segurança

⚠️ **IMPORTANTE**: 
- Permissões 600 são essenciais para segurança
- Apenas o dono deve poder ler os arquivos
- Nunca use permissões 644 ou 755 para chaves privadas
- Mantenha os arquivos fora do diretório público (`public/`)

## Troubleshooting

Se ainda não funcionar após corrigir as permissões:

1. Verifique se o caminho no banco de dados está correto
2. Verifique se os arquivos realmente existem no caminho especificado
3. Verifique os logs do Laravel para mais detalhes
4. Execute o teste novamente após corrigir as permissões

