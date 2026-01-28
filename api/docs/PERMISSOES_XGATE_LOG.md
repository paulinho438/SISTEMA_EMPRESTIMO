# Como Corrigir Permissões do Log XGate (xgate.log)

> **Dica:** Para corrigir permissões de **todo** o `storage` (logs, cache, sessões) de uma vez, use [PERMISSOES_STORAGE.md](PERMISSOES_STORAGE.md).

## Problema

O erro abaixo indica que o processo que roda o PHP (servidor web ou queue) não tem permissão para escrever no arquivo de log da XGate:

```
The stream or file "/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/logs/xgate.log" 
could not be opened in append mode: Failed to open stream: Permission denied
```

Isso pode ocorrer em **produção** quando o diretório `storage/logs` ou o arquivo `xgate.log` pertence a outro usuário (ex.: deploy via git com seu usuário) e o PHP roda como `www-data`, `nginx`, `agecontrolecom`, etc.

## Solução Rápida

Execute no **servidor de produção** (com permissões de root ou sudo):

```bash
# Caminho base do projeto (ajuste se necessário)
BASE=/home/agecontrolecom/SISTEMA_EMPRESTIMO/api

# Garantir que o diretório de logs existe
mkdir -p "$BASE/storage/logs"

# Criar o arquivo se não existir
touch "$BASE/storage/logs/xgate.log"

# Permissões: diretório gravável pelo dono e pelo grupo
chmod -R 775 "$BASE/storage/logs"

# Dono: usuário que roda o PHP (web/queue). No seu caso pode ser agecontrolecom ou www-data
chown -R agecontrolecom:agecontrolecom "$BASE/storage"
# OU, se o PHP rodar como www-data:
# chown -R www-data:www-data "$BASE/storage"
```

Se você não usar `agecontrolecom` como usuário do PHP, descubra o usuário correto (veja seção abaixo) e use-o no `chown`.

## Como Descobrir o Usuário do Servidor Web / PHP

### PHP-FPM

```bash
ps aux | grep php-fpm | head -1
# A segunda coluna é o usuário (ex.: www-data, nginx, agecontrolecom)
```

### Apache

```bash
ps aux | grep apache | head -1
# Ou
grep -E 'APACHE_RUN_USER|User ' /etc/apache2/envvars /etc/apache2/apache2.conf 2>/dev/null | head -2
```

### Queue / Scheduler (Laravel)

Se os comandos `queue:work` ou o agendador rodam com outro usuário (ex.: `agecontrolecom` via cron), esse usuário também precisa de permissão de escrita em `storage/logs`:

```bash
crontab -u agecontrolecom -l
# Ou
cat /etc/cron.d/laravel-scheduler 2>/dev/null
```

Use o mesmo usuário no `chown` que roda o PHP (web e, se for o caso, queue).

## Passos Resumidos (checklist)

1. **Criar o arquivo** (se não existir):  
   `touch /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/logs/xgate.log`

2. **Permissões do diretório**:  
   `chmod -R 775 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/logs`

3. **Permissões do arquivo**:  
   `chmod 664 /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/logs/xgate.log`

4. **Dono** (substitua `USUARIO` pelo usuário que roda PHP/web/queue):  
   `chown -R USUARIO:USUARIO /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage`

## Verificar Permissões

Após corrigir:

```bash
ls -la /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/logs/
```

Exemplo esperado:

```
drwxrwxr-x  2 agecontrolecom agecontrolecom 4096 Jan 28 12:00 .
-rw-rw-r--  1 agecontrolecom agecontrolecom  123 Jan 28 12:00 laravel.log
-rw-rw-r--  1 agecontrolecom agecontrolecom    0 Jan 28 12:00 xgate.log
```

- Diretório: `775` (dono e grupo podem escrever).
- Arquivos: `664` (dono e grupo podem escrever; outros só leitura).

## Testar Escrita

Testar se o usuário do PHP consegue escrever no log:

```bash
# Substitua agecontrolecom pelo usuário que roda o PHP
sudo -u agecontrolecom php -r "
\$f = '/home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage/logs/xgate.log';
file_put_contents(\$f, date('c').' teste' . PHP_EOL, FILE_APPEND);
echo file_exists(\$f) ? 'OK - escrita no xgate.log' : 'ERRO';
"
```

## Deploy / Primeira Vez

Em novos deploys ou em servidores onde o `xgate.log` ainda não existe:

1. Garantir que `storage/logs` existe e é gravável pelo usuário do PHP (ex.: `chmod -R 775 storage` e `chown` em `storage`).
2. O Laravel pode criar `xgate.log` na primeira escrita; se o usuário do PHP for o dono de `storage`, isso costuma funcionar. Se o deploy for feito por outro usuário, executar os comandos de permissão acima **após** o deploy.

## Segurança

- Não exponha a pasta `storage/` (incluindo `storage/logs`) via web; ela deve ficar fora da raiz pública.
- Permissões `775` no diretório e `664` no arquivo são suficientes para o log; o importante é o dono ser o usuário que roda o PHP.

## Troubleshooting

- **Ainda Permission denied**: confirme que o usuário do `chown` é o mesmo que aparece em `ps aux | grep php-fpm` (ou do processo que dispara a requisição/queue).
- **SELinux (CentOS/RHEL)**: se estiver ativo, pode bloquear escrita. Ajuste o contexto:  
  `chcon -R -t httpd_sys_rw_content_t /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage`
- **Log continua em laravel.log**: verifique em `config/logging.php` se o canal `xgate` está com `path` apontando para `storage_path('logs/xgate.log')` e se o código usa `Log::channel('xgate')->...` para as mensagens XGate.
