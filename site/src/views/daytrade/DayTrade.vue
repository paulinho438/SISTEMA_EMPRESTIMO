<template>
    <div class="daytrade-wrap">
        <div class="card">
            <h1>Day Trade — Acompanhamento da Meta Diária</h1>

            <div class="grid">
                <div>
                    <label>Capital inicial</label>
                    <div class="row">
                        <input class="prefix" value="R$" disabled />
                        <input v-model="form.capitalInicial" inputmode="decimal" placeholder="Ex: 1000,00" />
                    </div>
                </div>
                <div>
                    <label>Meta diária (%)</label>
                    <div class="row">
                        <input class="prefix" value="%" disabled />
                        <input v-model="form.metaDiaria" inputmode="decimal" placeholder="Ex: 15,9" />
                    </div>
                </div>
                <div>
                    <label>Dias (linha do tempo)</label>
                    <input v-model="form.dias" inputmode="numeric" placeholder="Ex: 30" />
                </div>
            </div>

            <div class="grid" style="margin-top: 12px">
                <div>
                    <label>Como lançar resultado diário</label>
                    <select v-model="form.modoLancamento">
                        <option value="brl">Em R$ (ex: 150,00 ou -80,00)</option>
                        <option value="pct">Em % (ex: 2,5 ou -1,2)</option>
                    </select>
                </div>
                <div>
                    <label>Regra do dia (se lançar em %)</label>
                    <select v-model="form.regraDia">
                        <option value="sobre_saldo">Resultado do dia sobre o saldo do dia anterior</option>
                        <option value="sobre_inicial">Resultado do dia sobre o capital inicial</option>
                    </select>
                </div>
                <div>
                    <label>Hoje (dia que quero comparar)</label>
                    <input v-model="form.diaAtual" inputmode="numeric" placeholder="Ex: 10" />
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" @click="gerarTabela">Gerar tabela</button>
                <button class="btn btn-ghost" @click="recalcular">Recalcular</button>
                <button class="btn btn-ghost" @click="zerarLancamentos">Zerar lançamentos</button>
                <button class="btn btn-save" @click="salvar" :disabled="salvando">
                    {{ salvando ? 'Salvando...' : 'Salvar tudo' }}
                </button>
                <span class="pill pill-status">{{ pillStatus }}</span>
            </div>

            <div class="hint">
                Meta composta diária: <code>Meta(dia) = CapitalInicial × (1 + meta)^dia</code>.
                Clique em <b>Salvar tudo</b> para guardar seus dados no servidor.
            </div>
        </div>

        <div v-show="resultadoVisivel" class="card resultado-card">
            <div class="section-title">Resultado (referente ao "Hoje")</div>

            <div class="kpis">
                <div class="kpi big">
                    <div class="t">Saldo real (Hoje)</div>
                    <div class="v" :class="kpiDiffClass">{{ kpiReal }}</div>
                    <div class="s">{{ kpiRealSub }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Saldo meta (Hoje)</div>
                    <div class="v">{{ kpiMeta }}</div>
                    <div class="s">Meta composta diária</div>
                </div>
                <div class="kpi">
                    <div class="t">Quanto preciso fazer HOJE</div>
                    <div class="v" :class="kpiPrecisoClass">{{ kpiPrecisoHoje }}</div>
                    <div class="s">{{ kpiPrecisoHojeSub }}</div>
                </div>
                <div class="kpi">
                    <div class="t">Status vs meta (Hoje)</div>
                    <div class="v" :class="kpiDiffClass">{{ kpiDiff }}</div>
                    <div class="s">{{ kpiDiffSub }}</div>
                </div>
            </div>

            <div class="section-title" style="margin-top: 18px">Gráfico</div>
            <div class="legend">
                <span><span class="dot" style="background: #111827"></span>Saldo real</span>
                <span><span class="dot" style="background: #b91c1c"></span>Saldo meta</span>
            </div>
            <div class="chart-wrap">
                <canvas ref="chartCanvas" width="1100" height="340"></canvas>
            </div>

            <div class="section-title" style="margin-top: 18px">Tabela diária (lançar ganho/perda)</div>
            <div class="table-wrap">
                <div class="scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Dia</th>
                                <th>Resultado do dia</th>
                                <th>Saldo real</th>
                                <th>Saldo meta</th>
                                <th>Preciso no dia (p/ bater meta)</th>
                                <th>Dif. (R$)</th>
                                <th>Dif. (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in serie" :key="r.dia">
                                <td><b>{{ r.dia }}</b></td>
                                <td>
                                    <input
                                        class="mini-input"
                                        :value="formatLancamento(r.lancamento)"
                                        @input="onLancamentoInput($event, r.dia)"
                                        :placeholder="form.modoLancamento === 'brl' ? 'Ex: 150,00' : 'Ex: 2,5'"
                                    />
                                </td>
                                <td>{{ formatBRL(r.saldoReal) }}</td>
                                <td>{{ formatBRL(r.saldoMeta) }}</td>
                                <td><b>{{ precisoTxt(r) }}</b></td>
                                <td :class="r.diff >= 0 ? 'pos' : 'neg'">{{ formatBRL(r.diff) }}</td>
                                <td :class="r.diff >= 0 ? 'pos' : 'neg'">{{ formatPct(r.diffPct) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hint">
                "Preciso no dia" = quanto você teria que ganhar/perder <b>naquele dia</b> para fechar exatamente na meta daquele dia.
                (considera o saldo do dia anterior como base).
            </div>
        </div>
    </div>
    <Toast />
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { ToastSeverity } from 'primevue/api';
import DaytradeService from '@/service/DaytradeService';

const toast = useToast();

const daytradeService = new DaytradeService();
const chartCanvas = ref(null);

const form = reactive({
    capitalInicial: '100,00',
    metaDiaria: '15,90',
    dias: '50',
    modoLancamento: 'pct',
    regraDia: 'sobre_inicial',
    diaAtual: '1',
});

const model = reactive({
    capitalInicial: 100,
    metaDiariaPct: 15.9,
    dias: 50,
    modoLancamento: 'pct',
    regraDia: 'sobre_inicial',
    diaAtual: 1,
    lancamentos: [],
});

const salvando = ref(false);
const resultadoVisivel = ref(false);

// Utils
function parseBRLNumber(str) {
    if (str == null) return 0;
    const s = String(str)
        .trim()
        .replace(/\./g, '')
        .replace(',', '.');
    const n = Number(s);
    return Number.isFinite(n) ? n : 0;
}

function formatBRL(n) {
    return (Number(n) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function formatPct(n) {
    const v = Number(n) || 0;
    return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
}

function formatLancamento(v) {
    const n = Number(v) || 0;
    return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function clampInt(n, min, max) {
    n = Math.trunc(Number(n));
    if (!Number.isFinite(n)) n = min;
    return Math.max(min, Math.min(max, n));
}

// Core
function saldoMeta(capitalInicial, metaPct, dia) {
    const m = metaPct / 100;
    return capitalInicial * Math.pow(1 + m, dia);
}

function recalcularSeries() {
    const cap0 = model.capitalInicial;
    const dias = model.dias;
    const modo = model.modoLancamento;
    const regra = model.regraDia;

    const serie = [];
    let saldo = cap0;
    let saldoAntesDia = cap0;

    for (let d = 0; d < dias; d++) {
        const meta = saldoMeta(cap0, model.metaDiariaPct, d);
        const lanc = Number(model.lancamentos[d] ?? 0) || 0;

        let pnlBRL = 0;
        if (modo === 'brl') {
            pnlBRL = lanc;
        } else {
            const base = regra === 'sobre_inicial' ? cap0 : saldo;
            pnlBRL = base * (lanc / 100);
        }

        saldoAntesDia = saldo;
        saldo = saldo + pnlBRL;

        const diff = saldo - meta;
        const diffPct = meta !== 0 ? (diff / meta) * 100 : 0;
        const precisoBRL = meta - saldoAntesDia;

        let precisoEmEntrada = null;
        if (modo === 'pct') {
            const basePreciso = regra === 'sobre_inicial' ? cap0 : saldoAntesDia;
            precisoEmEntrada = basePreciso !== 0 ? (precisoBRL / basePreciso) * 100 : 0;
        }

        serie.push({
            dia: d,
            lancamento: lanc,
            pnlBRL,
            saldoReal: saldo,
            saldoMeta: meta,
            diff,
            diffPct,
            saldoAntesDia,
            precisoBRL,
            precisoEmEntrada,
        });
    }

    return serie;
}

const serie = ref([]);

function applyInputsToModel() {
    model.capitalInicial = Math.max(0, parseBRLNumber(form.capitalInicial));
    model.metaDiariaPct = parseBRLNumber(form.metaDiaria);
    model.dias = clampInt(form.dias, 1, 3650);
    model.modoLancamento = form.modoLancamento;
    model.regraDia = form.regraDia;
    model.diaAtual = clampInt(form.diaAtual, 0, model.dias - 1);

    if (!Array.isArray(model.lancamentos)) model.lancamentos = [];
    if (model.lancamentos.length !== model.dias) {
        model.lancamentos = Array.from({ length: model.dias }, (_, i) => model.lancamentos[i] ?? 0);
    }
}

const pillStatus = computed(() => `Meta: ${formatPct(model.metaDiariaPct)} ao dia`);

const d = computed(() => clampInt(model.diaAtual, 0, model.dias - 1));
const r = computed(() => serie.value[d.value] || {});

const kpiReal = computed(() => formatBRL(r.value.saldoReal ?? 0));
const kpiMeta = computed(() => formatBRL(r.value.saldoMeta ?? 0));
const kpiPrecisoHoje = computed(() =>
    form.modoLancamento === 'brl' ? formatBRL(r.value.precisoBRL ?? 0) : `${formatPct(r.value.precisoEmEntrada ?? 0)} (≈ ${formatBRL(r.value.precisoBRL ?? 0)})`
);
const kpiPrecisoHojeSub = computed(() => {
    const precisa = r.value.precisoBRL ?? 0;
    if (precisa <= 0) return 'Você já está acima/na meta nesse dia.';
    return `Você precisa aproximadamente ${formatPct(r.value.precisoEmEntrada ?? 0)} hoje (≈ ${formatBRL(precisa)}) para fechar na meta do dia ${d.value}.`;
});
const kpiDiff = computed(() => `${formatBRL(r.value.diff ?? 0)} (${formatPct(r.value.diffPct ?? 0)})`);
const kpiDiffSub = computed(() =>
    (r.value.diff ?? 0) >= 0 ? `Você está acima da meta no dia ${d.value}` : `Você está abaixo da meta no dia ${d.value}`
);
const kpiRealSub = computed(() => {
    if (form.modoLancamento === 'brl') return `Resultado lançado no dia ${d.value}: ${formatBRL(r.value.pnlBRL ?? 0)}`;
    return `Resultado lançado no dia ${d.value}: ${formatPct(r.value.lancamento ?? 0)} (≈ ${formatBRL(r.value.pnlBRL ?? 0)})`;
});

const kpiDiffClass = computed(() => ((r.value.diff ?? 0) >= 0 ? 'pos' : 'neg'));
const kpiPrecisoClass = computed(() => ((r.value.precisoBRL ?? 0) <= 0 ? 'pos' : 'neg'));

function precisoTxt(r) {
    return form.modoLancamento === 'brl' ? formatBRL(r.precisoBRL) : `${formatPct(r.precisoEmEntrada)} (≈ ${formatBRL(r.precisoBRL)})`;
}

function drawChart() {
    const canvas = chartCanvas.value;
    if (!canvas || serie.value.length === 0) return;

    const ctx = canvas.getContext('2d');
    const w = canvas.width,
        h = canvas.height;

    ctx.clearRect(0, 0, w, h);

    const padL = 66,
        padR = 16,
        padT = 16,
        padB = 38;
    const iw = w - padL - padR;
    const ih = h - padT - padB;

    const ysReal = serie.value.map((r) => r.saldoReal);
    const ysMeta = serie.value.map((r) => r.saldoMeta);

    const maxY = Math.max(1, ...ysReal, ...ysMeta);
    const minY = Math.min(0, ...ysReal, ...ysMeta);
    const spanY = Math.max(1e-9, maxY - minY);

    function toXY(i, y) {
        const nx = i / Math.max(1, serie.value.length - 1);
        const ny = (y - minY) / spanY;
        return { x: padL + nx * iw, y: padT + (1 - ny) * ih };
    }

    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    const gridLines = 6;
    for (let i = 0; i <= gridLines; i++) {
        const yy = padT + (ih * i) / gridLines;
        ctx.beginPath();
        ctx.moveTo(padL, yy);
        ctx.lineTo(w - padR, yy);
        ctx.stroke();
    }

    ctx.strokeStyle = '#111827';
    ctx.lineWidth = 1.2;
    ctx.beginPath();
    ctx.moveTo(padL, padT);
    ctx.lineTo(padL, h - padB);
    ctx.lineTo(w - padR, h - padB);
    ctx.stroke();

    ctx.fillStyle = '#6b7280';
    ctx.font = '12px system-ui';
    for (let i = 0; i <= gridLines; i++) {
        const v = minY + spanY * (1 - i / gridLines);
        const yy = padT + (ih * i) / gridLines;
        ctx.fillText(v.toLocaleString('pt-BR', { maximumFractionDigits: 0 }), 10, yy + 4);
    }

    function drawLine(values, stroke) {
        ctx.strokeStyle = stroke;
        ctx.lineWidth = 2.4;
        ctx.beginPath();
        const p0 = toXY(0, values[0]);
        ctx.moveTo(p0.x, p0.y);
        for (let i = 1; i < values.length; i++) {
            const p = toXY(i, values[i]);
            ctx.lineTo(p.x, p.y);
        }
        ctx.stroke();
        for (let i = 0; i < values.length; i++) {
            const p = toXY(i, values[i]);
            ctx.beginPath();
            ctx.fillStyle = '#fff';
            ctx.arc(p.x, p.y, 4.4, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = stroke;
            ctx.lineWidth = 2;
            ctx.stroke();
        }
    }

    drawLine(ysMeta, '#b91c1c');
    drawLine(ysReal, '#111827');

    const diaAtual = clampInt(model.diaAtual, 0, model.dias - 1);
    const pReal = toXY(diaAtual, ysReal[diaAtual]);
    const pMeta = toXY(diaAtual, ysMeta[diaAtual]);

    ctx.strokeStyle = 'rgba(17,24,39,.25)';
    ctx.lineWidth = 1.2;
    ctx.beginPath();
    ctx.moveTo(pReal.x, padT);
    ctx.lineTo(pReal.x, h - padB);
    ctx.stroke();

    ctx.beginPath();
    ctx.fillStyle = '#111827';
    ctx.arc(pReal.x, pReal.y, 6.2, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.fillStyle = '#b91c1c';
    ctx.arc(pMeta.x, pMeta.y, 6.2, 0, Math.PI * 2);
    ctx.fill();

    ctx.fillStyle = '#6b7280';
    ctx.font = '12px system-ui';
    ctx.fillText('0', padL, h - 12);
    ctx.fillText(String(serie.value.length - 1), w - padR - 12, h - 12);
}

function atualizarTudo() {
    applyInputsToModel();
    serie.value = recalcularSeries();
    resultadoVisivel.value = true;
    drawChart();
}

function gerarTabela() {
    atualizarTudo();
    document.querySelector('.resultado-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function recalcular() {
    atualizarTudo();
}

function zerarLancamentos() {
    applyInputsToModel();
    model.lancamentos = Array.from({ length: model.dias }, () => 0);
    atualizarTudo();
}

function onLancamentoInput(event, dia) {
    const raw = String(event.target.value || '').trim();
    const val = parseBRLNumber(raw);
    model.lancamentos[dia] = val;
    atualizarTudo();
}

function getPayload() {
    applyInputsToModel();
    return {
        capitalInicial: model.capitalInicial,
        metaDiariaPct: model.metaDiariaPct,
        dias: model.dias,
        modoLancamento: model.modoLancamento,
        regraDia: model.regraDia,
        diaAtual: model.diaAtual,
        lancamentos: model.lancamentos,
    };
}

async function salvar() {
    salvando.value = true;
    try {
        const payload = getPayload();
        await daytradeService.save(payload);
        atualizarTudo();
        toast.add({ severity: ToastSeverity.SUCCESS, detail: 'Dados salvos com sucesso!', life: 3000 });
    } catch (err) {
        console.error(err);
        toast.add({ severity: ToastSeverity.ERROR, detail: err?.response?.data?.error || 'Erro ao salvar. Tente novamente.', life: 5000 });
    } finally {
        salvando.value = false;
    }
}

async function carregarDados() {
    try {
        const { data } = await daytradeService.get();
        if (data) {
            model.capitalInicial = data.capitalInicial ?? 100;
            model.metaDiariaPct = data.metaDiariaPct ?? 15.9;
            model.dias = data.dias ?? 50;
            model.modoLancamento = data.modoLancamento ?? 'pct';
            model.regraDia = data.regraDia ?? 'sobre_inicial';
            model.diaAtual = data.diaAtual ?? 1;
            model.lancamentos = Array.isArray(data.lancamentos) ? [...data.lancamentos] : [];

            form.capitalInicial = (model.capitalInicial ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            form.metaDiaria = (model.metaDiariaPct ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            form.dias = String(model.dias ?? 50);
            form.modoLancamento = model.modoLancamento ?? 'brl';
            form.regraDia = model.regraDia ?? 'sobre_saldo';
            form.diaAtual = String(model.diaAtual ?? 0);
        }
    } catch (err) {
        // Sem dados salvos, usa defaults
        model.lancamentos = Array.from({ length: model.dias }, () => 0);
    }
    applyInputsToModel();
    atualizarTudo();
}

onMounted(() => carregarDados());
</script>

<style scoped>
.daytrade-wrap {
    max-width: 1150px;
    margin: 26px auto;
    padding: 0 16px 40px;
}

.daytrade-wrap :root {
    --bg: #f6f7fb;
    --card: #ffffff;
    --text: #111827;
    --muted: #6b7280;
    --primary: #8b0f0f;
    --border: #e5e7eb;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    --radius: 14px;
    --good: #065f46;
    --bad: #991b1b;
}

.card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    padding: 18px;
    margin-bottom: 16px;
}

h1 {
    margin: 0 0 14px;
    font-size: 24px;
    color: #8b0f0f;
    letter-spacing: 0.2px;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
}

@media (max-width: 980px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

label {
    display: block;
    font-size: 13px;
    color: var(--text-color-secondary);
    margin-bottom: 6px;
}

input,
select,
button {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--surface-border);
    border-radius: 10px;
    font-size: 14px;
    outline: none;
    background: var(--surface-ground);
}

input:focus,
select:focus {
    border-color: #d1d5db;
    box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08);
}

.row {
    display: flex;
    gap: 10px;
    align-items: stretch;
}

.prefix {
    width: 70px;
    text-align: center;
    color: var(--text-color-secondary);
    background: var(--surface-100);
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    margin-top: 10px;
}

.btn {
    width: auto;
    padding: 12px 18px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 800;
}

.btn-primary {
    background: #8b0f0f;
    border: 1px solid #8b0f0f;
    color: #fff;
}

.btn-ghost {
    background: var(--surface-ground);
    border: 1px solid var(--surface-border);
    color: var(--text-color);
}

.btn-save {
    background: #065f46;
    border: 1px solid #065f46;
    color: #fff;
}

.btn-save:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(185, 28, 28, 0.08);
    color: #b91c1c;
    font-weight: 900;
    font-size: 12px;
}

.pill-status {
    background: rgba(185, 28, 28, 0.08);
    color: #b91c1c;
}

.hint {
    color: var(--text-color-secondary);
    font-size: 13px;
    margin-top: 8px;
    line-height: 1.35;
}

code {
    background: var(--surface-100);
    padding: 2px 6px;
    border-radius: 8px;
}

.section-title {
    margin: 14px 0 10px;
    font-size: 18px;
    color: #8b0f0f;
    font-weight: 900;
}

.kpis {
    display: grid;
    grid-template-columns: 1.2fr 1fr 1fr 1fr;
    gap: 12px;
    margin-top: 12px;
}

@media (max-width: 980px) {
    .kpis {
        grid-template-columns: 1fr;
    }
}

.kpi {
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    padding: 14px;
    background: var(--surface-ground);
}

.kpi.big {
    background: #8b0f0f;
    color: #fff;
    border-color: #8b0f0f;
}

.kpi .t {
    font-size: 12px;
    color: var(--text-color-secondary);
    margin-bottom: 6px;
}

.kpi.big .t {
    color: rgba(255, 255, 255, 0.85);
}

.kpi .v {
    font-size: 20px;
    font-weight: 900;
}

.kpi .s {
    font-size: 12px;
    color: var(--text-color-secondary);
    margin-top: 6px;
    line-height: 1.3;
}

.kpi.big .s {
    color: rgba(255, 255, 255, 0.85);
}

.pos {
    color: #065f46;
    font-weight: 900;
}

.neg {
    color: #991b1b;
    font-weight: 900;
}

.legend {
    display: flex;
    gap: 14px;
    align-items: center;
    justify-content: center;
    margin: 8px 0 2px;
    color: var(--text-color-secondary);
    font-size: 13px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    display: inline-block;
    margin-right: 6px;
}

.chart-wrap {
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    padding: 12px;
    overflow: hidden;
    background: var(--surface-ground);
}

.table-wrap {
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    overflow: hidden;
    background: var(--surface-ground);
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead th {
    text-align: left;
    background: var(--surface-100);
    padding: 10px 12px;
    border-bottom: 1px solid var(--surface-border);
    font-weight: 900;
    white-space: nowrap;
}

tbody td {
    padding: 8px 12px;
    border-bottom: 1px solid var(--surface-border);
    vertical-align: middle;
    white-space: nowrap;
}

tbody tr:last-child td {
    border-bottom: none;
}

.scroll {
    max-height: 420px;
    overflow: auto;
}

.mini-input {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid var(--surface-border);
    width: 140px;
}

.mini-input:focus {
    border-color: #d1d5db;
    box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.08);
}
</style>
