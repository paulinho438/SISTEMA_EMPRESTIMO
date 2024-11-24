<script>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import EmprestimoService from '../../service/EmprestimoService';

export default {
    data() {
        return {
            router: useRouter(),
            route: useRoute(),
            emprestimoService: new EmprestimoService(),
            id_pedido: ref(this.$route.params.id_pedido),
            informacoes: ref(null),
            products: ref([])
        };
    },

    computed: {
        parcelasComStatus() {
            return this.products?.data?.emprestimo?.parcelas.map(parcela => {
                return {
                    ...parcela,
                    status: parcela.dt_baixa ? 'Pago' : 'Pendente'
                };
            });
        }
    },

    methods: {
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
                if (this.products?.data?.emprestimo?.parcelas[i].dt_baixa === "") {
                    return this.products?.data?.emprestimo?.parcelas[i];
                }
            }
            return {};
        }
    },

    beforeMount() {
        this.emprestimoService.infoEmprestimoFront(this.id_pedido)
            .then((response) => {
                if (response.data?.data?.emprestimo?.parcelas) {
                    response.data.data.emprestimo.parcelas = response.data.data.emprestimo.parcelas.map(parcela => {
                        return {
                            ...parcela,
                            status: parcela.dt_baixa ? 'Pago' : 'Pendente'
                        };
                    });
                }
                this.products = response.data;
                console.log(this.products);
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
            <!-- Quitar Empréstimo -->
            <section class="payment-section">
                <h2>Quitar Empréstimo</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix para quitar o valor total do empréstimo.</p>
                <button class="btn-primary" @click="copyToClipboard('chave-pix-quitar')">Copiar Chave Pix - Quitar Empréstimo (R$ 1,00)</button>
            </section>

            <!-- Pagamento Mínimo -->
            <section class="payment-section">
                <h2>Pagamento Mínimo</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix abaixo para pagar o valor mínimo e manter seu empréstimo em dia.</p>
                <button class="btn-secondary" @click="copyToClipboard('chave-pix-minimo')">Copiar Chave Pix - Pagamento Mínimo (R$ 0,00)</button>
            </section>

            <!-- Parcela do Dia -->
            <section class="payment-section">
                <h2>Parcela do Dia</h2>
                <p>Ao clicar no botão abaixo, Copiará a chave Pix, esta é a parcela com vencimento mais próximo. Efetue o pagamento para evitar juros adicionais.</p>
                <p><strong>Vencimento:</strong> 25/11/2024</p>
                <p><strong>Valor:</strong> R$ 1,00</p>
                <button class="btn-secondary" @click="copyToClipboard('chave-pix-parcela-dia')">Copiar Chave Pix - Parcela do Dia</button>
            </section>

            <div class="card">
                <DataTable :value="this.products?.data?.emprestimo?.parcelas">
                    <Column field="venc_real" header="Venc."></Column>
                    <Column field="valor" header="Parcela"></Column>
                    <Column field="saldo" header="Saldo c/ Juros"></Column>
                    <Column v-if="!this.products?.data?.emprestimo?.pagamentominimo" field="total_pago_parcela" header="Pago"></Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <Button v-if="slotProps.data.status === 'Pago'" label="Pago"
                                class="p-button-raised p-button-success mr-2 mb-2" />
                            <Button v-if="slotProps.data?.chave_pix != '' && slotProps.data.status != 'Pago'" label="Copiar Chave Pix"
                                @click="copyToClipboard(this.encontrarPrimeiraParcelaPendente().chave_pix)"
                                class="p-button-raised p-button-danger mr-2 mb-2" />
                            <Button v-if="slotProps.data?.chave_pix == '' && slotProps.data.status != 'Pago' " label="Copiar Chave Pix"
                                @click="copyToClipboard(this.products?.data?.emprestimo?.banco.chavepix)"
                                class="p-button-raised p-button-danger mr-2 mb-2" />
                        </template>
                        
                    </Column>
                </DataTable>
            </div>

        </main>
    </div>
</template>

<style scoped>
/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
.container{
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
