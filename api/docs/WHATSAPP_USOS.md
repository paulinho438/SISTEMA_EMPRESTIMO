# 📱 Locais onde o WhatsApp é Utilizado no Sistema

## 📋 Resumo Geral

O WhatsApp é utilizado em **múltiplos pontos** do sistema para comunicação com clientes. Abaixo está a lista completa organizada por categoria.

---

## 1. 💰 **COBRANÇA** (Cobranças Automáticas)

### 1.1 Cobrança Automática A
- **Arquivo:** `api/app/Console/Commands/CobrancaAutomaticaA.php`
- **Função:** Envio automático de mensagens de cobrança
- **Tipos de envio:**
  - Mensagem de texto
  - Mensagem de áudio (se configurado)
  - PDF de comprovante (se necessário)
- **Quando:** Executado via cron/schedule

### 1.2 Cobrança Automática B
- **Arquivo:** `api/app/Console/Commands/CobrancaAutomaticaB.php`
- **Função:** Segunda etapa de cobrança automática
- **Tipos de envio:**
  - Mensagem de texto
  - Mensagem de áudio

### 1.3 Cobrança Automática C
- **Arquivo:** `api/app/Console/Commands/CobrancaAutomaticaC.php`
- **Função:** Terceira etapa de cobrança automática
- **Tipos de envio:**
  - Mensagem de texto

### 1.4 Processar Webhook de Cobrança
- **Arquivo:** `api/app/Console/Commands/ProcessarWebhookCobranca.php`
- **Função:** Processa webhooks de pagamento e envia mensagens
- **Tipos de envio:**
  - Mensagem de texto
  - Mensagem de áudio (se configurado)
- **Quando:** Após recebimento de pagamento via PIX

### 1.5 Teste de Cobrança Automática
- **Arquivo:** `api/app/Http/Controllers/CobrancaAutomaticaATestController.php`
- **Função:** Endpoint para testar envio de mensagens de cobrança
- **Rota:** `POST /api/cobrancas/enviar-mensagem-teste`

---

## 2. 💸 **TRANSFERÊNCIA** (Notificações de Pagamento)

### 2.1 Processar PIX Job
- **Arquivo:** `api/app/Jobs/ProcessarPixJob.php`
- **Função:** Processa pagamentos PIX e envia notificações
- **Tipos de envio:**
  - **PDF de comprovante** (`/enviar-pdf`)
  - **Mensagem de texto** com informações do pagamento
  - **Imagem** (comprovante em base64)
  - **Áudio** (mensagem de voz)
  - **Vídeo** (se configurado)
- **Quando:** Após confirmação de pagamento PIX

### 2.2 Enviar Comprovante Fornecedor
- **Arquivo:** `api/app/Jobs/EnviarComprovanteFornecedor.php`
- **Função:** Envia comprovante para fornecedores
- **Status:** Código comentado (não está ativo)

---

## 3. 🔄 **RENOVAÇÃO** (Mensagens de Renovação)

### 3.1 Mensagem Automática de Renovação
- **Arquivo:** `api/app/Console/Commands/MensagemAutomaticaRenovacao.php`
- **Função:** Envia mensagens automáticas sobre renovações
- **Tipos de envio:**
  - Mensagem de texto com informações de renovação

### 3.2 Envio Mensagem Renovação
- **Arquivo:** `api/app/Console/Commands/EnvioMensagemRenovacao.php`
- **Função:** Envia mensagens específicas de renovação
- **Tipos de envio:**
  - Mensagem de texto

---

## 4. 🎯 **PERSONALIZAÇÃO** (Pagamentos Personalizados)

### 4.1 Pagamento Personalizado
- **Arquivo:** `api/app/Http/Controllers/EmprestimoController.php`
- **Método:** `personalizarPagamento()`
- **Função:** Envia chave PIX personalizada via WhatsApp
- **Tipos de envio:**
  - Mensagem de texto com valor personalizado
  - Mensagem com chave PIX copia e cola
- **Rota:** `POST /api/emprestimos/{id}/personalizar-pagamento`

---

## 5. 📤 **ENVIO EM MASSA** (Mensagens para Múltiplos Clientes)

### 5.1 Enviar Mensagem em Massa
- **Arquivo:** `api/app/Http/Controllers/ClientController.php`
- **Método:** `enviarMensagemMassa()`
- **Função:** Envia mensagens para múltiplos clientes
- **Tipos de envio:**
  - Mensagem de texto personalizada
  - Mensagem sobre valores pré-aprovados
- **Rota:** `POST /api/enviarmensagemmassa`

### 5.2 Enviar Mensagem para Cliente
- **Arquivo:** `api/app/Http/Controllers/ClientController.php`
- **Método:** `enviarMensagem()`
- **Função:** Envia mensagem individual para cliente
- **Tipos de envio:**
  - Mensagem de texto

### 5.3 Enviar Mensagem Usuário App
- **Arquivo:** `api/app/Http/Controllers/ClientController.php`
- **Método:** `enviarMensagemUsuarioApp()`
- **Função:** Envia mensagem para usuários do app
- **Tipos de envio:**
  - Mensagem de texto

---

## 6. 📄 **DOCUMENTOS** (Envio de PDFs)

