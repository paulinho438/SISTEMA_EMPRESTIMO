<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import SimulacaoEmprestimoService from '@/service/SimulacaoEmprestimoService';
import Breadcrumb from 'primevue/breadcrumb';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';

const router = useRouter();
const toast = useToast();
const service = new SimulacaoEmprestimoService();

const contratos = ref([]);
const loading = ref(false);
const total = ref(0);
const totalEmPreenchimento = ref(0);
const totalEfetivados = ref(0);
const currentPage = ref(1);
const perPage = ref(10);
const search = ref('');
const situacaoFilter = ref(null);

const situacaoOptions = [
    { label: 'Todos', value: null },
    { label: 'Em preenchimento', value: 'em_preenchimento' },
    { label: 'Efetivados', value: 'efetivado' }
];

const breadcrumbItems = [
    { label: 'Contratos', to: '/contratos' },
    { label: 'Listar' }
];

function formatCurrency(val) {
    if (val == null || val === undefined) return 'R$ 0,00';
    return Number(val).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatPercent(val) {
    if (val == null || val === undefined) return '0,00%';
    return Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR');
}

function getSituacaoClass(situacao) {
    return situacao === 'Efetivado'
        ? 'p-badge p-badge-success'
        : 'p-badge p-badge-warning';
}

async function getContratos(page = 1) {
    loading.value = true;
    currentPage.value = page;
    try {
        const params = {
            page,
            per_page: perPage.value,
            ...(situacaoFilter.value && { situacao: situacaoFilter.value }),
            ...(search.value && { q: search.value })
        };
        const res = await service.getAll(params);
        contratos.value = res.data?.data || [];
        const meta = res.data?.meta || {};
        total.value = meta.total ?? contratos.value.length;
        totalEmPreenchimento.value = meta.total_em_preenchimento ?? 0;
        totalEfetivados.value = meta.total_efetivados ?? 0;
    } catch (err) {
        toast.add({
            severity: 'error',
            detail: err?.response?.data?.message || 'Erro ao carregar contratos',
            life: 3000
        });
        contratos.value = [];
    } finally {
        loading.value = false;
    }
}

async function efetivarContrato(id) {
    try {
        await service.efetivar(id);
        toast.add({ severity: 'success', detail: 'Contrato efetivado com sucesso.', life: 2500 });
        await getContratos(currentPage.value);
    } catch (err) {
        toast.add({
            severity: 'error',
            detail: err?.response?.data?.message || 'Erro ao efetivar contrato',
            life: 3500
        });
    }
}

function goToSimulacao() {
    router.push({ name: 'emprestimosSimulacao' });
}

function editContrato(id) {
    router.push({ name: 'emprestimosSimulacao', query: { contratoId: id } });
}

onMounted(() => {
    getContratos();
});
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <div class="flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="m-0">Contratos</h5>
                        <Breadcrumb :model="breadcrumbItems" />
                    </div>
                    <Button
                        label="Simular ou Cadastrar Contrato"
                        icon="pi pi-plus"
                        class="p-button-primary"
                        @click="goToSimulacao"
                    />
                </div>

                <!-- Cards de resumo -->
                <div class="grid mb-4">
                    <div class="col-12 md:col-4">
                        <div class="surface-100 border-round p-3">
                            <p class="text-500 text-sm m-0">Contratos Criados</p>
                            <p class="text-2xl font-bold m-0 mt-1">{{ total }}</p>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="surface-100 border-round p-3">
                            <p class="text-500 text-sm m-0">Em preenchimento</p>
                            <p class="text-2xl font-bold m-0 mt-1">{{ totalEmPreenchimento }}</p>
                        </div>
                    </div>
                    <div class="col-12 md:col-4">
                        <div class="surface-100 border-round p-3">
                            <p class="text-500 text-sm m-0">Efetivados</p>
                            <p class="text-2xl font-bold m-0 mt-1">{{ totalEfetivados }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="p-input-icon-left flex-1" style="min-width: 12rem">
                        <i class="pi pi-search" />
                        <InputText
                            v-model="search"
                            placeholder="Pesquisar"
                            class="w-full"
                            @keyup.enter="getContratos(1)"
                        />
                    </span>
                    <Dropdown
                        v-model="situacaoFilter"
                        :options="situacaoOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Situação"
                        class="w-12rem"
                        @change="getContratos(1)"
                    />
                    <Button label="Filtrar" icon="pi pi-filter" @click="getContratos(1)" />
                </div>

                <DataTable
                    :value="contratos"
                    :loading="loading"
                    :paginator="true"
                    :rows="perPage"
                    :total-records="total"
                    :lazy="true"
                    @page="(e) => getContratos(e.page + 1)"
                    responsive-layout="scroll"
                    class="p-datatable-gridlines"
                >
                    <template #empty>Nenhum contrato encontrado.</template>
                    <template #loading>Carregando contratos...</template>

                    <Column field="numero" header="N°" style="min-width: 8rem">
                        <template #body="{ data }">{{ data.numero }}</template>
                    </Column>
                    <Column field="tipo" header="Tipo" style="min-width: 8rem">
                        <template #body="{ data }">{{ data.tipo }}</template>
                    </Column>
                    <Column field="cliente" header="Cliente" style="min-width: 12rem">
                        <template #body="{ data }">{{ data.cliente }}</template>
                    </Column>
                    <Column field="valor_contrato" header="Valor do Contrato" style="min-width: 10rem">
                        <template #body="{ data }">{{ formatCurrency(data.valor_contrato) }}</template>
                    </Column>
                    <Column field="taxa" header="Taxa" style="min-width: 8rem">
                        <template #body="{ data }">{{ formatPercent(data.taxa) }}</template>
                    </Column>
                    <Column field="data_assinatura" header="Data de Assinatura" style="min-width: 10rem">
                        <template #body="{ data }">{{ formatDate(data.data_assinatura) }}</template>
                    </Column>
                    <Column field="situacao" header="Situação" style="min-width: 10rem">
                        <template #body="{ data }">
                            <span :class="getSituacaoClass(data.situacao)">{{ data.situacao }}</span>
                        </template>
                    </Column>
                    <Column header="" style="min-width: 6rem">
                        <template #body="{ data }">
                            <Button
                                v-if="data.situacao === 'Em preenchimento'"
                                icon="pi pi-check"
                                class="p-button-text p-button-sm p-button-success"
                                v-tooltip.top="'Efetivar'"
                                @click="efetivarContrato(data.id)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                class="p-button-text p-button-sm"
                                v-tooltip.top="'Editar'"
                                @click="editContrato(data.id)"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>
