<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <div class="flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="m-0">{{ contratoId ? 'Editar Contrato' : 'Simulação de Empréstimo' }}</h5>
                        <Breadcrumb :model="breadcrumbItems" />
                    </div>
                </div>

                <!-- Barra de progresso/etapas -->
                <div class="mb-4">
                    <div class="flex align-items-center gap-2 flex-wrap">
                        <span class="p-badge p-component" :class="etapaAtual === 1 ? 'p-badge-success' : (etapaAtual > 1 ? 'p-badge-info' : 'p-badge-secondary')">1. Operação</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component" :class="etapaAtual === 2 ? 'p-badge-success' : (etapaAtual > 2 ? 'p-badge-info' : 'p-badge-secondary')">2. Cliente</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component" :class="etapaAtual === 3 ? 'p-badge-success' : (etapaAtual > 3 ? 'p-badge-info' : 'p-badge-secondary')">3. Garantias</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component" :class="etapaAtual === 4 ? 'p-badge-success' : (etapaAtual > 4 ? 'p-badge-info' : 'p-badge-secondary')">4. Inadimplência</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component" :class="etapaAtual === 5 ? 'p-badge-success' : 'p-badge-secondary'">5. Concluir</span>
                    </div>
                </div>

                <div class="grid">
                    <!-- Coluna Esquerda - Formulário por etapa (largura total quando simulação oculta) -->
                    <div :class="etapaAtual === 1 ? 'col-12 lg:col-6' : 'col-12'">
                        <!-- Step 1 - Operação -->
                        <div v-if="etapaAtual === 1" class="card">
                            <h6 class="mb-3">1. Dados da Operação</h6>

                            <div class="field mb-3">
                                <label for="tipo_operacao" class="block mb-2">
                                    Tipo de operação <span class="text-red-500">*</span>
                                </label>
                                <Dropdown
                                    id="tipo_operacao"
                                    v-model="form.tipo_operacao"
                                    :options="tiposOperacao"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                />
                            </div>

                            <div class="field mb-3">
                                <label for="valor_solicitado" class="block mb-2">
                                    Valor solicitado <span class="text-red-500">*</span>
                                </label>
                                <InputNumber
                                    id="valor_solicitado"
                                    v-model="form.valor_solicitado"
                                    mode="decimal"
                                    :min="0.01"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="2"
                                    prefix="R$ "
                                    class="w-full"
                                    @input="onFormChange"
                                />
                            </div>

                            <div class="field mb-3">
                                <label for="periodo_amortizacao" class="block mb-2">
                                    Período de amortização <span class="text-red-500">*</span>
                                </label>
                                <Dropdown
                                    id="periodo_amortizacao"
                                    v-model="form.periodo_amortizacao"
                                    :options="periodosAmortizacao"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                    @change="onPeriodoChange"
                                />
                            </div>

                            <div class="field mb-3">
                                <label for="modelo_amortizacao" class="block mb-2">
                                    Modelo de amortização <span class="text-red-500">*</span>
                                </label>
                                <Dropdown
                                    id="modelo_amortizacao"
                                    v-model="form.modelo_amortizacao"
                                    :options="modelosAmortizacao"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Selecione"
                                    class="w-full"
                                    @change="onFormChange"
                                />
                            </div>

                            <div class="field mb-3">
                                <label for="quantidade_parcelas" class="block mb-2">
                                    Quantidade de parcelas <span class="text-red-500">*</span>
                                </label>
                                <InputNumber
                                    id="quantidade_parcelas"
                                    v-model="form.quantidade_parcelas"
                                    :min="1"
                                    :max="999"
                                    class="w-full"
                                    @input="onFormChange"
                                />
                            </div>

                            <div class="field mb-3">
                                <label for="taxa_juros_mensal" class="block mb-2">
                                    Taxa de juros mensal <span class="text-red-500">*</span>
                                </label>
                                <InputNumber
                                    id="taxa_juros_mensal"
                                    v-model="form.taxa_juros_mensal"
                                    :min="0.01"
                                    :minFractionDigits="2"
                                    :maxFractionDigits="2"
                                    suffix="%"
                                    class="w-full"
                                    @input="onFormChange"
                                />
                                <small class="text-500">Valor mínimo: 0,01%</small>
                            </div>

                            <div class="field mb-3">
                                <label for="data_assinatura" class="block mb-2">
                                    Data de assinatura do contrato e transferência de recursos <span class="text-red-500">*</span>
                                </label>
                                <Calendar
                                    id="data_assinatura"
                                    v-model="form.data_assinatura"
                                    dateFormat="dd/mm/yy"
                                    :showIcon="true"
                                    class="w-full"
                                    @date-select="onDataAssinaturaChange"
                                    :manualInput="false"
                                />
                            </div>

                            <div class="field mb-3">
                                <label for="banco_pagador" class="block mb-2">
                                    Banco pagador (para transferir ao cliente) <span class="text-red-500">*</span>
                                </label>
                                <Dropdown
                                    id="banco_pagador"
                                    v-model="form.banco_id"
                                    :options="bancos"
                                    optionLabel="name_agencia_conta"
                                    optionValue="id"
                                    placeholder="Selecione o banco"
                                    class="w-full"
                                />
                                <small class="text-500">Este banco será usado para pagar o cliente e iniciar o fluxo do empréstimo.</small>
                            </div>

                            <div class="field mb-3">
                                <label for="data_primeira_parcela" class="block mb-2">
                                    Data da primeira parcela <span class="text-red-500">*</span>
                                </label>
                                <Calendar
                                    id="data_primeira_parcela"
                                    v-model="form.data_primeira_parcela"
                                    dateFormat="dd/mm/yy"
                                    :minDate="form.data_assinatura"
                                    :showIcon="true"
                                    class="w-full"
                                    @date-select="onFormChange"
                                    :manualInput="false"
                                />
                                <small v-if="!isValid" class="text-red-500">
                                    A data da primeira parcela deve ser igual ou posterior à data de assinatura.
                                </small>
                            </div>

                            <!-- Toggles -->
                            <div class="field mb-3">
                                <div class="flex align-items-center gap-2">
                                    <InputSwitch v-model="form.cliente_simples_nacional" @change="onFormChange" />
                                    <label for="cliente_simples_nacional" class="mb-0">
                                        Cliente optante do Simples Nacional
                                    </label>
                                </div>
                            </div>

                            <div class="field mb-3">
                                <div class="flex align-items-center gap-2">
                                    <InputSwitch v-model="form.calcular_iof" @change="onFormChange" />
                                    <label for="calcular_iof" class="mb-0">
                                        Calcular IOF
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-content-between mt-4">
                                <Button label="Cancelar" icon="pi pi-times" class="p-button-outlined p-button-secondary" @click="voltarParaLista" />
                                <Button label="Avançar" icon="pi pi-arrow-right" class="p-button-primary" :disabled="!form.banco_id" @click="avancarEtapa" />
                            </div>
                        </div>

                        <!-- Step 2 - Cliente -->
                        <div v-if="etapaAtual === 2" class="card">
                            <h6 class="mb-3">2. Cliente (Pessoa Jurídica)</h6>
                            <div class="field mb-3">
                                <label for="cliente_pj" class="block mb-2">
                                    Buscar empresa <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <AutoComplete
                                        id="cliente_pj"
                                        v-model="clienteSelecionado"
                                        :suggestions="clientesPJFiltered"
                                        optionLabel="label_completo"
                                        placeholder="Digite para buscar por razão social, nome fantasia ou CNPJ"
                                        class="flex-1"
                                        @complete="searchClientesPJ"
                                        @item-select="onClienteSelect"
                                        @clear="onClienteClear"
                                    />
                                    <Button label="Nova empresa" icon="pi pi-plus" class="p-button-outlined" @click="abrirCadastroPJ" />
                                </div>
                                <small v-if="!form.client_id && etapaAtual >= 2" class="text-500">Selecione uma empresa para continuar.</small>
                            </div>
                            <div v-if="form.client_id && clienteSelecionado" class="p-3 surface-100 border-round mb-3">
                                <h6 class="mt-0 mb-2">Cliente selecionado</h6>
                                <p class="m-0"><strong>Razão Social:</strong> {{ clienteSelecionado.razao_social || clienteSelecionado.nome_completo }}</p>
                                <p class="m-0"><strong>CNPJ:</strong> {{ clienteSelecionado.cnpj }}</p>
                                <p class="m-0"><strong>E-mail:</strong> {{ clienteSelecionado.email }}</p>
                            </div>
                            <div class="flex justify-content-between mt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="voltarEtapa" />
                                <Button label="Salvar e Avançar" icon="pi pi-arrow-right" class="p-button-primary" :disabled="!form.client_id" @click="salvarEAvancar" />
                            </div>
                        </div>

                        <!-- Step 3 - Garantias -->
                        <div v-if="etapaAtual === 3">
                            <StepGarantias v-model="form.garantias" />
                            <div class="flex justify-content-between mt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="voltarEtapa" />
                                <Button label="Salvar e Avançar" icon="pi pi-arrow-right" class="p-button-primary" @click="salvarEAvancar" />
                            </div>
                        </div>

                        <!-- Step 4 - Inadimplência -->
                        <div v-if="etapaAtual === 4" class="card">
                            <h6 class="mb-3">4. Inadimplência</h6>
                            <p class="text-500 text-sm mb-3">Configure multa e juros de mora para parcelas em atraso.</p>
                            <div class="grid formgrid p-fluid">
                                <div class="field col-12 md:col-4">
                                    <label for="multa">Multa (% sobre parcela)</label>
                                    <InputNumber id="multa" v-model="form.inadimplencia.multa_percentual" :min="0" :max="100" :minFractionDigits="2" suffix="%" class="w-full" />
                                </div>
                                <div class="field col-12 md:col-4">
                                    <label for="juros_mora">Juros de mora (% ao dia)</label>
                                    <InputNumber id="juros_mora" v-model="form.inadimplencia.juros_mora_diario" :min="0" :max="10" :minFractionDigits="2" suffix="%" class="w-full" />
                                </div>
                            </div>
                            <div class="flex justify-content-between mt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="voltarEtapa" />
                                <Button label="Avançar" icon="pi pi-arrow-right" class="p-button-primary" @click="salvarEAvancar" />
                            </div>
                        </div>

                        <!-- Step 5 - Concluir -->
                        <div v-if="etapaAtual === 5" class="card">
                            <h6 class="mb-3">5. Concluir</h6>
                            <div class="surface-100 p-3 border-round mb-3">
                                <p class="font-semibold m-0 mb-2">Resumo</p>
                                <p class="m-0 text-sm"><strong>Operação:</strong> {{ form.tipo_operacao }} - {{ form.valor_solicitado }} em {{ form.quantidade_parcelas }}x</p>
                                <p class="m-0 text-sm"><strong>Cliente:</strong> {{ clienteSelecionado?.razao_social || clienteSelecionado?.nome_completo || '—' }}</p>
                                <p class="m-0 text-sm"><strong>Garantias:</strong> {{ (form.garantias || []).length }} cadastrada(s)</p>
                            </div>

                            <!-- Assinatura Eletrônica -->
                            <div class="mb-4">
                                <h6 class="mb-2">Assinatura Eletrônica</h6>
                                <p class="text-500 text-sm mb-3">Escolha como o contrato será assinado pelas partes.</p>
                                <div class="flex flex-column gap-2">
                                    <div class="flex align-items-start">
                                        <RadioButton v-model="assinaturaTipo" inputId="sem_assinatura" name="assinatura" value="sem" :disabled="false" />
                                        <label for="sem_assinatura" class="ml-2 cursor-pointer">
                                            <strong>Sem Assinatura Eletrônica</strong>
                                            <p class="m-0 text-500 text-sm">Assinatura realizada fisicamente ou por outros meios digitais externos ao sistema.</p>
                                        </label>
                                    </div>
                                    <div class="flex align-items-start opacity-60">
                                        <RadioButton v-model="assinaturaTipo" inputId="assinatura_avancada" name="assinatura" value="avancada" :disabled="true" />
                                        <label for="assinatura_avancada" class="ml-2">
                                            <strong>Assinatura Eletrônica Avançada (Selfie + Documento)</strong>
                                            <p class="m-0 text-500 text-sm">Serão coletadas a foto do signatário e do documento de identidade (Custo: 1,00 créditos/documento).</p>
                                        </label>
                                    </div>
                                    <div class="flex align-items-start opacity-60">
                                        <RadioButton v-model="assinaturaTipo" inputId="assinatura_qualificada" name="assinatura" value="qualificada" :disabled="true" />
                                        <label for="assinatura_qualificada" class="ml-2">
                                            <strong>Assinatura Eletrônica Qualificada (Certificado Digital)</strong>
                                            <p class="m-0 text-500 text-sm">Uso obrigatório de certificado digital ICP-Brasil (Custo: 1,00 créditos/documento).</p>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Baixar documentos -->
                            <div class="mb-4">
                                <h6 class="mb-2">Documentos do contrato</h6>
                                <SplitButton
                                    label="Baixar documentos do contrato"
                                    icon="pi pi-download"
                                    :model="documentosMenuItems"
                                    class="p-button-outlined"
                                    :disabled="!result"
                                />
                            </div>

                            <div class="flex gap-2 mb-3">
                                <Button label="Exportar simulação" icon="pi pi-download" class="p-button-outlined p-button-secondary" @click="exportSimulation" :disabled="!result" />
                                <Button label="Salvar simulação" icon="pi pi-save" class="p-button-success" :loading="saving" :disabled="!result || !form.client_id" @click="onSaveSimulation" />
                            </div>
                            <p class="text-500 text-sm mb-3">Volte à etapa Operação para visualizar a tabela de simulação completa.</p>
                            <div class="p-3 border-round mb-3" style="background-color: #fff3cd; border: 1px solid #ffeeba;">
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-exclamation-triangle" style="color: #856404;"></i>
                                    <small style="color: #856404;">
                                        Ao iniciar o contrato, a operação e as cobranças serão iniciadas. As informações financeiras não poderão mais ser alteradas (será necessário criar outro contrato).
                                    </small>
                                </div>
                            </div>
                            <div class="flex justify-content-between align-items-center mt-4">
                                <Button label="Voltar" icon="pi pi-arrow-left" class="p-button-outlined p-button-secondary" @click="voltarEtapa" />
                                <div class="flex gap-2">
                                    <Button label="Salvar e fechar (sem iniciar)" class="p-button-outlined" :loading="saving" :disabled="!result || !form.client_id" @click="onSaveSimulation" />
                                    <Button label="Iniciar contrato" class="p-button-danger" :disabled="!result || !form.client_id || !form.banco_id" @click="onEfetivarContrato" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Direita - Simulação (apenas na etapa Operação) -->
                    <div v-if="etapaAtual === 1" class="col-12 lg:col-6">
                        <div class="card">
                            <h6 class="mb-3">Simulação</h6>

                            <div v-if="loading" class="text-center p-4">
                                <ProgressSpinner />
                                <p class="mt-3">Calculando simulação...</p>
                            </div>

                            <div v-else-if="error" class="p-3 mb-3 border-round" style="background-color: #fee;">
                                <Message severity="error" :closable="false">{{ error }}</Message>
                            </div>

                            <div v-else-if="result">
                                <!-- Tabela de Simulação -->
                                <DataTable
                                    :value="result.cronograma"
                                    :paginator="true"
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50]"
                                    responsiveLayout="scroll"
                                    class="mb-4"
                                >
                                    <Column field="numero" header="#" :sortable="true" style="width: 60px" />
                                    <Column field="parcela" header="Parcela" :sortable="true">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.parcela) }}
                                        </template>
                                    </Column>
                                    <Column field="vencimento" header="Data de Vencimento" :sortable="true">
                                        <template #body="{ data }">
                                            {{ formatDate(data.vencimento) }}
                                        </template>
                                    </Column>
                                    <Column field="juros" header="Juros" :sortable="true">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.juros) }}
                                        </template>
                                    </Column>
                                    <Column field="amortizacao" header="Amortização" :sortable="true">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.amortizacao) }}
                                        </template>
                                    </Column>
                                    <Column field="saldo_devedor" header="Saldo devedor" :sortable="true">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.saldo_devedor) }}
                                        </template>
                                    </Column>
                                </DataTable>

                                <!-- Botões Exportar e Salvar -->
                                <div class="flex gap-2 mb-4">
                                    <Button
                                        label="Exportar simulação"
                                        icon="pi pi-download"
                                        class="p-button-danger flex-1"
                                        @click="exportSimulation"
                                    />
                                    <Button
                                        label="Salvar simulação"
                                        icon="pi pi-save"
                                        class="p-button-success flex-1"
                                        :loading="saving"
                                        :disabled="!result || !form.client_id"
                                        @click="onSaveSimulation"
                                    />
                                </div>

                                <!-- Outras Informações -->
                                <div class="card">
                                    <h6 class="mb-3">Outras Informações</h6>
                                    <div class="grid">
                                        <div class="col-12 mb-2">
                                            <strong>Data de assinatura do contrato e transferência de recursos:</strong>
                                            <span class="ml-2">{{ formatDate(result.inputs.data_assinatura) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Valor do IOF:</strong>
                                            <span class="ml-2">{{ formatCurrency(result.iof.total) }}</span>
                                        </div>
                                        <div class="col-12 mb-2 pl-4">
                                            <small>IOF diário ({{ result.iof.aliquota_diaria }}):</small>
                                            <span class="ml-2">{{ formatCurrency(result.iof.diario) }}</span>
                                        </div>
                                        <div class="col-12 mb-2 pl-4">
                                            <small>IOF adicional (0,38%):</small>
                                            <span class="ml-2">{{ formatCurrency(result.iof.adicional) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Valor do contrato:</strong>
                                            <span class="ml-2">{{ formatCurrency(result.valor_contrato) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Total das parcelas:</strong>
                                            <span class="ml-2">{{ formatCurrency(result.totais.total_parcelas) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>CET ao mês:</strong>
                                            <span class="ml-2">{{ formatPercent(parseFloat(result.totais.cet_mes)) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>CET ao ano:</strong>
                                            <span class="ml-2">{{ formatPercent(parseFloat(result.totais.cet_ano)) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Juros de acerto:</strong>
                                            <span class="ml-2">{{ formatCurrency(result.totais.juros_acerto) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center p-4 text-500">
                                Preencha os campos do formulário para gerar a simulação.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useLoanSimulation } from '@/composables/useLoanSimulation';
import ClientService from '@/service/ClientService';
import EmpresaService from '@/service/EmpresaService';
import SimulacaoEmprestimoService from '@/service/SimulacaoEmprestimoService';
import BancoService from '@/service/BancoService';
import StepGarantias from './steps/StepGarantias.vue';
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import AutoComplete from 'primevue/autocomplete';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import RadioButton from 'primevue/radiobutton';
import SplitButton from 'primevue/splitbutton';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';
import Toast from 'primevue/toast';

const {
    form,
    loading,
    saving,
    error,
    result,
    isValid,
    simulateDebounced,
    setDefaultFirstDue,
    formatCurrency,
    formatPercent,
    formatDate,
    exportPDF,
    exportContratoInicial,
    exportNotasPromissorias,
    saveSimulation,
} = useLoanSimulation();

const toast = useToast();
const router = useRouter();
const route = useRoute();
const clientService = new ClientService();
const empresaService = new EmpresaService();
const simulacaoService = new SimulacaoEmprestimoService();
const bancoService = new BancoService();
const assinaturaTipo = ref('sem');
const empresaData = ref({});
const etapaAtual = ref(1);
const clienteSelecionado = ref(null);
const clientesPJFiltered = ref([]);
const contratoId = ref(null);
const bancos = ref([]);

const breadcrumbItems = ref([
    { label: 'Contratos', to: '/emprestimos' },
    { label: 'Simulação de Empréstimo' },
]);

const tiposOperacao = ref([
    { label: 'Empréstimo', value: 'Empréstimo' },
]);

const periodosAmortizacao = ref([
    { label: 'Diário', value: 'Diário' },
    { label: 'Semanal', value: 'Semanal' },
    { label: 'Mensal', value: 'Mensal' },
]);

const modelosAmortizacao = ref([
    { label: 'Price', value: 'Price' },
]);

function onFormChange() {
    if (isValid.value) {
        simulateDebounced();
    }
}

function onPeriodoChange() {
    setDefaultFirstDue();
    if (isValid.value) {
        simulateDebounced();
    }
}

function onDataAssinaturaChange() {
    setDefaultFirstDue();
    if (isValid.value) {
        simulateDebounced();
    }
}

async function searchClientesPJ(event) {
    const query = event.query || '';
    try {
        const res = await clientService.getByTipoPessoa('PJ', query);
        clientesPJFiltered.value = res.data?.data || [];
    } catch (e) {
        clientesPJFiltered.value = [];
    }
}

function onClienteSelect(event) {
    form.client_id = event.value?.id ?? null;
}

function onClienteClear() {
    form.client_id = null;
    clienteSelecionado.value = null;
}

function abrirCadastroPJ() {
    router.push({ name: 'pjAdd' });
}

function avancarEtapa() {
    if (etapaAtual.value < 5) {
        etapaAtual.value++;
        if (etapaAtual.value === 5 && isValid.value) {
            simulateDebounced();
        }
    }
}

async function salvarRascunhoSeEditando() {
    // Só faz PUT quando estiver editando um contrato existente
    if (!contratoId.value) return true;
    const res = await saveSimulation(contratoId.value);
    if (res?.success) return true;
    toast.add({ severity: 'error', summary: 'Erro', detail: res?.message || 'Erro ao salvar rascunho.', life: 5000 });
    return false;
}

async function salvarEAvancar() {
    const ok = await salvarRascunhoSeEditando();
    if (!ok) return;
    avancarEtapa();
}

function voltarEtapa() {
    if (etapaAtual.value > 1) {
        etapaAtual.value--;
    }
}

function voltarParaLista() {
    router.push('/emprestimos');
}

function exportSimulation() {
    exportPDF();
}

const documentosMenuItems = ref([
    {
        label: 'Contrato Inicial',
        icon: 'pi pi-file-pdf',
        command: () => {
            exportContratoInicial(empresaData.value, clienteSelecionado.value);
        },
    },
    {
        label: 'Notas promissórias',
        icon: 'pi pi-file-pdf',
        command: () => {
            exportNotasPromissorias(empresaData.value, clienteSelecionado.value, form.garantias || []);
        },
    },
]);

async function carregarEmpresa() {
    try {
        const res = await empresaService.get();
        empresaData.value = res.data?.data ?? res.data ?? {};
    } catch {
        empresaData.value = {};
    }
}

async function carregarBancos() {
    try {
        const res = await bancoService.getAll();
        bancos.value = res.data?.data ?? [];
    } catch {
        bancos.value = [];
    }
}

async function onSaveSimulation() {
    const res = await saveSimulation(contratoId.value);
    if (res?.success) {
        if (res?.id) contratoId.value = res.id;
        toast.add({ severity: 'success', summary: 'Salvo', detail: 'Simulação salva com sucesso.', life: 3000 });
    } else if (res?.message) {
        toast.add({ severity: 'error', summary: 'Erro', detail: res.message, life: 5000 });
    }
}

async function onEfetivarContrato() {
    try {
        if (!result.value || !form.client_id) return;

        if (!contratoId.value) {
            const res = await saveSimulation(null);
            if (!res?.success) {
                toast.add({ severity: 'error', summary: 'Erro', detail: res?.message || 'Erro ao salvar antes de efetivar.', life: 5000 });
                return;
            }
            if (res?.id) contratoId.value = res.id;
        }

        const resp = await simulacaoService.efetivar(contratoId.value);
        const emprestimoId = resp?.data?.emprestimo_id;
        toast.add({ severity: 'success', summary: 'Iniciado', detail: 'Contrato iniciado e empréstimo criado.', life: 3000 });
        if (emprestimoId) {
            router.push(`/emprestimos/${emprestimoId}/aprovacao`);
        } else {
            router.push('/contratos');
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível efetivar o contrato.', life: 5000 });
    }
}

function mapPeriodoToLabel(p) {
    const norm = String(p || '').toLowerCase();
    if (norm === 'diario') return 'Diário';
    if (norm === 'semanal') return 'Semanal';
    if (norm === 'mensal') return 'Mensal';
    return 'Diário';
}

function mapModeloToLabel(m) {
    const norm = String(m || '').toLowerCase();
    if (norm === 'price') return 'Price';
    return 'Price';
}

async function carregarContratoParaEdicao(id) {
    try {
        const res = await simulacaoService.get(id);
        const payload = res.data || {};
        contratoId.value = payload.id || id;

        if (payload.result) {
            result.value = payload.result;
        }

        const inputs = payload.result?.inputs || {};
        form.valor_solicitado = String(inputs.valor_solicitado ?? form.valor_solicitado);
        form.periodo_amortizacao = mapPeriodoToLabel(inputs.periodo_amortizacao ?? form.periodo_amortizacao);
        form.modelo_amortizacao = mapModeloToLabel(inputs.modelo_amortizacao ?? form.modelo_amortizacao);
        form.quantidade_parcelas = Number(inputs.quantidade_parcelas ?? form.quantidade_parcelas);
        form.taxa_juros_mensal = String(inputs.taxa_juros_mensal ?? form.taxa_juros_mensal);
        form.calcular_iof = Boolean(inputs.calcular_iof ?? form.calcular_iof);
        form.cliente_simples_nacional = Boolean(inputs.simples_nacional ?? form.cliente_simples_nacional);

        form.data_assinatura = inputs.data_assinatura ? new Date(inputs.data_assinatura) : form.data_assinatura;
        form.data_primeira_parcela = inputs.data_primeira_parcela ? new Date(inputs.data_primeira_parcela) : form.data_primeira_parcela;

        form.garantias = inputs.garantias || [];
        form.inadimplencia = inputs.inadimplencia || form.inadimplencia;

        form.client_id = payload.client_id ?? form.client_id;
        form.banco_id = payload.banco_id ?? inputs.banco_id ?? form.banco_id;

        if (form.client_id) {
            const cRes = await clientService.get(form.client_id);
            clienteSelecionado.value = cRes.data?.data ?? cRes.data ?? null;
        }

        breadcrumbItems.value = [
            { label: 'Contratos', to: '/contratos' },
            { label: 'Editar Contrato' },
        ];
        etapaAtual.value = 5;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível carregar o contrato para edição.', life: 4000 });
    }
}

// Simular ao montar componente se dados válidos
onMounted(() => {
    if (isValid.value) {
        simulateDebounced();
    }
    carregarEmpresa();
    carregarBancos();

    const qId = route.query?.contratoId;
    if (qId) {
        carregarContratoParaEdicao(qId);
    }
});
</script>

<style scoped>
.text-red-500 {
    color: #ef4444;
}

.text-500 {
    color: #6b7280;
}

.text-sm {
    font-size: 0.875rem;
}
</style>
