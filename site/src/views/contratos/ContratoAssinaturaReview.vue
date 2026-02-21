<script setup>
import { ref, onBeforeUnmount, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import SimulacaoEmprestimoService from '@/service/SimulacaoEmprestimoService';
import Toast from 'primevue/toast';
import axios from 'axios';

const route = useRoute();
const toast = useToast();
const service = new SimulacaoEmprestimoService();
const apiPath = import.meta.env.VITE_APP_BASE_URL;

const loading = ref(false);
const detalhes = ref(null);
const justificativa = ref('');
const revisando = ref(false);

const thumbUrls = ref({});

const previewVisible = ref(false);
const previewLoading = ref(false);
const previewItem = ref(null);
const previewUrl = ref(null);
const previewMime = ref(null);

function podeRevisar() {
    const s = detalhes.value?.assinatura_status;
    return s !== 'signed' && s !== 'rejected';
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
        await preloadThumbs();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e?.response?.data?.message || 'Não foi possível carregar.', life: 4000 });
        detalhes.value = null;
    } finally {
        loading.value = false;
    }
}

function filenameFromContentDisposition(contentDisposition) {
    if (!contentDisposition) return null;
    // Ex: attachment; filename="contrato-7-original.pdf"
    const match = /filename\*=UTF-8''([^;]+)|filename=\"?([^\";]+)\"?/i.exec(String(contentDisposition));
    const raw = match?.[1] || match?.[2];
    if (!raw) return null;
    try {
        return decodeURIComponent(raw);
    } catch {
        return raw;
    }
}

async function downloadAuth(url, fallbackName = 'arquivo', openInNewTab = true) {
    try {
        const res = await axios.get(url, { responseType: 'blob' });
        const contentType = res?.headers?.['content-type'] || 'application/octet-stream';
        const cd = res?.headers?.['content-disposition'];
        const filename = filenameFromContentDisposition(cd) || fallbackName;

        const blob = new Blob([res.data], { type: contentType });
        const objectUrl = URL.createObjectURL(blob);

        if (openInNewTab) {
            window.open(objectUrl, '_blank');
        } else {
            const a = document.createElement('a');
            a.href = objectUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
        }

        setTimeout(() => URL.revokeObjectURL(objectUrl), 30_000);
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Falha ao baixar arquivo.';
        toast.add({ severity: 'error', summary: 'Erro', detail: msg, life: 4500 });
    }
}

function evidenciaUrl(evidenciaId) {
    return `${apiPath}/contratos/${detalhes.value?.id}/assinatura/evidencias/${evidenciaId}`;
}

function isImageEvidence(ev) {
    const mime = String(ev?.mime || '');
    if (mime.startsWith('image/')) return true;
    if (mime.startsWith('video/')) return false;
    return ['doc_frente', 'doc_verso', 'selfie'].includes(String(ev?.tipo || '').toLowerCase());
}

function isVideoEvidence(ev) {
    const mime = String(ev?.mime || '');
    if (mime.startsWith('video/')) return true;
    return String(ev?.tipo || '').toLowerCase() === 'video';
}

async function getEvidenceBlobUrl(ev) {
    const url = evidenciaUrl(ev.id);
    const res = await axios.get(url, { responseType: 'blob' });
    const contentType = res?.headers?.['content-type'] || ev?.mime || 'application/octet-stream';
    const blob = new Blob([res.data], { type: contentType });
    return { objectUrl: URL.createObjectURL(blob), contentType };
}

async function preloadThumbs() {
    const evs = detalhes.value?.evidencias || [];
    for (const ev of evs) {
        if (!ev?.id) continue;
        if (!isImageEvidence(ev)) continue;
        if (thumbUrls.value[ev.id]) continue;
        try {
            const { objectUrl } = await getEvidenceBlobUrl(ev);
            thumbUrls.value = { ...thumbUrls.value, [ev.id]: objectUrl };
        } catch {
            // deixa sem miniatura
        }
    }
}

async function abrirPreview(ev) {
    if (!ev?.id) return;
    previewVisible.value = true;
    previewLoading.value = true;
    previewItem.value = ev;
    previewMime.value = ev?.mime || null;

    try {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }
        const { objectUrl, contentType } = await getEvidenceBlobUrl(ev);
        previewUrl.value = objectUrl;
        previewMime.value = contentType || previewMime.value;
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Falha ao abrir evidência.',
            life: 4500,
        });
    } finally {
        previewLoading.value = false;
    }
}

function fecharPreview() {
    previewVisible.value = false;
}

function baixarEvidencia(ev) {
    if (!ev?.id) return;
    downloadAuth(
        evidenciaUrl(ev.id),
        `contrato-${detalhes.value?.id}-evidencia-${ev.tipo || 'arquivo'}-${ev.id}`,
        false
    );
}

watch(previewVisible, (v) => {
    if (!v && previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
        previewItem.value = null;
        previewMime.value = null;
    }
});

