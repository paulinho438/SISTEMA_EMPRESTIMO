# 📱 Controle de Integração WhatsApp

## 📋 Estrutura da Tabela `companies`

A tabela `companies` é responsável por armazenar as configurações de integração com a API do WhatsApp para cada empresa.

### Campos Relacionados ao WhatsApp

| Campo | Tipo | Descrição | Uso |
|-------|------|-----------|-----|
| `whatsapp` | `string` (nullable) | URL da API antiga do WhatsApp | Para empresas que usam a API antiga (company->id 8 e 1) |
| `token_api_wtz` | `string` (nullable) | Token de autenticação da nova API | Para empresas que usam a nova API (WAPI) |
| `instance_id` | `string` (nullable) | ID da instância da nova API | Para empresas que usam a nova API (WAPI) |

### Exemplo de Valores

**API Antiga:**
```php
$company->whatsapp = "https://node1.rjemprestimos.com.br";
// ou
$company->whatsapp = "https://node2.agecontrole.com.br";
```

**Nova API (WAPI):**
```php
$company->token_api_wtz = "seu_token_aqui";
$company->instance_id = "sua_instance_id_aqui";
```

## 🔄 Lógica de Diferenciação

O sistema diferencia automaticamente qual API usar baseado no `company->id`:

### API Antiga (HTTP)
- **Company IDs:** `8` e `1`
- **Endpoint:** `{whatsapp}/enviar-mensagem`
- **Método:** POST com JSON
- **Exemplo:**
  ```php
  Http::asJson()->post("$baseUrl/enviar-mensagem", [
      "numero" => "55" . $telefone,
      "mensagem" => $frase
  ]);
  ```

### Nova API (WAPI)
- **Company IDs:** Todos os outros (exceto 8 e 1)
- **Endpoint:** `https://api.w-api.app/v1/message/send-text?instanceId={instance_id}`
- **Método:** POST com Bearer Token
- **Exemplo:**
  ```php
  $wapiService->enviarMensagem(
      $company->token_api_wtz,
      $company->instance_id,
      ["phone" => "55" . $telefone, "message" => $frase]
  );
  ```

## 📍 Localização no Código

### Modelo
- **Arquivo:** `api/app/Models/Company.php`
- **Campos fillable:** `whatsapp`, `token_api_wtz`, `instance_id`

### Controller
- **Arquivo:** `api/app/Http/Controllers/EmprestimoController.php`
- **Método:** `enviarMensagem($parcela, $frase)` (linha ~2520)
- **Método privado:** `enviarMensagemAPIAntiga($parcela, $frase)`

### Service
- **Arquivo:** `api/app/Services/WAPIService.php`
- **Métodos:**
  - `enviarMensagem()` - Envio de texto
  - `enviarMensagemAudio()` - Envio de áudio
  - `envioMensagemVideo()` - Envio de vídeo
  - `enviarMensagemImagem()` - Envio de imagem

## 🗄️ Migrations

### Adicionar campo `whatsapp`
- **Arquivo:** `api/database/migrations/2024_04_20_150323_a_l_t_e_r__t_a_b_l_e__c_o_m_p_a_n_i_e_s.php`

### Adicionar campos `token_api_wtz` e `instance_id`
- **Arquivo:** `api/database/migrations/2025_07_05_121120_add_token_and_instanceid_to_company_table.php`

## 🔧 Como Configurar

### Via Controller
```php
$company = Company::find($id);
$company->whatsapp = "https://node1.rjemprestimos.com.br"; // API antiga
// OU
$company->token_api_wtz = "seu_token";
$company->instance_id = "sua_instance_id"; // Nova API
$company->save();
```

### Via Frontend
O campo `whatsapp` pode ser editado através do formulário de empresa:
- **Arquivo:** `site/src/views/empresa/EmpresaForm.vue`
- **Controller:** `api/app/Http/Controllers/CompanyController.php` (método `update`)

## 📝 Observações Importantes

1. **Compatibilidade:** O sistema suporta ambas as APIs simultaneamente
2. **Prioridade:** A escolha da API é feita automaticamente baseada no `company->id`
3. **Validação:** Para a nova API, o sistema verifica se `token_api_wtz` e `instance_id` estão preenchidos
4. **Fallback:** Se a nova API não estiver configurada, o sistema não envia mensagem (retorna sem erro)

## 🔍 Onde é Usado

O campo `whatsapp` da tabela `companies` é utilizado em vários pontos do sistema:

1. **EmprestimoController** - Envio de mensagens de cobrança
2. **ProcessarPixJob** - Notificações de pagamento
3. **CobrancaAutomaticaA/B/C** - Cobranças automáticas
4. **ClientController** - Mensagens para clientes
5. **EnviarMensagemWhatsApp** - Job de envio assíncrono

Todos esses pontos respeitam a lógica de diferenciação baseada no `company->id`.

