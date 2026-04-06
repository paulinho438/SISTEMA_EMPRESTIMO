<script>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import EmprestimoService from '../../service/EmprestimoService';
import FullScreenLoading from '@/components/FullScreenLoading.vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import { useConfirm } from 'primevue/useconfirm';

import moment from 'moment';

/**
 * Landing "Histórico de Parcelas" — botões PIX.
 * Fonte de dados: EmprestimoService.infoEmprestimoFront → POST /parcela/:id/infoemprestimofront
 * (EmprestimoController::infoEmprestimoFront).
 *
 * - Valor pendente do dia: renderiza se `emprestimo.pagamentosaldopendente` existir (a API pode criar
 *   o registro em `pagamento_saldo_pendente` quando faltava, desde que haja parcela aberta e valor > 0).
 * - Quitar empréstimo: renderiza se `emprestimo.quitacao` existir e `quitacao.saldo` > 0 (total em aberto).
 */
export default {
    components: {
        FullScreenLoading, // Registra o componente
    },
    data() {
        return {
            router: useRouter(),
            route: useRoute(),
            loading: ref(false),
            emprestimoService: new EmprestimoService(),
            id_pedido: ref(this.$route.params.id_pedido),
            informacoes: ref(null),
            products: ref([]),
            display: ref(false),
            sliderValue: ref(1),
            min: ref(0),
            max: ref(1000),
            toast: useToast(),
            loadingPix: ref(false),
            /** Quando XGate: { tipo: 'saldoPendente'|'quitacao'|'parcela', id } do botão que está gerando o QR */
            loadingPixContext: ref(null),
            valorPendenteHojeCalculado: ref(0),
            displayPixManualModal: false,
            pixChaveParaModal: ''
        };
    },

    computed: {
        parcelasComStatus() {
            return this.products?.data?.emprestimo?.parcelas.map((parcela) => {
                return {
                    ...parcela,
                    status: parcela.dt_baixa ? 'Pago' : 'Pendente'
                };
            });
        },
        isXGate() {
            return this.products?.data?.emprestimo?.banco?.bank_type === 'xgate';
        },
        isApix() {
            return this.products?.data?.emprestimo?.banco?.bank_type === 'apix';
        },
        valorPendenteHoje() {
            // Retornar o valor calculado e armazenado
            return this.valorPendenteHojeCalculado || 0;
        }
    },

    methods: {
        openConfirmation() {
            this.display = true;
        },
        formatarTelefone(telefone) {
            if (!telefone) return '';
            const regex = /^(\d{2})(\d{5})(\d{4})$/;
            const match = telefone.match(regex);
            if (match) {
                return `(${match[1]}) ${match[2]}-${match[3]}`;
            }
            return telefone;
        },
        isToday(date) {
            if (!date) return false;
            return moment(date, 'DD/MM/YYYY').isSame(moment(), 'day');
        },
        closeConfirmation() {
            this.display = false;

            this.emprestimoService
                .personalizarPagamento(this.id_pedido, this.sliderValue)
                .then((response) => {
                    alert('Aguarde e verifique seu WhatsApp para receber a chave PIX.');
                })
                .catch((error) => {
                    if (error?.response?.status != 422) {
                        alert(UtilService.message(error.response.data));
                    }
                });
        },
        goToPixLink(pixLink) {
            if (pixLink) {
                window.location.href = pixLink;
            } else {
                console.error('Pix link is not available');
            }
        },
        async copyToClipboard(text) {
            if (!text) return false;
            try {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    await navigator.clipboard.writeText(text);
                    return true;
                }
            } catch (_) {}
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            textArea.setAttribute('readonly', '');
            document.body.appendChild(textArea);
            textArea.select();
            textArea.setSelectionRange(0, 99999);
            let ok = false;
            try {
                ok = document.execCommand('copy');
            } finally {
                document.body.removeChild(textArea);
            }
            return ok;
        },
        openPixManualModal(chave) {
            this.pixChaveParaModal = chave || '';
            this.displayPixManualModal = true;
        },
        closePixManualModal() {
            this.displayPixManualModal = false;
        },
        async copiarChavePixModal() {
            if (!this.pixChaveParaModal) return;
            const ok = await this.copyToClipboard(this.pixChaveParaModal);
            if (ok) {
                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Chave PIX copiada para a área de transferência!',
                    life: 3500
                });
                this.closePixManualModal();
            } else {
                this.toast.add({
                    severity: ToastSeverity.WARN,
                    detail: 'Não foi possível copiar automaticamente. Selecione o código acima e use Copiar do teclado.',
                    life: 5000
                });
            }
        },
        isEsteBotaoLoading(tipo, id) {
            const ctx = this.loadingPixContext;
            return !!ctx && ctx.tipo === tipo && ctx.id == id;
        },
        async copiarChavePix(tipo, id, textoAtual) {
            const gerarPixNoServidor =
                tipo === 'pagamentoMinimo' || ((this.isXGate || this.isApix) && id);

            if (gerarPixNoServidor) {
                this.loadingPix = true;
                this.loadingPixContext = { tipo, id };
                try {
                    let res;
                    if (tipo === 'saldoPendente') {
                        res = await this.emprestimoService.gerarPixPagamentoSaldoPendente(id);
                    } else if (tipo === 'quitacao') {
                        res = await this.emprestimoService.gerarPixPagamentoQuitacao(id);
                    } else if (tipo === 'pagamentoMinimo') {
                        res = await this.emprestimoService.gerarPixPagamentoMinimo(this.id_pedido);
                    } else {
                        res = await this.emprestimoService.gerarPixPagamentoParcela(id);
                    }
                    const chave = res?.data?.chave_pix || res?.chave_pix;
                    if (chave) {
                        const copiou = await this.copyToClipboard(chave);
                        if (tipo === 'saldoPendente' && this.products?.data?.emprestimo?.pagamentosaldopendente) {
                            this.products.data.emprestimo.pagamentosaldopendente.chave_pix = chave;
                        }
                        if (tipo === 'quitacao' && this.products?.data?.emprestimo?.quitacao) {
                            this.products.data.emprestimo.quitacao.chave_pix = chave;
                        }
                        if (tipo === 'parcela' && this.products?.data?.emprestimo?.parcelas) {
                            const p = this.products.data.emprestimo.parcelas.find((x) => x.id === id);
                            if (p) p.chave_pix = chave;
                        }
                        if (tipo === 'pagamentoMinimo' && this.products?.data?.emprestimo?.pagamentominimo) {
                            this.products.data.emprestimo.pagamentominimo.chave_pix = chave;
                        }
                        if (copiou) {
                            this.toast.add({
                                severity: ToastSeverity.SUCCESS,
                                detail: 'Chave PIX copiada para a área de transferência!',
                                life: 3500
                            });
                        } else {
                            this.openPixManualModal(chave);
                        }
                    } else {
                        alert(res?.data?.message || 'Não foi possível gerar a chave PIX.');
                    }
                } catch (e) {
                    const msg = e?.response?.data?.message || e?.response?.data?.error || e?.message || 'Erro ao gerar chave PIX.';
                    alert(msg);
                } finally {
                    this.loadingPix = false;
                    this.loadingPixContext = null;
                }
            } else {
                const copiou = await this.copyToClipboard(textoAtual);
                if (copiou) {
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: 'Chave PIX copiada para a área de transferência!',
                        life: 3500
                    });
                } else if (textoAtual) {
                    this.openPixManualModal(textoAtual);
                }
            }
        },
        encontrarPrimeiraParcelaPendente() {
            for (let i = 0; i < this.products?.data?.emprestimo?.parcelas.length; i++) {
                if (this.products?.data?.emprestimo?.parcelas[i].dt_baixa === '') {
                    return this.products?.data?.emprestimo?.parcelas[i];
                }
            }
            return {};
        },
        calcularValorPendenteHoje() {
            if (!this.products?.data?.emprestimo?.parcelas || !Array.isArray(this.products.data.emprestimo.parcelas)) {
                this.valorPendenteHojeCalculado = 0;
                return;
            }

            const hoje = moment().startOf('day');
            let total = 0;
            const abertas = this.products.data.emprestimo.parcelas.filter(
                (parcela) => !parcela.dt_baixa || parcela.dt_baixa === ''
            );

            abertas.forEach((parcela) => {
                if (!parcela.venc_real) {
                    return;
                }
                const vr = moment(parcela.venc_real, 'DD/MM/YYYY', true);
                if (vr.isValid() && vr.isSameOrBefore(hoje, 'day')) {
                    total += parseFloat(parcela.saldo || 0);
                }
            });

            total = Math.round(total * 100) / 100;
            if (total === 0 && abertas.length > 0) {
                total = Math.round(parseFloat(abertas[0].saldo || 0) * 100) / 100;
            }

            this.valorPendenteHojeCalculado = total;
        }
    },

    watch: {
        displayPixManualModal(val) {
            if (!val) {
                this.pixChaveParaModal = '';
            }
        }
    },

    beforeMount() {
        this.loading = true;
        this.emprestimoService
            .infoEmprestimoFront(this.id_pedido)
            .then((response) => {
                if (response.data?.data?.emprestimo?.parcelas) {
                    response.data.data.emprestimo.parcelas = response.data.data.emprestimo.parcelas
                        .map((parcela) => {
                            return {
                                ...parcela,
                                status: parcela.dt_baixa ? 'Pago' : 'Pendente'
                            };
                        });
                }
                this.products = response.data;
                
                // Calcular valor pendente hoje após carregar os dados
                this.calcularValorPendenteHoje();

                // Define min: pagamento mínimo ou valor pendente do dia como fallback
                // Converte para número garantindo que seja um valor válido
                const pagamentoMinimo = parseFloat(this.products?.data?.emprestimo?.pagamentominimo?.valorSemFormatacao) || 0;
                const valorPendente = this.valorPendenteHojeCalculado || parseFloat(this.products?.data?.emprestimo?.pagamentosaldopendente?.valor) || 0;
                let saldoAReceber = parseFloat(this.products?.data?.emprestimo?.saldoareceber) || 0;
                
                // Se saldoareceber for 0, usa o valor pendente como fallback
                if (saldoAReceber <= 0 && valorPendente > 0) {
                    saldoAReceber = valorPendente;
                }
                
                // Min deve ser o pagamento mínimo quando existir, senão o valor pendente
                this.min = pagamentoMinimo > 0 ? pagamentoMinimo : (valorPendente > 0 ? valorPendente : 0);
                this.max = saldoAReceber;

                // Garante que min não seja maior que max
                if (this.min > this.max && this.max > 0) {
                    this.min = this.max;
                }

                // Garante valores válidos para o slider
                if (this.min <= 0 || this.max <= 0 || this.min >= this.max) {
                    // Se não houver valores válidos, usa valores padrão seguros
                    this.min = 0;
                    this.max = 1000;
                    this.sliderValue = 500;
                } else {
                    // Define o valor inicial no meio do range entre min e max
                    let valor = this.max - this.min;
                    this.sliderValue = this.min + valor / 2;
                }
                this.loading = false;
            })
            .catch((error) => {
                if (error?.response?.status != 422) {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error.response.data),
                        life: 3000
                    });
                }
                this.loading = false;
            });
    }
};
</script>

