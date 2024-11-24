<script>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import EmprestimoService from '../../service/EmprestimoService';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

export default {
    data() {
        return {
            router: useRouter(),
            route: useRoute(),
            emprestimoService: new EmprestimoService(),
            id_pedido: ref(this.$route.params.id_pedido),
            informacoes: ref(null),
            products: ref([]),
            display: ref(false),
            sliderValue: ref(1),
            min: ref(0),
            max: ref(1000)
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
        }
    },

    methods: {
        openConfirmation() {
            this.display = true;
        },

        closeConfirmation() {
            this.display = false;
        },
        goToPixLink(pixLink) {
            if (pixLink) {
                window.location.href = pixLink;
            } else {
                console.error('Pix link is not available');
            }
        },
        copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);

            textArea.select();
            textArea.setSelectionRange(0, 99999);

            document.execCommand('copy');
            document.body.removeChild(textArea);

            alert('Chave PIX copiado para a área de transferência!');
        },
        encontrarPrimeiraParcelaPendente() {
            for (let i = 0; i < this.products?.data?.emprestimo?.parcelas.length; i++) {
                if (this.products?.data?.emprestimo?.parcelas[i].dt_baixa === '') {
                    return this.products?.data?.emprestimo?.parcelas[i];
                }
            }
            return {};
        }
    },

    beforeMount() {
        this.emprestimoService
            .infoEmprestimoFront(this.id_pedido)
            .then((response) => {
                if (response.data?.data?.emprestimo?.parcelas) {
                    response.data.data.emprestimo.parcelas = response.data.data.emprestimo.parcelas.map((parcela) => {
                        return {
                            ...parcela,
                            status: parcela.dt_baixa ? 'Pago' : 'Pendente'
                        };
                    });
                }
                this.products = response.data;

                this.min = this.products?.data?.emprestimo?.pagamentominimo?.valorSemFormatacao;
                this.max = this.products?.data?.emprestimo?.saldoareceber;

                let valor = this.max - this.min;

                this.sliderValue =  this.min + (valor / 2);


                console.log(this.min);
                console.log(this.max);
                console.log(this.sliderValue);
            })
            .catch((error) => {
                if (error?.response?.status != 422) {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error.response.data),
                        life: 3000
                    });
                }
            });
    }
};
</script>

<template>
    <div class="container">
        <header>
            <h1>Histórico de Parcelas</h1>
        </header>

        <main>
            <!-- Parcela do Dia -->
            <section class="payment-section">
                <h2>Parcela do Dia</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix, efetue o pagamento para evitar juros adicionais.</p>
                <p><strong>Vencimento:</strong> 25/11/2024</p>
                <p><strong>Valor:</strong> R$ {{this.encontrarPrimeiraParcelaPendente().saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</p>
                <button class="btn-secondary" @click="copyToClipboard(this.encontrarPrimeiraParcelaPendente().chave_pix)">Copiar Chave Pix - Parcela do Dia</button>
            </section>

            <!-- Quitar Empréstimo -->
            <section class="payment-section">
                <h2>Quitar Empréstimo</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix para quitar o valor total do empréstimo.</p>
                <button class="btn-primary" @click="copyToClipboard(this.products?.data?.emprestimo?.quitacao.chave_pix)">Copiar Chave Pix - Quitar Empréstimo (R$ {{ this.products?.data?.emprestimo?.quitacao.saldo }})</button>
            </section>

            <!-- Pagamento Mínimo -->
            <section class="payment-section">
                <h2>Pagamento Mínimo</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix abaixo para pagar o valor mínimo e manter seu empréstimo em dia.</p>
                <button class="btn-secondary" @click="copyToClipboard(this.products?.data?.emprestimo?.pagamentominimo.chave_pix)">Copiar Chave Pix - Pagamento Mínimo (R$ {{ this.products?.data?.emprestimo?.pagamentominimo.valor }})</button>
            </section>

            <section class="payment-section">
                <h2>Pagamento Personalizado</h2>
                <p>
                    Informe um valor superior ao pagamento mínimo. O valor será aplicado para abater os juros e parte do empréstimo, e o vencimento será prorrogado para o próximo mês. Ao clicar no botão abaixo, i O sistema enviará a chave Pix por
                    mensagem
                </p>

                <button class="btn-secondary" @click="openConfirmation()">Personalizar Valor</button>
            </section>

            <div class="card">
                <DataTable :value="this.products?.data?.emprestimo?.parcelas">
                    <Column field="venc_real" header="Venc."></Column>
                    <Column field="valor" header="Parcela"></Column>
                    <Column field="saldo" header="Saldo c/ Juros">
                        <template #body="slotProps">
                            <span>{{slotProps.data.saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}}</span>
                        </template>
                    </Column>
                    <Column v-if="!this.products?.data?.emprestimo?.pagamentominimo" field="total_pago_parcela" header="Pago"></Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <Button v-if="slotProps.data.status === 'Pago'" label="Pago" class="p-button-raised p-button-success mr-2 mb-2" />
                            <Button
                                v-if="slotProps.data?.chave_pix != '' && slotProps.data.status != 'Pago'"
                                label="Copiar Chave Pix"
                                @click="copyToClipboard(this.encontrarPrimeiraParcelaPendente().chave_pix)"
                                class="p-button-raised p-button-danger mr-2 mb-2"
                            />
                            <Button
                                v-if="slotProps.data?.chave_pix == '' && slotProps.data.status != 'Pago'"
                                label="Copiar Chave Pix"
                                @click="copyToClipboard(this.products?.data?.emprestimo?.banco.chavepix)"
                                class="p-button-raised p-button-danger mr-2 mb-2"
                            />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </main>
    </div>

    <Dialog header="Personalizar Valor" v-model:visible="display" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <p class="line-height-3 m-0">Selecione um valor para abater os juros e parte do empréstimo, e o vencimento será prorrogado para o próximo mês.</p>
       
        <h2 style="margin-top:20px; text-align: center;"> Valor: <b>{{sliderValue.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}}</b></h2>
        <Slider style="margin-top: 20px" v-model="sliderValue" :min="this.min" :max="this.max" :step="0.50"/>

        <template #footer>
            <Button label="Enviar" @click="closeConfirmation" icon="pi pi-check" class="p-button-outlined" />
        </template>
    </Dialog>
</template>

<style scoped>
/* Reset */

.container {
    padding: 2rem;
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

.card {
    margin-top: 1rem;
}
</style>
