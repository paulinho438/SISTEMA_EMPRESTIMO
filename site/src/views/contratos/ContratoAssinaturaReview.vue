<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Textarea from 'primevue/textarea';
import SimulacaoEmprestimoService from '@/service/SimulacaoEmprestimoService';
import Toast from 'primevue/toast';

const route = useRoute();
const toast = useToast();
const service = new SimulacaoEmprestimoService();
const apiPath = import.meta.env.VITE_APP_BASE_URL;

const loading = ref(false);
const detalhes = ref(null);
const justificativa = ref('');
const revisando = ref(false);

function formatDateTime(v) {
    if (!v) return '—';
    try {
        const d = new Date(v);
        return d.toLocaleString('pt-BR');
    } catch {
        return String(v);
    }
}

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
        resubmit_required: 'Reenvio solicitado',
    };
    return map[s] || s;
}

async function carregar() {
    loading.value = true;
    try {
        const id = route.params.id;
        const res = await service.getAssinaturaDetalhes(id);
        detalhes.value = res.data || null;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e?.response?.data?.message || 'Não foi possível carregar.', life: 4000 });
        detalhes.value = null;
    } finally {
        loading.value = false;
    }
}

function download(url) {
    window.open(url, '_blank');
}

async function revisar(acao) {
    if (!detalhes.value?.id) return;
    revisando.value = true;
    try {
        await service.revisarAssinatura(detalhes.value.id, { acao, justificativa: justificativa.value || null });
        toast.add({ severity: 'success', summary: 'Ok', detail: 'Revisão salva.', life: 2500 });
        justificativa.value = '';
        await carregar();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e?.response?.data?.message || 'Falha ao salvar revisão.', life: 4000 });
    } finally {
        revisando.value = false;
    }
}

onMounted(() => {
    carregar();
});
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <div class="flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="m-0">Assinatura do Contrato #{{ route.params.id }}</h5>
                        <small class="text-500">Status: <strong>{{ statusLabel(detalhes?.assinatura_status) }}</strong></small>
                    </div>
                    <Button label="Atualizar" icon="pi pi-refresh" class="p-button-outlined" :loading="loading" @click="carregar" />
                </div>

                <div v-if="!detalhes" class="p-3 text-500">
                    {{ loading ? 'Carregando...' : 'Sem dados.' }}
                </div>

                <div v-else class="grid">
                    <div class="col-12 lg:col-6">
                        <div class="surface-100 border-round p-3 mb-3">
                            <p class="m-0 text-sm"><strong>Aceite em:</strong> {{ formatDateTime(detalhes.aceite_at) }}</p>
                            <p class="m-0 text-sm"><strong>Finalizado em:</strong> {{ formatDateTime(detalhes.finalizado_at) }}</p>
                            <p class="m-0 text-sm"><strong>Hash original:</strong> <span class="break-all">{{ detalhes.pdf_original_sha256 || '—' }}</span></p>
                            <p class="m-0 text-sm"><strong>Hash final:</strong> <span class="break-all">{{ detalhes.pdf_final_sha256 || '—' }}</span></p>
                        </div>

                        <div class="flex flex-wrap gap-2 mb-3">
                            <Button
                                label="Baixar PDF original"
                                icon="pi pi-file-pdf"
                                class="p-button-outlined"
                                :disabled="!detalhes.pdf_original_path"
                                @click="download(`${apiPath}/contratos/${detalhes.id}/assinatura/pdf-original`)"
                            />
                            <Button
                                label="Baixar PDF final"
                                icon="pi pi-file-pdf"
                                class="p-button-outlined p-button-success"
                                :disabled="!detalhes.pdf_final_path"
                                @click="download(`${apiPath}/contratos/${detalhes.id}/assinatura/pdf-final`)"
                            />
                        </div>

                        <h6 class="mb-2">Revisão (analista)</h6>
                        <Textarea v-model="justificativa" rows="3" class="w-full mb-2" placeholder="Justificativa (opcional)" />
                        <div class="flex gap-2">
                            <Button label="Aprovar" icon="pi pi-check" class="p-button-success" :loading="revisando" @click="revisar('aprovar')" />
                            <Button label="Reprovar" icon="pi pi-times" class="p-button-danger" :loading="revisando" @click="revisar('reprovar')" />
                            <Button label="Solicitar reenvio" icon="pi pi-replay" class="p-button-warning" :loading="revisando" @click="revisar('solicitar_reenvio')" />
                        </div>
                    </div>

                    <div class="col-12 lg:col-6">
                        <h6 class="mb-2">Evidências</h6>
                        <DataTable :value="detalhes.evidencias || []" responsive-layout="scroll" class="p-datatable-sm mb-4">
                            <template #empty>Nenhuma evidência.</template>
                            <Column field="id" header="ID" style="width: 5rem" />
                            <Column field="tipo" header="Tipo" style="min-width: 8rem" />
                            <Column field="sha256" header="SHA-256" style="min-width: 14rem">
                                <template #body="{ data }">
                                    <span class="break-all">{{ data.sha256 }}</span>
                                </template>
                            </Column>
                            <Column header="" style="width: 6rem">
                                <template #body="{ data }">
                                    <Button
                                        icon="pi pi-download"
                                        class="p-button-text p-button-sm"
                                        v-tooltip.top="'Baixar'"
                                        @click="download(`${apiPath}/contratos/${detalhes.id}/assinatura/evidencias/${data.id}`)"
                                    />
                                </template>
                            </Column>
                        </DataTable>

                        <h6 class="mb-2">Linha do tempo</h6>
                        <DataTable :value="detalhes.eventos || []" responsive-layout="scroll" class="p-datatable-sm">
                            <template #empty>Nenhum evento.</template>
                            <Column field="created_at" header="Data/Hora" style="min-width: 10rem">
                                <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
                            </Column>
                            <Column field="evento_tipo" header="Evento" style="min-width: 12rem" />
                            <Column field="ator_tipo" header="Ator" style="width: 6rem" />
                            <Column field="ip" header="IP" style="min-width: 8rem" />
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.break-all {
    word-break: break-all;
}
</style>