<template>
    <FullScreenLoading :isLoading="loading || loadingPix" />
    <div class="container">
        <header>
            <h1>Histórico de Parcelas</h1>
        </header>

        <main>
            <!-- Aviso XGate: pagamento PIX deve ser do mesmo CPF do titular -->
            <section v-if="isXGate && this.products?.data?.emprestimo?.cpf_titular" class="payment-section xgate-aviso-cpf">
                <div class="xgate-aviso-box">
                    <p class="xgate-aviso-titulo">⚠️ Importante – Pagamento PIX (XGate)</p>
                    <p>O pagamento desta chave PIX deve ser realizado de uma <strong>conta cujo titular seja o mesmo CPF do empréstimo</strong>.</p>
                    <p><strong>CPF cadastrado do titular:</strong> <span class="cpf-destaque">{{ this.products?.data?.emprestimo?.cpf_titular }}</span></p>
                    <p class="xgate-aviso-rodape">O titular da conta de origem do PIX precisa ser exatamente este CPF.</p>
                </div>
            </section>

            <!-- Parcela do Dia / Valor Pendente -->
            <section v-if="this.products?.data?.emprestimo?.pagamentosaldopendente" class="payment-section">
                <h2>Valor Pendente do Dia {{ valorPendenteHojeCalculado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix, efetue o pagamento para evitar juros adicionais.</p>
                <button class="btn-secondary" :disabled="loadingPix" @click="copiarChavePix('saldoPendente', this.products?.data?.emprestimo?.pagamentosaldopendente?.id, this.products?.data?.emprestimo?.pagamentosaldopendente?.chave_pix)">
                    Copiar Chave Pix - Valor Pendente <br />{{ valorPendenteHojeCalculado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                </button>
            </section>

            <!-- Quitar Empréstimo (independe do valor pendente do dia; ambos podem aparecer juntos) -->
            <section
                v-if="this.products?.data?.emprestimo?.quitacao && Number(this.products?.data?.emprestimo?.quitacao?.saldo) > 0"
                class="payment-section"
            >
                <h2>Quitar Empréstimo</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix para quitar o valor total do empréstimo.</p>
                <button class="btn-primary" :disabled="loadingPix" @click="copiarChavePix('quitacao', this.products?.data?.emprestimo?.quitacao?.id, this.products?.data?.emprestimo?.quitacao?.chave_pix)">
                    Copiar Chave Pix - Quitar Empréstimo <br />{{ Number(this.products?.data?.emprestimo?.quitacao?.saldo || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                </button>
            </section>

            <!-- Pagamento Mínimo -->
            <section v-if="this.products?.data?.emprestimo?.pagamentominimo && this.products?.data?.emprestimo?.liberar_minimo == 1" class="payment-section">
                <h2>Pagamento Mínimo - Juros</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix abaixo para pagar o valor mínimo e manter seu empréstimo em dia.</p>
                <button
                    class="btn-primary"
                    :disabled="loadingPix"
                    @click="copiarChavePix('pagamentoMinimo', this.id_pedido, this.products?.data?.emprestimo?.pagamentominimo?.chave_pix)"
                >
                    Copiar Chave Pix - Pagamento Mínimo <br />{{ this.products?.data?.emprestimo?.pagamentominimo.valor }}
                </button>
            </section>

            <!-- Pagamento Mínimo -->
            <section v-if="!this.products?.data?.emprestimo?.pagamentominimo && this.products?.data?.emprestimo?.parcelas.length == 1 && this.products?.data?.emprestimo?.liberar_minimo == 1" class="payment-section">
                <h2>Pagamento Mínimo - Juros</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix abaixo para pagar o valor mínimo e manter seu empréstimo em dia.</p>
                <button class="btn-primary" @click="copyToClipboard(this.encontrarPrimeiraParcelaPendente().chave_pix != '' ? this.encontrarPrimeiraParcelaPendente().chave_pix : this.products?.data?.emprestimo?.banco.chavepix)">Copiar Chave Pix - Pagamento Mínimo <br />{{ this.products?.data?.emprestimo?.lucro?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })  }}</button>
            </section>

            <section v-if="this.products?.data?.emprestimo?.pagamentominimo" class="payment-section">
                <h2>Pagamento Personalizado</h2>
                <p>Ao clicar no botão abaixo, você conseguirá personalizar o valor e será aplicado para abater os juros e parte do empréstimo.</p>

                <button class="btn-secondary" @click="openConfirmation()">Personalizar Valor</button>
            </section>

            <!-- Pagamento Mínimo BANCO MANUAL-->

            <!-- <section v-if="!this.products?.data?.emprestimo?.pagamentominimo && this.products?.data?.emprestimo?.parcelas.length == 1" class="payment-section">
                <h2>Pagamento Total {{ this.encontrarPrimeiraParcelaPendente().saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</h2>
                <h2 style="margin-top: -3px">Pagamento mínimo - Juros {{ (this.encontrarPrimeiraParcelaPendente().saldo - this.products?.data?.emprestimo?.valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</h2>
                <p>Para pagamento de demais valores<br />Entre em contato pelo WhatsApp {{ formatarTelefone(this.products?.data?.emprestimo?.telefone_empresa) }}</p>
            </section> -->

            <!-- <section v-if="!this.products?.data?.emprestimo?.quitacao " class="payment-section">
                <h2>Valor para quitação {{ this.encontrarPrimeiraParcelaPendente().total_pendente.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</h2>
                <h2 v-if="!this.products?.data?.emprestimo?.pagamentominimo && this.products?.data?.emprestimo?.parcelas.length == 1" style="margin-top: -3px">
                    Pagamento mínimo - Juros {{ (this.encontrarPrimeiraParcelaPendente().saldo - this.products?.data?.emprestimo?.valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                </h2>
                <p>Para pagamento de demais valores<br />Entre em contato pelo WhatsApp {{ formatarTelefone(this.products?.data?.emprestimo?.telefone_empresa) }}</p>
            </section> -->

            <section v-if="(!this.products?.data?.emprestimo?.pagamentominimo && this.products?.data?.emprestimo?.parcelas.length == 1) && this.products?.data?.emprestimo?.banco?.chavepix" class="payment-section">
                <h2>{{ this.products?.data?.emprestimo?.banco?.info_recebedor_pix }}</h2>
                <h2 style="margin-top: -3px" @click="copyToClipboard(this.products?.data?.emprestimo?.banco?.chavepix)">Chave pix: {{ this.products?.data?.emprestimo?.banco?.chavepix }}</h2>
            </section>

            <DataTable :value="this.products?.data?.emprestimo?.parcelas">
                <Column field="venc_real" header="Venc.">
                    <template #body="slotProps">
                        <span>
                            {{ slotProps.data?.venc }}
                        </span>
                    </template>
                </Column>
                <Column field="venc_real" header="Venc. Real">
                    <template #body="slotProps">
                        <span :style="{ color: isToday(slotProps.data?.venc_real) ? 'red' : 'black' }">
                            {{ slotProps.data?.venc_real }}
                        </span>
                    </template>
                </Column>
                <Column field="saldo" header="Saldo c/ Juros">
                    <template #body="slotProps">
                        <span :style="{ color: isToday(slotProps.data?.venc_real) ? 'red' : 'black' }">{{ slotProps.data?.saldo?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</span>
                    </template>
                </Column>
                <Column v-if="!this.products?.data?.emprestimo?.parcelas.length > 1" field="total_pago_parcela" header="Pago"></Column>
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <Button v-if="slotProps.data.status === 'Pago'" label="Pago" class="p-button-raised p-button-success mr-2 mb-2" />
                        <Button
                            v-if="slotProps.data.status != 'Pago'"
                            :disabled="loadingPix"
                            label="Copiar Chave Pix"
                            @click="copiarChavePix('parcela', slotProps.data.id, slotProps.data.chave_pix || this.products?.data?.emprestimo?.banco?.chavepix)"
                            class="p-button-raised p-button-danger mr-2 mb-2"
                        />
                    </template>
                </Column>
            </DataTable>
        </main>
    </div>

    <Dialog header="Personalizar Valor" v-model:visible="display" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <p class="line-height-3 m-0">Selecione um valor para abater os juros e parte do empréstimo, e o vencimento será prorrogado para o próximo mês, o sistema enviará a chave Pix pelo WhatsApp.</p>

        <h2 style="margin-top: 20px; text-align: center">
            Valor: <b>{{ sliderValue?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</b>
        </h2>
        <Slider style="margin-top: 20px" v-model="sliderValue" :min="this.min" :max="this.max" :step="0.5" />

        <template #footer>
            <Button label="Enviar" @click="closeConfirmation" icon="pi pi-check" class="p-button-outlined" />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="displayPixManualModal"
        header="Chave PIX"
        :modal="true"
        :closable="true"
        :dismissableMask="true"
        :breakpoints="{ '960px': '90vw' }"
        :style="{ width: 'min(520px, 94vw)' }"
    >
        <p class="pix-manual-intro m-0 mb-3 line-height-3">Chave PIX gerada. Use o botão abaixo para copiar ou selecione o código.</p>
        <div class="pix-manual-chave-wrap">
            <textarea class="pix-manual-chave" readonly rows="6" :value="pixChaveParaModal" @focus="$event.target.select()" />
        </div>
        <template #footer>
            <Button label="Fechar" icon="pi pi-times" class="p-button-text" @click="closePixManualModal" />
            <Button label="Copiar chave PIX" icon="pi pi-copy" class="p-button-primary" @click="copiarChavePixModal" />
        </template>
    </Dialog>
</template>

<style scoped>
/* Reset */

.container {
    padding: 1rem;
}

.payment-section p {
    text-align: center;
}

.payment-section h2 {
    text-align: center;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f9f9f9;
    color: #333;
    padding: 1rem;
}

header {
    text-align: center;
    margin-bottom: 1rem;
}

h1 {
    font-size: 2rem;
    color: #0056b3;
}

main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

section {
    background: #fff;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

h2 {
    font-size: 1.4rem;
    margin-bottom: 0.5rem;
    color: #0056b3;
}

p {
    font-size: 1rem;
    margin-bottom: 1rem;
}

button {
    width: 100%;
    padding: 0.8rem;
    font-size: 1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background: #28a745;
    color: #fff;
}

.btn-secondary {
    background: #007bff;
    color: #fff;
}

.btn-light {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

button:hover {
    opacity: 0.9;
}

button:disabled {
    cursor: wait;
    opacity: 0.85;
}

.xgate-aviso-cpf {
    margin-bottom: 1rem;
}

.xgate-aviso-box {
    background: #fff8e6;
    border: 1px solid #e6c200;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    color: #5c4a00;
}

.xgate-aviso-titulo {
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.xgate-aviso-box p {
    margin: 0.4rem 0;
    line-height: 1.4;
}

.xgate-aviso-box .cpf-destaque {
    font-family: monospace;
    font-size: 1.05rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.xgate-aviso-rodape {
    margin-top: 0.6rem;
    font-size: 0.95rem;
    opacity: 0.95;
}

.card {
    margin-top: 1rem;
}

.pix-manual-chave-wrap {
    width: 100%;
}

.pix-manual-chave {
    width: 100%;
    box-sizing: border-box;
    font-family: ui-monospace, monospace;
    font-size: 0.75rem;
    line-height: 1.35;
    word-break: break-all;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    resize: vertical;
    min-height: 120px;
    background: #f8f9fa;
    color: #212529;
}

.pix-manual-intro {
    color: #495057;
    font-size: 0.95rem;
}
</style>
