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
const currentPage = ref(1);
const perPage = ref(10);
const search = ref('');
const assinaturaStatusFilter = ref(null);

const breadcrumbItems = [
    { label: 'Contratos', to: '/contratos' },
    { label: 'Assinaturas' }
];

function statusLabel(s) {
    if (!s) return 'Não iniciado';
    const map = {
        pending_acceptance: 'Pendente de aceite',
        evidence_pending: 'Aguardando evidências',
        evidence_submitted: 'Evidências enviadas',
        otp_pending: 'Aguardando 2FA',
        signed_pending_review: 'Assinado (aguardando revisão)',
        signed: 'Assinado (aprovado)',
        rejected: 'Reprovado',
        resubmit_required: 'Reenvio solicitado'
    };
    return map[s] || s;
}

function formatDateTime(v) {
    if (!v) return '—';
    try {
        const d = new Date(v);
        return d.toLocaleString('pt-BR');
    } catch {
        return String(v);
    }
}

const assinaturaStatusOptions = [
    { label: 'Todos', value: null },
    { label: 'Pendente de aceite', value: 'pending_acceptance' },
    { label: 'Aguardando evidências', value: 'evidence_pending' },
    { label: 'Evidências enviadas', value: 'evidence_submitted' },
    { label: 'Aguardando 2FA', value: 'otp_pending' },
    { label: 'Assinado (aguardando revisão)', value: 'signed_pending_review' },
    { label: 'Assinado (aprovado)', value: 'signed' },
    { label: 'Reprovado', value: 'rejected' },
    { label: 'Reenvio solicitado', value: 'resubmit_required' }
];

async function getAssinaturas(page = 1) {
    loading.value = true;
    currentPage.value = page;
    try {
        const params = {
            page,
            per_page: perPage.value,
            assinatura_only: 1,
            ...(assinaturaStatusFilter.value && { assinatura_status: assinaturaStatusFilter.value }),
            ...(search.value && { q: search.value })
        };
        const res = await service.getAll(params);
        contratos.value = res.data?.data || [];
        const meta = res.data?.meta || {};
        total.value = meta.total ?? contratos.value.length;
    } catch (err) {
        toast.add({
            severity: 'error',
            detail: err?.response?.data?.message || 'Erro ao carregar assinaturas',
            life: 3000
        });
        contratos.value = [];
    } finally {
        loading.value = false;
    }
}

function abrir(contratoId) {
    router.push({ name: 'contratoAssinaturaReview', params: { id: contratoId } });
}

onMounted(() => {
    getAssinaturas();
});
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <div class="flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="m-0">Assinaturas (clientes)</h5>
                        <Breadcrumb :model="breadcrumbItems" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="p-input-icon-left flex-1" style="min-width: 12rem">
                        <i class="pi pi-search" />
                        <InputText
                            v-model="search"
                            placeholder="Pesquisar por cliente ou ID"
                            class="w-full"
                            @keyup.enter="getAssinaturas(1)"
                        />
                    </span>
                    <Dropdown
                        v-model="assinaturaStatusFilter"
                        :options="assinaturaStatusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Status da assinatura"
                        class="w-16rem"
                        @change="getAssinaturas(1)"
                    />
                    <Button label="Filtrar" icon="pi pi-filter" @click="getAssinaturas(1)" />
                </div>

                <DataTable
                    :value="contratos"
                    :loading="loading"
                    :paginator="true"
                    :rows="perPage"
                    :total-records="total"
                    :lazy="true"
                    @page="(e) => getAssinaturas(e.page + 1)"
                    responsive-layout="scroll"
                    class="p-datatable-gridlines"
                >
                    <template #empty>Nenhuma assinatura encontrada.</template>
                    <template #loading>Carregando assinaturas...</template>

                    <Column field="numero" header="N°" style="min-width: 8rem">
                        <template #body="{ data }">{{ data.numero }}</template>
                    </Column>

                    <Column field="cliente" header="Cliente" style="min-width: 14rem">
                        <template #body="{ data }">{{ data.cliente }}</template>
                    </Column>

                    <Column field="situacao" header="Situação do contrato" style="min-width: 12rem">
                        <template #body="{ data }">{{ data.situacao }}</template>
                    </Column>

                    <Column field="assinatura_status" header="Status assinatura" style="min-width: 14rem">
                        <template #body="{ data }">
                            <strong>{{ statusLabel(data.assinatura_status) }}</strong>
                        </template>
                    </Column>

                    <Column field="aceite_at" header="Aceite" style="min-width: 12rem">
                        <template #body="{ data }">{{ formatDateTime(data.aceite_at) }}</template>
                    </Column>

                    <Column field="finalizado_at" header="Finalizado" style="min-width: 12rem">
                        <template #body="{ data }">{{ formatDateTime(data.finalizado_at) }}</template>
                    </Column>

                    <Column header="" style="min-width: 6rem">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-external-link"
                                class="p-button-text p-button-sm"
                                v-tooltip.top="'Abrir acompanhamento'"
                                @click="abrir(data.id)"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>

