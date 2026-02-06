<script>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import EmprestimoService from '../../service/EmprestimoService';
import FullScreenLoading from '@/components/FullScreenLoading.vue';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import { useConfirm } from 'primevue/useconfirm';

import moment from 'moment';

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
            valorPendenteHojeCalculado: ref(0)
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
        isEsteBotaoLoading(tipo, id) {
            const ctx = this.loadingPixContext;
            return !!ctx && ctx.tipo === tipo && ctx.id == id;
        },
        async copiarChavePix(tipo, id, textoAtual) {
            if ((this.isXGate || this.isApix) && id) {
                this.loadingPix = true;
                this.loadingPixContext = { tipo, id };
                try {
                    let res;
                    if (tipo === 'saldoPendente') {
                        res = await this.emprestimoService.gerarPixPagamentoSaldoPendente(id);
                    } else if (tipo === 'quitacao') {
                        res = await this.emprestimoService.gerarPixPagamentoQuitacao(id);
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
                        if (copiou) {
                            alert('Chave PIX copiado para a área de transferência!');
                        } else {
                            alert('Chave PIX gerada. Copie manualmente:\n\n' + chave);
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
                    alert('Chave PIX copiado para a área de transferência!');
                } else if (textoAtual) {
                    alert('Copie manualmente a chave:\n\n' + textoAtual);
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
            // Calcular e atualizar o valor pendente hoje
            if (!this.products?.data?.emprestimo?.parcelas || !Array.isArray(this.products.data.emprestimo.parcelas)) {
                this.valorPendenteHojeCalculado = 0;
                return;
            }
            
            const hoje = moment().format('DD/MM/YYYY');
            let total = 0;
            
            this.products.data.emprestimo.parcelas.forEach((parcela) => {
                if ((!parcela.dt_baixa || parcela.dt_baixa === '') && parcela.venc_real === hoje) {
                    total += parseFloat(parcela.saldo || 0);
                }
            });
            
            this.valorPendenteHojeCalculado = Math.round(total * 100) / 100;
            console.log('Valor pendente hoje atualizado:', this.valorPendenteHojeCalculado);
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
    <FullScreenLoading :isLoading="loading" />
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

            <!-- Parcela do Dia -->
            <section v-if="this.products?.data?.emprestimo?.pagamentosaldopendente?.chave_pix" class="payment-section">
                <h2>Valor Pendente do Dia {{ valorPendenteHojeCalculado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix, efetue o pagamento para evitar juros adicionais.</p>
                <!-- <p><strong>Vencimento:</strong> {{ this.encontrarPrimeiraParcelaPendente().venc_real }}</p> -->
                <!-- <p><strong>Valor Parcela: </strong>{{ this.encontrarPrimeiraParcelaPendente().saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</p> -->
                <!-- <p><strong>Saldo Pendente: </strong>{{ this.encontrarPrimeiraParcelaPendente().total_pendente_hoje.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</p> -->
                <button class="btn-secondary" :disabled="loadingPix" @click="copiarChavePix('saldoPendente', this.products?.data?.emprestimo?.pagamentosaldopendente?.id, this.products?.data?.emprestimo?.pagamentosaldopendente?.chave_pix)">
                    <span v-if="(isXGate || isApix) && isEsteBotaoLoading('saldoPendente', this.products?.data?.emprestimo?.pagamentosaldopendente?.id)">Gerando...</span>
                    <template v-else>Copiar Chave Pix - Valor Pendente <br />{{ valorPendenteHojeCalculado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</template>
                </button>
            </section>

            <section v-if="!this.products?.data?.emprestimo?.pagamentosaldopendente?.chave_pix" class="payment-section">
                <h2>Valor Pendente do Dia {{ valorPendenteHojeCalculado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix, efetue o pagamento para evitar juros adicionais.</p>
                <!-- <p><strong>Vencimento:</strong> {{ this.encontrarPrimeiraParcelaPendente().venc_real }}</p> -->
                <!-- <p><strong>Valor Parcela: </strong>{{ this.encontrarPrimeiraParcelaPendente().saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</p> -->
                <!-- <p><strong>Saldo Pendente: </strong>{{ this.encontrarPrimeiraParcelaPendente().total_pendente_hoje.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</p> -->
                <button class="btn-secondary" :disabled="loadingPix" @click="copiarChavePix('parcela', this.encontrarPrimeiraParcelaPendente()?.id, this.encontrarPrimeiraParcelaPendente()?.chave_pix || this.products?.data?.emprestimo?.banco?.chavepix)">
                    <span v-if="(isXGate || isApix) && isEsteBotaoLoading('parcela', this.encontrarPrimeiraParcelaPendente()?.id)">Gerando...</span>
                    <template v-else>Copiar Chave Pix - Valor Pendente <br />{{ valorPendenteHojeCalculado.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</template>
                </button>
            </section>

            <!-- Quitar Empréstimo -->
            <section v-if="(this.products?.data?.emprestimo?.quitacao?.saldo && this.products?.data?.emprestimo?.quitacao?.saldo != this.products?.data?.emprestimo?.pagamentosaldopendente?.valor) && (this.products?.data?.emprestimo?.saldoareceber != this.encontrarPrimeiraParcelaPendente().saldo)" class="payment-section">
                <h2>Quitar Empréstimo</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix para quitar o valor total do empréstimo.</p>
                <button class="btn-primary" :disabled="loadingPix" @click="copiarChavePix('quitacao', this.products?.data?.emprestimo?.quitacao?.id, this.products?.data?.emprestimo?.quitacao?.chave_pix)">
                    <span v-if="(isXGate || isApix) && isEsteBotaoLoading('quitacao', this.products?.data?.emprestimo?.quitacao?.id)">Gerando...</span>
                    <template v-else>Copiar Chave Pix - Quitar Empréstimo <br />{{ this.products?.data?.emprestimo?.quitacao?.saldo }}</template>
                </button>
            </section>

            <!-- Pagamento Mínimo -->
            <section v-if="this.products?.data?.emprestimo?.pagamentominimo && this.products?.data?.emprestimo?.liberar_minimo == 1" class="payment-section">
                <h2>Pagamento Mínimo - Juros</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix abaixo para pagar o valor mínimo e manter seu empréstimo em dia.</p>
                <button class="btn-primary" @click="copyToClipboard(this.products?.data?.emprestimo?.pagamentominimo.chave_pix)">Copiar Chave Pix - Pagamento Mínimo <br />{{ this.products?.data?.emprestimo?.pagamentominimo.valor }}</button>
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
                            :loading="(isXGate || isApix) && isEsteBotaoLoading('parcela', slotProps.data.id)"
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
</style>
