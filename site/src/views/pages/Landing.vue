<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useRoute } from 'vue-router';
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
        encontrarPrimeiraParcelaPendente() {
            for (let i = 0; i < this.products?.data?.emprestimo?.parcelas.length; i++) {
                if (this.products?.data?.emprestimo?.parcelas[i].dt_baixa === "") {
                    return this.products?.data?.emprestimo?.parcelas[i];
                }
            }

            return {};
        }
    },

    beforeMount: function () {
        //Requisição para buscar informações do pedido

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
                console.log(this.products)

            })
            .catch((error) => {
                if (error?.response?.status != 422) {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error.response.data),
                        life: 3000
                    });
                }
            })
            .finally(() => {
            });
    }
};
</script>

<template>
    <div class="surface-0 flex justify-content-center">
        <div id="home" class="landing-wrapper overflow-hidden center text-center">
            <div id="hero" class="flex flex-column pt-4 px-4 lg:px-8 overflow-hidden pb-6"
                style="background: linear-gradient(0deg, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)), radial-gradient(77.36% 256.97% at 77.36% 57.52%, rgba(0, 0, 0, 1) 0%, #003360 100%); clip-path: ellipse(150% 87% at 93% 13%)">
                <div class="mx-4 md:mx-8 mt-0 center text-center">
                    <!-- <img width="200px" src="https://www.gruporialma.com.br/wp-content/uploads/2023/12/imagem_2023-12-01_162149885.png" /> -->
                    <h3 style="color: white" class="font-bold line-height-2">Histórico de parcelas</h3>
                </div>
            </div>

            <div id="features" class="py-4 px-4 lg:px-8 mt-5 mx-0 lg:mx-8">
                <div class="grid justify-content-center">
                    <div class="col-6 md:col-6 lg:col-4 p-0 lg:pr-5 lg:pb-5 mt-4 lg:mt-0 p-2">
                        <div
                            style="height: 160px; padding: 10px; border-radius: 1px; background: linear-gradient(90deg, rgba(145, 226, 237, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(172, 180, 223, 0.2))">
                            <div class="p-3 surface-card h-full" style="border-radius: 8px">
                                <div class="icon-wrapper flex align-items-center justify-content-center bg-green-200 mb-3">
                                    <i class="pi pi-fw pi-money-bill text-2xl text-cyan-700 p-1"></i>
                                </div>
                                <h5 class="mb-2 text-900">Valor do Empréstimo</h5>
                                <span class="text-600">{{ this.products?.data?.emprestimo?.valor }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 md:col-6 lg:col-4 p-0 lg:pr-5 lg:pb-5 mt-4 lg:mt-0 p-2">
                        <div
                            style="height: 160px; padding: 10px; border-radius: 1px; background: linear-gradient(90deg, rgba(187, 199, 205, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(145, 210, 204, 0.2))">
                            <div class="p-3 surface-card h-full" style="border-radius: 8px">
                                <div
                                    class="icon-wrapper flex align-items-center justify-content-center bg-bluegray-200 mb-3">
                                    <i class="pi pi-fw pi-building text-2xl text-bluegray-700 p-1"></i>
                                </div>
                                <h5 class="mb-2 text-900">Parcelas</h5>
                                <span class="text-600">{{ this.products?.data?.emprestimo?.parcelas.length }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 md:col-12 lg:col-4 p-0 lg:pr-5 lg:pb-5 mt-4 lg:mt-0 p-2">
                        <div
                            style=" padding: 10px; border-radius: 1px; background: linear-gradient(90deg, rgba(187, 199, 205, 0.2), rgba(251, 199, 145, 0.2)), linear-gradient(180deg, rgba(253, 228, 165, 0.2), rgba(145, 210, 204, 0.2))">
                            <div class="p-3 surface-card h-full" style="border-radius: 8px">
                                <Button @click="goToPixLink(this.encontrarPrimeiraParcelaPendente().chave_pix)" :label="`Parcela Pendente n° ${this.encontrarPrimeiraParcelaPendente().parcela} no Valor ${this.encontrarPrimeiraParcelaPendente().valor}`"
                                    class="p-button-raised p-button-danger mr-2 mb-2" style="height: 60px;" />
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <DataTable :value="this.products?.data?.emprestimo?.parcelas">
                            <Column field="venc_real" header="Venc."></Column>
                            <Column field="valor" header="Parcela"></Column>
                            <Column field="saldo" header="Saldo c/ Juros"></Column>
                            <Column field="total_pago_parcela" header="Pago"></Column>
                            <Column field="status" header="Status">
                                <template #body="slotProps">
                                    <Button v-if="slotProps.data.status === 'Pago'" label="Pago"
                                        class="p-button-raised p-button-success mr-2 mb-2" />
                                    <Button v-else label="Pendente Pagar com Pix"
                                        @click="goToPixLink(slotProps.data?.chave_pix)"
                                        class="p-button-raised p-button-danger mr-2 mb-2" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>


                </div>
            </div>
        </div>
    </div>
    <AppConfig simple />
</template>
