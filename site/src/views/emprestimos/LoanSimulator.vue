<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <div class="flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="m-0">Simulação de Empréstimo</h5>
                        <Breadcrumb :model="breadcrumbItems" />
                    </div>
                </div>

                <!-- Barra de progresso/etapas -->
                <div class="mb-4">
                    <div class="flex align-items-center gap-2">
                        <span class="p-badge p-component" :class="etapaAtual === 1 ? 'p-badge-success' : 'p-badge-secondary'">1. Operação</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component p-badge-secondary">2. Cliente</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component p-badge-secondary">3. Garantias</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component p-badge-secondary">4. Inadimplência</span>
                        <i class="pi pi-chevron-right text-500"></i>
                        <span class="p-badge p-component p-badge-secondary">5. Concluir</span>
                    </div>
                </div>

                <div class="grid">
                    <!-- Coluna Esquerda - Formulário -->
                    <div class="col-12 lg:col-6">
                        <div class="card">
                            <h6 class="mb-3">Dados da Operação</h6>

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
                                    @change="onFormChange"
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
                                    @date-select="onFormChange"
                                    :manualInput="false"
                                />
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
                                    <InputSwitch v-model="form.cliente_simples_nacional" />
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

                            <!-- Seção Garantias -->
                            <div class="field mb-3">
                                <h6 class="mb-3">Garantias</h6>
                                
                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.sem_garantia" />
                                    <label class="mb-0">Sem garantia</label>
                                    <span class="text-500 text-sm">0,01% (valor mínimo)</span>
                                </div>

                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.avalistas" />
                                    <label class="mb-0">Avalistas</label>
                                </div>

                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.imovel" />
                                    <label class="mb-0">Imóvel</label>
                                </div>

                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.veiculo" />
                                    <label class="mb-0">Veículo</label>
                                </div>

                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.devedor_solidario" />
                                    <label class="mb-0">Devedor solidário</label>
                                </div>

                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.recebiveis" />
                                    <label class="mb-0">Recebíveis</label>
                                </div>

                                <div class="flex align-items-center gap-2 mb-2">
                                    <InputSwitch v-model="form.garantias.outras_garantias" />
                                    <label class="mb-0">Outras garantias</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Coluna Direita - Resultados -->
                    <div class="col-12 lg:col-6">
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

                                <!-- Botão Exportar -->
                                <Button
                                    label="Exportar simulação"
                                    icon="pi pi-download"
                                    class="p-button-danger mb-4 w-full"
                                    @click="exportSimulation"
                                />

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
                                            <small>IOF diário (0,0082%):</small>
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
                                            <span class="ml-2">{{ formatPercent(parseFloat(result.totais.cet_mes) * 100) }}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>CET ao ano:</strong>
                                            <span class="ml-2">{{ formatPercent(parseFloat(result.totais.cet_ano) * 100) }}</span>
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
import { ref, onMounted, watch } from 'vue';
import { useLoanSimulation } from '@/composables/useLoanSimulation';
import Breadcrumb from 'primevue/breadcrumb';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

const {
    form,
    loading,
    error,
    result,
    isValid,
    simulateDebounced,
    formatCurrency,
    formatPercent,
    formatDate,
    exportJSON,
    exportCSV,
} = useLoanSimulation();

const etapaAtual = ref(1);

const breadcrumbItems = ref([
    { label: 'Contratos', to: '/emprestimos' },
    { label: 'Simulação de Empréstimo' },
]);

const tiposOperacao = ref([
    { label: 'Empréstimo', value: 'Empréstimo' },
]);

const periodosAmortizacao = ref([
    { label: 'Diário', value: 'Diário' },
]);

const modelosAmortizacao = ref([
    { label: 'Price', value: 'Price' },
]);

function onFormChange() {
    if (isValid.value) {
        simulateDebounced();
    }
}

function exportSimulation() {
    // Perguntar ao usuário qual formato deseja
    // Por enquanto, exportar ambos
    exportJSON();
    setTimeout(() => {
        exportCSV();
    }, 500);
}

// Simular ao montar componente se dados válidos
onMounted(() => {
    if (isValid.value) {
        simulateDebounced();
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
