# Como Corrigir Permissões do Diretório storage (Produção)

## Problemas comuns

O PHP (servidor web, queue, scheduler) precisa **escrever** em vários subdiretórios de `storage/`. Se o dono ou as permissões estiverem errados, aparecem erros como:

- **Logs:** `Permission denied` ao abrir `storage/logs/xgate.log` ou `laravel.log`
- **Cache:** `Unable to create lockable file` em `storage/framework/cache/data/...` (rate limiter, cache em arquivo)
- **Sessions:** erro ao gravar sessão em `storage/framework/sessions/`
- **Uploads:** erro ao salvar arquivos em `storage/app/`

Todos se resolvem garantindo que o **usuário que roda o PHP** seja dono (ou tenha permissão de escrita) em **todo** o `storage/`.

---

## Solução única (recomendada)

Execute no **servidor de produção** (com sudo), ajustando `USUARIO` para o usuário do PHP (ex.: `agecontrolecom`, `www-data`):

```bash
BASE=/home/agecontrolecom/SISTEMA_EMPRESTIMO/api

# Criar estrutura mínima se não existir
mkdir -p "$BASE/storage/framework/cache/data"
mkdir -p "$BASE/storage/framework/sessions"
mkdir -p "$BASE/storage/framework/views"
mkdir -p "$BASE/storage/logs"
mkdir -p "$BASE/bootstrap/cache"

# Permissões: dono e grupo podem ler/escrever; outros só leitura
chmod -R 775 "$BASE/storage"
chmod -R 775 "$BASE/bootstrap/cache"

# Dono: usuário e grupo que rodam o PHP (web + queue/cron)
chown -R agecontrolecom:agecontrolecom "$BASE/storage"
chown -R agecontrolecom:agecontrolecom "$BASE/bootstrap/cache"
```

Se o PHP rodar como outro usuário (ex.: `www-data`), use:

```bash
chown -R www-data:www-data "$BASE/storage"
chown -R www-data:www-data "$BASE/bootstrap/cache"
```

---

## Erro específico: cache (rate limiter)

Mensagem:

```
Unable to create lockable file: .../storage/framework/cache/data/ae/40/ae40....
Please ensure you have permission to create files in this location.
```

Isso ocorre quando o **ThrottleRequests** (rate limiter da API) tenta gravar no cache em arquivo. Corrigindo o `storage` inteiro (e o `bootstrap/cache`) com os comandos acima, o problema some.

---

## Como descobrir o usuário do PHP

```bash
# PHP-FPM
ps aux | grep php-fpm | head -1

# Ou usuário do processo que atende requisições web
ps aux | grep -E 'php-fpm|apache|nginx' | head -3
```

Use o usuário da **segunda coluna** no `chown`.

---

## Checklist rápido

| Ação | Comando |
|------|--------|
| Dono do storage | `sudo chown -R USUARIO:USUARIO /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/storage` |
| Dono do bootstrap/cache | `sudo chown -R USUARIO:USUARIO /home/agecontrolecom/SISTEMA_EMPRESTIMO/api/bootstrap/cache` |
| Permissões | `sudo chmod -R 775 .../api/storage .../api/bootstrap/cache` |

---

## Após deploy (git pull)

Se o deploy for feito por um usuário (ex.: `agecontrolecom`) e o PHP rodar como outro (ex.: `www-data`), rode os comandos de `chown` e `chmod` **depois** do `git pull` para o usuário do PHP voltar a ter permissão.

---

## Referência

- Log XGate em separado: [PERMISSOES_XGATE_LOG.md](PERMISSOES_XGATE_LOG.md)  
- Certificados Cora: [CORRIGIR_PERMISSOES_CORA.md](CORRIGIR_PERMISSOES_CORA.md)
