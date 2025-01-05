<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import contaspagarService from '@/service/ContaspagarService';
import UtilService from '@/service/UtilService';
import EmprestimoService from '@/service/EmprestimoService';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';
import FullScreenLoading from '@/components/FullScreenLoading.vue';

export default {
    name: 'cicomForm',
    components: {
        FullScreenLoading // Registra o componente
    },
    setup() {
        return {
            route: useRoute(),
            router: useRouter(),
            contaspagarService: new contaspagarService(),
            emprestimoService: new EmprestimoService(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            contaspagar: ref({}),
            oldcontaspagar: ref(null),
            errors: ref([]),
            bancos: ref([]),
            banco: ref(null),
            fornecedores: ref([]),
            fornecedor: ref(null),
            costcenters: ref([]),
            costcenter: ref(null),
            address: ref({
                id: 1,
                name: 'ok',
                geolocalizacao: '17.23213, 12.455345'
            }),
            loading: ref(false),
            selectedTipoDocumento: ref(''),
            tipoDocumento: ref([
                { name: 'Boleto', value: 'Boleto' },
                { name: 'Carnê', value: 'Carnê' },
                { name: 'Cheque', value: 'Cheque' },
                { name: 'Promissória', value: 'Promissória' },
                { name: 'Retirada', value: 'Retirada' },
                { name: 'Outros', value: 'Outros' }
            ]),
            displayConfirmation: ref(false),
            displayConfirmationMessage: ref(''),
            loadingFullScreen: ref(false)
        };
    },
    methods: {
        changeLoading() {
            this.loading = !this.loading;
        },
        openConfirmation() {
            this.displayConfirmation = true;
        },

        closeConfirmation() {
            this.displayConfirmation = false;
            this.toast.add({ severity: 'info', summary: 'Cancelar', detail: 'Pagamento não realizado!', life: 3000 });
        },
        getcontaspagar() {
            if (this.route.params?.id) {
                this.contaspagar = ref(null);
                this.loading = true;
                this.contaspagarService
                    .get(this.route.params.id)
                    .then((response) => {
                        this.contaspagar = response.data?.data;
                        this.selectedTipoDocumento = { name: this.contaspagar?.tipodoc, value: this.contaspagar?.tipodoc };
                        this.costcenter = response.data?.data.costcenter;
                        this.fornecedor = response.data?.data.fornecedor;
                        this.banco = response.data?.data.banco;

                        console.log(response.data);
                    })
                    .catch((error) => {
                        this.toast.add({
                            severity: ToastSeverity.ERROR,
                            detail: UtilService.message(e),
                            life: 3000
                        });
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            } else {
                this.contaspagar = ref({});
                this.contaspagar.address = [];
            }
        },
        back() {
            this.router.push(`/aprovacao`);
        },
        changeEnabled(enabled) {
            this.contaspagar.enabled = enabled;
        },
        async searchFornecedor(event) {
            try {
                let response = await this.emprestimoService.searchFornecedor(event.query);
                this.fornecedores = response.data.data;
            } catch (e) {
                console.log(e);
            }
        },
        async searchBanco(event) {
            try {
                let response = await this.emprestimoService.searchbanco(event.query);
                this.bancos = response.data.data;
            } catch (e) {
                console.log(e);
            }
        },
        async searchCostcenter(event) {
            try {
                let response = await this.emprestimoService.searchCostcenter(event.query);
                this.costcenters = response.data.data;
            } catch (e) {
                console.log(e);
            }
        },
        realizarTransferencia() {
            this.loadingFullScreen = true;
            if (this.route.params?.id) {
                if (this.banco.wallet) {
                    console.log('chamou');
                    this.emprestimoService
                        .efetuarPagamentoTituloConsulta(this.route.params.id)
                        .then((response) => {
                            this.loadingFullScreen = false;
                            this.displayConfirmationMessage = `Tem certeza que deseja realizar o pagamento de ${this.contaspagar?.valor?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })} para ${response.data.creditParty.name}?`;
                            this.displayConfirmation = true;
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
                            this.loadingFullScreen = false;
                        });
                } else {
                    this.emprestimoService
                        .efetuarPagamentoEmprestimo(this.route.params.id)
                        .then((response) => {
                            if (response) {
                                this.toast.add({
                                    severity: ToastSeverity.SUCCESS,
                                    detail: 'Pagamento Efetuado',
                                    life: 3000
                                });
                                setTimeout(() => {
                                    this.router.push(`/aprovacao`);
                                }, 1200);
                            }
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
                            this.loadingFullScreen = false;
                        });
                }

                
            }

        },
        reprovarEmprestimo() {
			this.changeLoading();
            if (this.route.params?.id) {
                this.emprestimoService
                    .reprovarPagamentoContasAPagar(this.route.params.id)
                    .then((response) => {
                        if (response) {
                            this.toast.add({
                                severity: ToastSeverity.SUCCESS,
                                detail: 'Pagamento Reprovado!',
                                life: 3000
                            });

                            setTimeout(() => {
                                this.router.push(`/aprovacao`);
                            }, 1200);
                        }
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
						this.changeLoading();
					});
            }
        },
        save() {
            this.changeLoading();
            this.errors = [];

            if (this.selectedTipoDocumento.value == undefined) {
                this.toast.add({
                    severity: ToastSeverity.ERROR,
                    detail: 'Selecione o Tipo de Documento',
                    life: 3000
                });

                return false;
            }

            this.contaspagar.tipodoc = this.selectedTipoDocumento.value;
            this.contaspagar.costcenter = this.costcenter;
            this.contaspagar.banco = this.banco;
            this.contaspagar.fornecedor = this.fornecedor;

            this.contaspagarService
                .save(this.contaspagar)
                .then((response) => {
                    if (undefined != response.data.data) {
                        this.contaspagar = response.data.data;
                    }

                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: this.contaspagar?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
                        life: 3000
                    });

                    setTimeout(() => {
                        this.router.push({ name: 'contaspagarList' });
                    }, 1200);
                })
                .catch((error) => {
                    this.changeLoading();
                    this.errors = error?.response?.data?.errors;

                    if (error?.response?.status != 422) {
                        this.toast.add({
                            severity: ToastSeverity.ERROR,
                            detail: UtilService.message(error.response.data),
                            life: 3000
                        });
                    }

                    this.changeLoading();
                })
                .finally(() => {
                    this.changeLoading();
                });
        },
        closeConfirmationPagamento() {
            this.displayConfirmation = false;
            this.loadingFullScreen = true;
            this.emprestimoService
                .efetuarPagamentoTitulo(this.route.params.id)
                .then((response) => {
                    if (response) {
                        this.toast.add({
                            severity: ToastSeverity.SUCCESS,
                            detail: 'Pagamento Efetuado',
                            life: 3000
                        });
                        setTimeout(() => {
                            this.router.push(`/aprovacao`);
                        }, 1200);
                    }
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
                    this.loadingFullScreen = false;
                });
        },

        clearcontaspagar() {
            this.loading = true;
        },
        addCityBeforeSave(city) {
            // this.contaspagar.cities.push(city);
            this.changeLoading();
        },
        clearCicom() {
            this.loading = true;
        }
    },
    computed: {
        title() {
            return this.route.params?.id ? 'Aprovar Título' : 'Criar Título';
        }
    },
    mounted() {
        this.getcontaspagar();
    }
};
</script>

<template>
    <FullScreenLoading :isLoading="loadingFullScreen" />

    <Dialog header="Confirmation" v-model:visible="displayConfirmation" :style="{ width: '350px' }" :modal="true">
        <div class="flex align-items-center justify-content-center">
            <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
            <span>{{ displayConfirmationMessage }}</span>
        </div>
        <template #footer>
            <Button label="Não" icon="pi pi-times" @click="closeConfirmation" class="p-button-text" />
            <Button label="Sim" icon="pi pi-check" @click="closeConfirmationPagamento" class="p-button-text" autofocus />
        </template>
    </Dialog>
    <Toast />
    <!-- <LoadingComponent :loading="loading" /> -->
    <div class="grid flex flex-wrap mb-3 px-4 pt-2">
        <div class="col-8 px-0 py-0">
            <h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.BUILDING"></i> {{ title }}</h5>
        </div>
        <div class="col-4 px-0 py-0 text-right">
            <Button label="Voltar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.ANGLE_LEFT" @click.prevent="back" />
        </div>
    </div>
    <Card>
        <template #content>
            <div class="col-12">
                <div class="p-fluid formgrid grid">
                    <div class="field col-12 md:col-12">
                        <label for="firstname2">Fornecedor</label>
                        <AutoComplete
                            :modelValue="fornecedor"
                            v-model="fornecedor"
                            :dropdown="true"
                            :suggestions="fornecedores"
                            placeholder="Informe o nome do fornecedor"
                            class="w-full"
                            inputClass="w-full p-inputtext-sm"
                            @complete="searchFornecedor($event)"
                            optionLabel="nome_completo"
                        />
                    </div>
                    <div class="field col-12 md:col-12">
                        <label for="firstname2">Banco</label>
                        <AutoComplete
                            :modelValue="banco"
                            v-model="banco"
                            :dropdown="true"
                            :suggestions="bancos"
                            placeholder="Informe o nome do banco"
                            class="w-full"
                            inputClass="w-full p-inputtext-sm"
                            @complete="searchBanco($event)"
                            optionLabel="name_agencia_conta"
                        />
                    </div>
                    <div class="field col-12 md:col-12">
                        <label for="firstname2">Centro de Custo</label>
                        <AutoComplete
                            :modelValue="costcenter"
                            :dropdown="true"
                            v-model="costcenter"
                            :suggestions="costcenters"
                            placeholder="Informe o centro de custo"
                            class="w-full"
                            inputClass="w-full p-inputtext-sm"
                            @complete="searchCostcenter($event)"
                            optionLabel="name"
                        />
                    </div>
                    <div class="field col-12 md:col-6">
                        <label for="firstname2">Descricão</label>
                        <InputText id="firstname2" :modelValue="contaspagar?.descricao" v-model="contaspagar.descricao" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="lastname2">Tipo Documento</label>
                        <Dropdown v-model="selectedTipoDocumento" :options="tipoDocumento" optionLabel="name" placeholder="Selecione" />
                    </div>

                    <div class="field col-12 md:col-3">
                        <label for="zip">Valor</label>
                        <InputNumber
                            id="inputnumber"
                            :modelValue="contaspagar?.valor"
                            v-model="contaspagar.valor"
                            :mode="'currency'"
                            :currency="'BRL'"
                            :locale="'pt-BR'"
                            :precision="2"
                            class="w-full p-inputtext-sm"
                            :class="{ 'p-invalid': errors?.description }"
                        ></InputNumber>
                    </div>

                    <div v-if="selectedTipoDocumento.value == 'Boleto'" class="field col-12 md:col-12">
                        <label for="firstname2">Código de Barras</label>
                        <InputText id="firstname2" :modelValue="contaspagar?.cod_barras" v-model="contaspagar.cod_barras" type="text" />
                    </div>
                </div>
                <div class="col-12 px-0 py-0 text-right">
                    <Button label="Realizar Pagamento" class="p-button p-button-success p-button-sm" :icon="icons.CHECK" @click.prevent="realizarTransferencia" />
                    <Button  label="Reprovar Pagamento" class="p-button p-button-danger p-button-sm ml-3" :icon="icons.TIMES" type="button" @click.prevent="reprovarEmprestimo" />
                </div>
            </div>
        </template>
    </Card>
</template>
