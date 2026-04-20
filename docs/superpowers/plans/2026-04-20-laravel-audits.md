# Laravel Audits (tabelas críticas) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar auditoria de dados (before/after + autoria) para tabelas críticas (`emprestimos`, `parcelas`, e depois extensível) e padronizar log de ações de negócio via `CustomLog`, incluindo endpoints para consulta de auditoria por entidade.

**Architecture:** Usar `owen-it/laravel-auditing` para auditoria CRUD em Eloquent Models (tabela `audits`) e manter `CustomLog` para eventos de fluxo. Expor endpoints internos para buscar audits de `Emprestimo` e `Parcela` com paginação.

**Tech Stack:** Laravel 9, PHP 8.0, MySQL, `owen-it/laravel-auditing`.

---

## Estrutura de arquivos (criar/modificar)

**Criar:**
- `api/config/audit.php` (via publish do pacote)
- `api/database/migrations/*_create_audits_table.php` (via publish do pacote)
- `api/app/Http/Controllers/AuditController.php` (consulta de auditorias por entidade)

**Modificar:**
- `api/composer.json` (adicionar dependência)
- `api/app/Models/Emprestimo.php` (habilitar auditing)
- `api/app/Models/Parcela.php` (habilitar auditing e restringir campos auditados)
- `api/routes/api.php` (registrar rotas de consulta de audits)
- `api/app/Models/CustomLog.php` (opcional: casts e helper p/ content como array)
- `api/app/Http/Controllers/EmprestimoController.php` (inserir logs de ação em pontos críticos; incremental)

---

### Task 1: Instalar e publicar Laravel Auditing

**Files:**
- Modify: `api/composer.json`
- Create: `api/config/audit.php`
- Create: `api/database/migrations/*_create_audits_table.php`

- [ ] **Step 1: Instalar dependência**

Run (na pasta `api/`):
```bash
composer require owen-it/laravel-auditing
```
Expected: dependência instalada e `vendor/` atualizado.

- [ ] **Step 2: Publicar config e migration do pacote**

Run:
```bash
php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider"
```
Expected: criação de `config/audit.php` e migration `create_audits_table`.

- [ ] **Step 3: Rodar migrations**

Run:
```bash
php artisan migrate
```
Expected: tabela `audits` criada no banco.

- [ ] **Step 4: Sanity check de config**

Verificar no `config/audit.php` (manual):
- Driver padrão de storage (database).
- Campos de auditoria habilitados.
- Resolver de usuário configurado (se necessário).

- [ ] **Step 5: Commit (somente se solicitado pelo usuário)**

---

### Task 2: Habilitar auditoria nos models críticos (Emprestimo e Parcela)

**Files:**
- Modify: `api/app/Models/Emprestimo.php`
- Modify: `api/app/Models/Parcela.php`

- [ ] **Step 1: Criar testes mínimos (opcional, se o projeto tiver harness de testes pronto)**

Se existir infra de testes com DB:
```bash
php artisan test
```
Expected: baseline (pode falhar por testes ausentes; documentar).

- [ ] **Step 2: Habilitar auditing no `Emprestimo`**

Mudanças esperadas:
- Implementar `OwenIt\Auditing\Contracts\Auditable`
- Usar trait `\OwenIt\Auditing\Auditable`

- [ ] **Step 3: Habilitar auditing no `Parcela` com allowlist**

Mudanças esperadas:
- Implementar `Auditable` + trait
- Definir lista de campos auditáveis (ex.: `auditInclude`) para reduzir ruído:
  - `venc_real`, `dt_baixa`, `saldo`, `valor_recebido`, `valor_recebido_pix`, `nome_usuario_baixa`, `nome_usuario_baixa_pix`, `identificador`, `chave_pix`, `venc_real_audit`

- [ ] **Step 4: Verificar geração de audits via tinker (smoke test)**

Run:
```bash
php artisan tinker
```
Expected (exemplo):
- Atualizar um registro existente de `Parcela` (p.ex. setar `venc_real_audit`) deve criar linha em `audits`.

- [ ] **Step 5: Commit (somente se solicitado pelo usuário)**

---

### Task 3: Autoria (user_type/user_id) + metadados (IP/UA)

