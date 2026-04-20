## Objetivo

Implementar “auditoria” no backend Laravel (`api/`) cobrindo:

- **Auditoria de dados (CRUD)** em modelos críticos (capturar before/after, evento e autoria).
- **Log de ações de negócio** (eventos do fluxo: gerar cobrança PIX, processar webhook, renovar/refinanciar, baixa manual etc.).

O resultado deve permitir rastrear **quem fez o quê**, **quando**, **em qual entidade** e **quais campos mudaram**, sem misturar auditoria de dados com logs operacionais.

## Contexto atual (observado)

- Projeto usa **Laravel 9**.
- Modelos relevantes já existentes: `App\Models\Emprestimo`, `App\Models\Parcela`, `App\Models\CustomLog`.
- Rotas de empréstimo são extensas e centralizadas em `EmprestimoController`.

## Abordagens consideradas

### Opção 1 (recomendada): `owen-it/laravel-auditing` para dados + `CustomLog` para ações

- **Auditoria de dados**: pacote `owen-it/laravel-auditing` (tabela `audits`) para create/update/delete.
- **Ações de negócio**: manter `CustomLog` (ou evoluir depois) para eventos de fluxo.

**Prós**
- Auditoria “por model” bem padrão e confiável (eventos + old/new).
- Separação clara de responsabilidades (audits ≠ logs de fluxo).

**Contras**
- Duas fontes/tabelas (o que é aceitável e desejável pela separação).

### Opção 2: `spatie/laravel-activitylog` para tudo

**Prós**
- Centraliza tudo em uma tabela e API.

**Contras**
- Auditoria de dados completa exige mais configuração e disciplina; risco de inconsistência.

### Opção 3: Auditoria custom (observers + tabela própria)

**Prós**
- Totalmente customizável.

**Contras**
- Maior custo de manutenção e maior chance de “buracos” no rastreamento.

## Design aprovado para implementação

### 1) Auditoria de dados (CRUD)

#### Pacote

- Adotar `owen-it/laravel-auditing`.

#### Modelos auditados (escopo inicial)

- `App\Models\Emprestimo`
- `App\Models\Parcela`

Extensões posteriores (fora do escopo inicial, mas previstas):
- `Client`, `Banco`, `Contaspagar`, `Contasreceber`, `Movimentacaofinanceira`.

#### Estratégia de volume e relevância

- **Parcela** tende a ter atualizações frequentes; portanto:
  - Definir **lista explícita** de campos a auditar (allowlist) OU excluir campos ruidosos.
  - Foco inicial em: `venc_real`, `dt_baixa`, `saldo`, `valor_recebido`, `valor_recebido_pix`, `nome_usuario_baixa`, `nome_usuario_baixa_pix`, `identificador`, `chave_pix`.

#### Autoria (quem fez)

- Preencher `user_type`/`user_id` automaticamente a partir do usuário autenticado.
- Considerar múltiplos guards (no projeto há `auth:api` e `auth:clientes`); a auditoria deve capturar o usuário disponível no contexto.

#### Metadados recomendados

- `ip_address` e `user_agent` (quando houver request HTTP).
- Quando for execução via job/command sem request, aceitar auditoria sem IP/UA.

#### Privacidade

- Excluir/mascarar campos sensíveis caso existam nos modelos auditados (tokens, chaves, segredos, etc.).

### 2) Log de ações de negócio

#### Fonte

- Manter `App\Models\CustomLog` como registro de ações (eventos) do fluxo.

#### Padrão de payload

- `operation`: string padronizada (ex.: `emprestimo.renovacao`, `parcela.baixa_manual`, `pix.gerar`, `webhook.apix.recebido`).
- `content`: JSON serializado com:
  - ids relevantes (`emprestimo_id`, `parcela_id`, `client_id`, `company_id`)
  - valores de entrada/saída relevantes (quando necessário)
  - identificadores externos (txid, charge_id etc.), se aplicável

### 3) API de consulta (escopo inicial)

Adicionar endpoints internos (rotas protegidas por `auth:api`):

- `GET /emprestimo/{id}/audits`
  - Retorna auditoria do `Emprestimo` + auditoria das `Parcelas` relacionadas (paginado).
- `GET /parcela/{id}/audits`
  - Retorna auditoria da parcela.

Observação: o endpoint `/log` já existe; a padronização do payload de `CustomLog` é parte do trabalho, mas sem quebrar compatibilidade do consumidor atual.

### 4) Critérios de sucesso

- Um update em `Emprestimo`/`Parcela` gera registro em `audits` com:
  - evento correto (created/updated/deleted)
  - before/after de campos relevantes
  - autoria quando houver usuário autenticado
- Ações de fluxo principais passam a registrar `CustomLog` com `operation` consistente.
- Novos endpoints permitem consultar auditoria por entidade.

## Fora do escopo (por enquanto)

- UI/backoffice para visualizar auditoria (somente API).
- Reprocessamento histórico de auditoria (não retroativo).
- Auditoria em todos os modelos do sistema (vamos começar pelos críticos).

