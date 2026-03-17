---
name: git-commit
description: 'ajuda a criar commits no git de forma simples e organizada. use quando a pessoa pedir para salvar alterações, criar um commit, fazer um commit no projeto ou mencionar "/commit". a skill analisa o que mudou, sugere uma mensagem clara em português, separa os arquivos quando fizer sentido e explica de forma fácil o que está sendo salvo.'
---

# Criar commit no Git de forma simples

## O que esta skill faz

Esta skill ajuda a **salvar alterações do projeto no Git** de um jeito mais organizado e fácil de entender.

Ela foi feita para pessoas que não querem lidar com muitos termos técnicos.  
Antes de salvar, a skill olha o que foi alterado e tenta explicar de forma simples:

- o que mudou
- quais arquivos fazem parte dessa mudança
- qual mensagem faz mais sentido para o commit
- o que será salvo naquele momento

A ideia é que a pessoa entenda **o que está sendo registrado no projeto**, sem precisar dominar Git.

---

## Como a skill deve agir

Quando a pessoa pedir para criar um commit:

1. Verificar o que foi alterado no projeto
2. Explicar isso em linguagem simples
3. Separar os arquivos, se necessário, para não misturar mudanças diferentes
4. Sugerir uma mensagem de commit clara em português
5. Fazer o commit somente com o que faz sentido naquele registro

Sempre priorize explicações curtas, diretas e fáceis de entender.

---

## Como explicar para a pessoa

Evite falar de forma técnica quando não for necessário.

Em vez de falar:
- "analisar diff"
- "staged files"
- "scope"
- "imperative mood"
- "conventional commits"

Prefira dizer:
- "ver o que mudou"
- "ver quais arquivos serão salvos"
- "identificar a parte do projeto afetada"
- "criar uma mensagem clara"
- "registrar a alteração de forma organizada"

Sempre que possível, explique como se estivesse falando com alguém iniciante.

Exemplo de explicação boa:
- "Encontrei mudanças na tela de login e no texto de ajuda."
- "Vou separar isso em um commit organizado."
- "A mensagem vai resumir de forma simples o que foi feito."

---

## Tipos de alteração

Quando precisar escolher o tipo do commit, use estes significados internos:

| Tipo       | Quando usar |
| ---------- | ----------- |
| `feat`     | quando foi adicionada uma nova funcionalidade |
| `fix`      | quando foi corrigido um problema |
| `docs`     | quando a mudança foi apenas em documentação ou texto explicativo |
| `style`    | quando foi apenas ajuste visual ou formatação sem mudar a lógica |
| `refactor` | quando o código foi reorganizado sem adicionar novidade nem corrigir erro |
| `perf`     | quando a mudança melhora desempenho |
| `test`     | quando foram criados ou ajustados testes |
| `build`    | quando a mudança envolve dependências ou processo de build |
| `ci`       | quando a mudança envolve automações e integrações |
| `chore`    | quando for manutenção geral |
| `revert`   | quando for desfazer uma alteração anterior |

Esses nomes podem aparecer no commit, mas a explicação para a pessoa deve continuar em português simples.

---

Importante:
- não adicionar assinatura
- não adicionar crédito de ferramenta
- não adicionar observações automáticas
- não adicionar "Made-with: Cursor" em nenhuma hipótese

---

## Como analisar as mudanças

Primeiro, verificar o que já está preparado para commit e o que ainda não está.

Use:

```bash
git status --porcelain
git diff --staged
git diff