**Files:**
- Modify: `api/config/audit.php` (se necessário)
- (Opcional) Create: `api/app/Providers/AuditServiceProvider.php` ou configuração equivalente

- [ ] **Step 1: Garantir captura de usuário autenticado**

Objetivo: quando request passar por `auth:api` (usuários internos) ou `auth:clientes`, o audit deve preencher `user_id`.

Implementação típica:
- Configurar o resolver de usuário do pacote (se necessário) para tentar `auth('api')->user()` e depois `auth('clientes')->user()`.

- [ ] **Step 2: Capturar IP e User-Agent**

Objetivo: preencher `ip_address` e `user_agent` nos audits quando houver request HTTP.

Implementação típica:
- Ajustar `audit.php`/resolvers do pacote para incluir `ip_address`/`user_agent` no campo de metadados.

- [ ] **Step 3: Smoke test HTTP**

Run:
```bash
php artisan serve
```
E chamar um endpoint que atualize `Emprestimo`/`Parcela` autenticado; verificar se audit contém `user_id`, `ip_address`, `user_agent`.

- [ ] **Step 4: Commit (somente se solicitado pelo usuário)**

---

### Task 4: API de consulta de auditoria (Emprestimo e Parcela)

**Files:**
- Create: `api/app/Http/Controllers/AuditController.php`
- Modify: `api/routes/api.php`

- [ ] **Step 1: Criar controller de consulta**

Endpoints:
- `GET /emprestimo/{id}/audits`
  - Retorna audits do `Emprestimo` e das `Parcelas` relacionadas
  - Paginação por query params (`page`, `per_page`)
- `GET /parcela/{id}/audits`

Regras:
- Rotas dentro do middleware `auth:api` + `single.token` (seguir padrão existente).
- Resposta JSON consistente (items, meta de paginação).

- [ ] **Step 2: Adicionar rotas**

Adicionar em `api/routes/api.php` dentro do grupo protegido.

- [ ] **Step 3: Smoke test**

Run:
```bash
php artisan route:list | rg "audits"
```
Expected: rotas registradas.

- [ ] **Step 4: Commit (somente se solicitado pelo usuário)**

---

### Task 5: Padronizar log de ações com `CustomLog` (incremental, sem quebrar compatibilidade)

**Files:**
- Modify: `api/app/Models/CustomLog.php`
- Modify: `api/app/Http/Controllers/EmprestimoController.php`

- [ ] **Step 1: Ajustar `CustomLog` para suportar `content` como array (sem quebrar)**

Opção:
- Adicionar `casts` para `content` como `array` (se for coluna JSON) OU manter string e criar helper `CustomLog::write($operation, array $content, ?int $userId)`.

- [ ] **Step 2: Inserir logs em pontos críticos do fluxo**

Começar por 3-5 ações de alto valor, por exemplo:
- Renovação/refinanciamento (`emprestimo.renovacao`, `emprestimo.refinanciamento`)
- Baixa manual (`parcela.baixa_manual`)
- Geração de PIX (`pix.gerar`)
- Recebimento webhook (`webhook.*.recebido`)

Critérios:
- `operation` padronizado
- `content` com ids e identificadores externos

- [ ] **Step 3: Smoke test (manual)**

Executar fluxo e validar que `/log` mostra registros com `operation` e `content` esperado.

- [ ] **Step 4: Commit (somente se solicitado pelo usuário)**

---

## Self-review (plan vs spec)

- **Cobertura do spec**: auditoria em `Emprestimo`/`Parcela` (Task 2), autoria + IP/UA (Task 3), endpoints de consulta (Task 4), log de ações via `CustomLog` (Task 5).
- **Sem placeholders**: passos têm comandos e objetivos; onde há variação (“se necessário”), a decisão é tomada durante implementação ao inspecionar o `audit.php` publicado e estrutura da coluna `content` em `custom_logs`.

---

## Próximo passo (execução)

Plano completo e salvo em `docs/superpowers/plans/2026-04-20-laravel-audits.md`. Duas opções de execução:

1. **Subagent-Driven (recomendado)** - despacho um subagent por task e reviso entre tasks  
2. **Execução inline** - executo as tasks aqui na sessão, com checkpoints de revisão

Qual abordagem você prefere?