### 6.1 Envio de PDF de Contas a Pagar
- **Arquivo:** `api/app/Http/Controllers/EmprestimoController.php`
- **Função:** Envia PDF de contas a pagar via WhatsApp
- **Endpoint usado:** `{whatsapp}/enviar-pdf`

### 6.2 Envio de PDF de Comprovante
- **Arquivo:** `api/app/Jobs/ProcessarPixJob.php`
- **Função:** Envia PDF de comprovante de pagamento
- **Endpoint usado:** `{whatsapp}/enviar-pdf`

---

## 7. 🧪 **TESTES** (Endpoints de Teste)

### 7.1 Teste WAPI - Mensagem
- **Arquivo:** `api/app/Http/Controllers/EmprestimoController.php`
- **Método:** `enviarMensagemWAPITeste()`
- **Função:** Testa envio de mensagem via nova API (WAPI)
- **Rota:** `POST /api/wapi/envio_mensagem_teste`

### 7.2 Teste WAPI - Áudio
- **Arquivo:** `api/app/Http/Controllers/EmprestimoController.php`
- **Método:** `enviarMensagemAudioWAPITeste()`
- **Função:** Testa envio de áudio via nova API (WAPI)
- **Rota:** `POST /api/wapi/envio_mensagem_teste_audio`

---

## 8. 📱 **FRONTEND** (Abertura do WhatsApp)

### 8.1 App Mobile (React Native)
- **Arquivos:**
  - `appemprestimos/src/containers/moreOpctions/ATMFinder/CobrancaMap.js`
  - `appemprestimos/src/containers/moreOpctions/ATMFinder/ClientMap.js`
  - `appemprestimos/src/containers/moreOpctions/ATMFinder/BaixaMap.js`
  - `appemprestimos/src/components/modals/Location.js`
  - `appemprestimos/src/components/modals/ParcelasExtorno.js`
  - `appemprestimos/src/components/modals/InfoParcelas.js`
- **Função:** Abre WhatsApp nativo do celular com número pré-preenchido
- **Formato:** `whatsapp://send?phone={numero}&text={mensagem}`

### 8.2 Site (Vue.js)
- **Arquivos:**
  - `site/src/views/pages/Landing.vue`
  - `site/src/views/emprestimosfinalizados/EmprestimosFinalizadosList.vue`
  - `site/src/views/empresa/EmpresaForm.vue`
- **Função:** 
  - Exibe informações de contato WhatsApp
  - Abre WhatsApp Web com número pré-preenchido
  - Formato: `https://wa.me/{numero}?text={mensagem}`

---

## 9. ⚙️ **CONFIGURAÇÃO** (Gerenciamento)

### 9.1 Formulário de Empresa
- **Arquivo:** `site/src/views/empresa/EmpresaForm.vue`
- **Função:** 
  - Configura URL de integração WhatsApp
  - Testa conexão com WhatsApp (`/logar`)
  - Desconecta WhatsApp

### 9.2 Formulário de Empresas
- **Arquivo:** `site/src/views/empresas/EmpresasForm.vue`
- **Função:** Edita URL de integração WhatsApp da empresa

---

## 📊 **Resumo por Tipo de Envio**

| Tipo | Endpoint/API | Onde é Usado |
|------|--------------|--------------|
| **Mensagem de Texto** | `/enviar-mensagem` ou WAPI | Cobranças, Renovações, Notificações |
| **Mensagem de Áudio** | `/enviar-audio` ou WAPI | Cobranças automáticas (se configurado) |
| **PDF** | `/enviar-pdf` | Comprovantes, Contas a pagar |
| **Imagem** | WAPI | Comprovantes em base64 |
| **Vídeo** | WAPI | Mensagens especiais (se configurado) |

---

## 🔄 **Fluxo de Diferenciação de API**

O sistema diferencia automaticamente qual API usar:

1. **API Antiga (HTTP):**
   - Companies com ID `8` ou `1`
   - Endpoints: `/enviar-mensagem`, `/enviar-pdf`, `/enviar-audio`, `/logar`

2. **Nova API (WAPI):**
   - Todas as outras companies
   - Usa `WAPIService` com `token_api_wtz` e `instance_id`
   - Endpoint: `https://api.w-api.app/v1/message/*`

---

## 📝 **Observações Importantes**

1. **Jobs Assíncronos:** Alguns envios são feitos via Jobs (Queue) para não bloquear a aplicação
2. **Validação:** Sistema verifica se WhatsApp está configurado antes de enviar
3. **Logs:** Mensagens importantes são logadas para auditoria
4. **Retry:** Alguns Jobs têm tentativas automáticas em caso de falha
5. **Delay:** Alguns envios têm delay configurado para evitar spam

---

## 🎯 **Principais Casos de Uso**

1. ✅ **Cobrança** - Envio automático de mensagens de cobrança
2. ✅ **Transferência** - Notificação de pagamentos recebidos
3. ✅ **Renovação** - Mensagens sobre renovações disponíveis
4. ✅ **Personalização** - Envio de chaves PIX personalizadas
5. ✅ **Massa** - Campanhas para múltiplos clientes
6. ✅ **Documentos** - Envio de PDFs e comprovantes
7. ✅ **Testes** - Endpoints para testar integração

