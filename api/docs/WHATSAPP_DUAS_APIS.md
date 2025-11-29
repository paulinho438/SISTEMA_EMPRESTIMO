# 📱 Sistema de Duas APIs WhatsApp

## 📋 Resumo

O sistema agora suporta **duas APIs do WhatsApp** separadas:

1. **API Geral (`whatsapp`)** - Usada para todas as funcionalidades exceto cobrança
2. **API Cobrança (`whatsapp_cobranca`)** - Usada exclusivamente para cobranças automáticas

## 🗄️ Estrutura do Banco de Dados

### Tabela `companies`

| Campo | Tipo | Descrição | Uso |
|-------|------|-----------|-----|
| `whatsapp` | `string` (nullable) | URL da API WhatsApp Geral | Renovações, Transferências, Personalização, etc. |
| `whatsapp_cobranca` | `string` (nullable) | URL da API WhatsApp Cobrança | **Apenas** cobranças automáticas |

## 🔄 Lógica de Uso

### API Geral (`whatsapp`)
Usada para:
- ✅ Renovações (`MensagemAutomaticaRenovacao`, `EnvioMensagemRenovacao`)
- ✅ Transferências/Notificações de Pagamento (`ProcessarPixJob`)
- ✅ Personalização de Pagamento (`EmprestimoController::personalizarPagamento`)
- ✅ Envio em Massa (`ClientController::enviarMensagemMassa`)
- ✅ Envio de Documentos (PDFs)
- ✅ Todas as outras funcionalidades

### API Cobrança (`whatsapp_cobranca`)
Usada **exclusivamente** para:
- ✅ `CobrancaAutomaticaA` - Primeira etapa de cobrança
- ✅ `CobrancaAutomaticaB` - Segunda etapa de cobrança
- ✅ `CobrancaAutomaticaC` - Terceira etapa de cobrança
- ✅ `ProcessarWebhookCobranca` - Processamento de webhooks de cobrança

### Fallback
Se `whatsapp_cobranca` não estiver configurado, o sistema usa `whatsapp` como fallback:
```php
$baseUrl = $company->whatsapp_cobranca ?? $company->whatsapp;
```

## 📍 Arquivos Modificados

### Backend

1. **Migration:**
   - `api/database/migrations/2025_01_27_000000_add_whatsapp_cobranca_to_companies_table.php`

2. **Model:**
   - `api/app/Models/Company.php` - Adicionado `whatsapp_cobranca` no `$fillable`

3. **Controller:**
   - `api/app/Http/Controllers/CompanyController.php` - Salva `whatsapp_cobranca`

4. **Resource:**
   - `api/app/Http/Resources/EmpresaResource.php` - Retorna `whatsapp_cobranca`

5. **Comandos de Cobrança:**
   - `api/app/Console/Commands/CobrancaAutomaticaA.php`
   - `api/app/Console/Commands/CobrancaAutomaticaB.php`
   - `api/app/Console/Commands/CobrancaAutomaticaC.php`
   - `api/app/Console/Commands/ProcessarWebhookCobranca.php`

### Frontend

1. **Formulário de Empresa:**
   - `site/src/views/empresa/EmpresaForm.vue` - Mostra status de conexão das 2 APIs

2. **Formulário de Empresas:**
   - `site/src/views/empresas/EmpresasForm.vue` - Campos para configurar as 2 APIs

## 🎯 Como Configurar

### Via Frontend

1. Acesse: `https://sistema.agecontrole.com.br/#/empresa`
2. Configure:
   - **URL Integração WhatsApp (Geral)** - Para todas as funcionalidades
   - **URL Integração WhatsApp (Cobrança)** - Apenas para cobranças
3. Conecte ambas as APIs usando os botões de conexão

### Via Backend

```php
$company = Company::find($id);
$company->whatsapp = "https://node1.rjemprestimos.com.br"; // API Geral
$company->whatsapp_cobranca = "https://node2.rjemprestimos.com.br"; // API Cobrança
$company->save();
```

## ✅ Benefícios

1. **Separação de Responsabilidades:** Cobranças isoladas das outras funcionalidades
2. **Flexibilidade:** Pode usar APIs diferentes para diferentes propósitos
3. **Fallback:** Se não configurar `whatsapp_cobranca`, usa `whatsapp` automaticamente
4. **Monitoramento:** Status de conexão separado para cada API no frontend

## 🔍 Verificação

Para verificar se está funcionando:

1. Configure `whatsapp_cobranca` na empresa
2. Execute um comando de cobrança: `php artisan cobranca:AutomaticaA`
3. Verifique os logs para confirmar que está usando a API correta

## 📝 Notas Importantes

- Se `whatsapp_cobranca` não estiver configurado, o sistema usa `whatsapp` como fallback
- Ambas as APIs precisam estar conectadas para funcionar corretamente
- O status de conexão é verificado automaticamente a cada 10 segundos no frontend
- A API de cobrança é usada **apenas** nos comandos de cobrança automática