onBeforeUnmount(() => {
    try {
        Object.values(thumbUrls.value || {}).forEach((u) => {
            if (u) URL.revokeObjectURL(u);
        });
    } catch {}
    if (previewUrl.value) {
        try { URL.revokeObjectURL(previewUrl.value); } catch {}
    }
});

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
                                @click="downloadAuth(`${apiPath}/contratos/${detalhes.id}/assinatura/pdf-original`, `contrato-${detalhes.id}-original.pdf`, true)"
                            />
                            <Button
                                label="Baixar PDF final"
                                icon="pi pi-file-pdf"
                                class="p-button-outlined p-button-success"
                                :disabled="!detalhes.pdf_final_path"
                                @click="downloadAuth(`${apiPath}/contratos/${detalhes.id}/assinatura/pdf-final`, `contrato-${detalhes.id}-assinado.pdf`, true)"
                            />
                        </div>

                        <div v-if="podeRevisar()">
                            <h6 class="mb-2">Revisão (analista)</h6>
                            <Textarea v-model="justificativa" rows="3" class="w-full mb-2" placeholder="Justificativa (opcional)" />
                            <div class="flex gap-2">
                                <Button label="Aprovar" icon="pi pi-check" class="p-button-success" :loading="revisando" @click="revisar('aprovar')" />
                                <Button label="Reprovar" icon="pi pi-times" class="p-button-danger" :loading="revisando" @click="revisar('reprovar')" />
                                <Button label="Solicitar reenvio" icon="pi pi-replay" class="p-button-warning" :loading="revisando" @click="revisar('solicitar_reenvio')" />
                            </div>
                        </div>
                    </div>

                    <div class="col-12 lg:col-6">
                        <h6 class="mb-2">Evidências</h6>

                        <div v-if="(detalhes.evidencias || []).length" class="evidence-grid mb-3">
                            <div v-for="ev in (detalhes.evidencias || [])" :key="ev.id" class="evidence-item">
                                <button class="evidence-thumb" type="button" @click="abrirPreview(ev)">
                                    <img v-if="thumbUrls[ev.id]" :src="thumbUrls[ev.id]" class="evidence-img" alt="Evidência" />
                                    <div v-else class="evidence-placeholder">
                                        <i :class="isVideoEvidence(ev) ? 'pi pi-video' : 'pi pi-image'" style="font-size: 1.25rem"></i>
                                        <span class="evidence-placeholder-text">{{ ev.tipo }}</span>
                                    </div>
                                </button>
                                <div class="evidence-meta">
                                    <span class="evidence-label">{{ ev.tipo }}</span>
                                    <Button
                                        icon="pi pi-download"
                                        class="p-button-text p-button-sm"
                                        v-tooltip.top="'Baixar'"
                                        @click="baixarEvidencia(ev)"
                                    />
                                </div>
                            </div>
                        </div>

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
                                        @click="downloadAuth(`${apiPath}/contratos/${detalhes.id}/assinatura/evidencias/${data.id}`, `contrato-${detalhes.id}-evidencia-${data.tipo || 'arquivo'}-${data.id}`, false)"
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

    <Dialog
        v-model:visible="previewVisible"
        modal
        :header="previewItem ? `Evidência: ${previewItem.tipo} (#${previewItem.id})` : 'Evidência'"
        :style="{ width: '70vw', maxWidth: '960px' }"
        @hide="fecharPreview"
    >
        <div v-if="previewLoading" class="p-3 text-500">Carregando...</div>
        <div v-else-if="previewUrl" class="preview-body">
            <img
                v-if="String(previewMime || '').startsWith('image/') || isImageEvidence(previewItem)"
                :src="previewUrl"
                class="preview-media"
                alt="Pré-visualização"
            />
            <video
                v-else-if="String(previewMime || '').startsWith('video/') || isVideoEvidence(previewItem)"
                :src="previewUrl"
                class="preview-media"
                controls
            />
            <div v-else class="p-3 text-500">Formato não suportado para pré-visualização.</div>
        </div>
        <div v-else class="p-3 text-500">Sem conteúdo.</div>

        <div class="flex justify-content-end gap-2 mt-3">
            <Button
                v-if="previewItem"
                label="Baixar"
                icon="pi pi-download"
                class="p-button-outlined"
                @click="baixarEvidencia(previewItem)"
            />
            <Button label="Fechar" icon="pi pi-times" class="p-button-text" @click="fecharPreview" />
        </div>
    </Dialog>
</template>

<style scoped>
.break-all {
    word-break: break-all;
}

.evidence-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
}

.evidence-item {
    border: 1px solid var(--surface-border);
    border-radius: 10px;
    overflow: hidden;
    background: var(--surface-card);
}

.evidence-thumb {
    width: 100%;
    height: 120px;
    padding: 0;
    border: none;
    background: #111;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.evidence-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.evidence-placeholder {
    color: #fff;
    opacity: 0.9;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    text-align: center;
}

.evidence-placeholder-text {
    font-size: 0.8rem;
}

.evidence-meta {
    padding: 0.5rem 0.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.evidence-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-color);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preview-body {
    display: flex;
    justify-content: center;
    align-items: center;
}

.preview-media {
    width: 100%;
    max-height: 70vh;
    object-fit: contain;
    background: #000;
    border-radius: 8px;
}
</style>

