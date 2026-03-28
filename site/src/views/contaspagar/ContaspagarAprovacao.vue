<script>
import { ref, unref } from 'vue';
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
            displayConfirmationChavePix: ref(null),
            displayConfirmationNome: ref(null),
            displayConfirmationValor: ref(null),
            loadingFullScreen: ref(false),
            displayModalDocumentoXgate: ref(false),
            xgateDocumentoTipo: ref(null),
            pendingXgateConsultaParaConfirmacao: null
        };
    },
    methods: {
        getFornecedorDesembrulhado() {
            const f = unref(this.fornecedor);
            if (f && typeof f === 'object' && f.nome_completo !== undefined) {
                return f;
            }
            const cp = unref(this.contaspagar);
            return cp?.fornecedor ?? null;
        },
        /** CPF no campo principal (cpfcnpj) + CNPJ no campo adicional — mesma regra da API */
        fornecedorPrecisaEscolhaDocumentoXgate() {
            const f = this.getFornecedorDesembrulhado();
            if (!f) {
                return false;
            }
            const dMain = String(f.cpfcnpj || '').replace(/\D/g, '');
            const dCnpjExtra = String(f.cnpj || '').replace(/\D/g, '');
            return dMain.length === 11 && dCnpjExtra.length >= 14;
        },
        fecharModalDocumentoXgate() {
            this.displayModalDocumentoXgate = false;
            this.pendingXgateConsultaParaConfirmacao = null;
        },
        confirmarDocumentoXgate(tipo) {
            this.xgateDocumentoTipo = tipo;
            this.displayModalDocumentoXgate = false;
            if (this.pendingXgateConsultaParaConfirmacao) {
                const { data, valor, isXgate, isApix } = this.pendingXgateConsultaParaConfirmacao;
                this.pendingXgateConsultaParaConfirmacao = null;
                this.abrirDialogoConfirmacaoTitulo(data, valor, isXgate, isApix);
                return;
            }
            this.executarConsultaTransferenciaTitulo();
        },
        abrirDialogoConfirmacaoTitulo(consultData, valor, isXgate, isApix) {
            const f = this.getFornecedorDesembrulhado();
            const nomeBeneficiario = consultData?.creditParty?.name ?? f?.nome_completo ?? 'Não informado';
            const mensagem = `Tem certeza que deseja realizar o pagamento de ${valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })} para ${nomeBeneficiario}?`;
            if ((isXgate || isApix) && consultData?.chave_pix) {
                this.displayConfirmationChavePix = consultData.chave_pix;
                this.displayConfirmationNome = nomeBeneficiario;
                this.displayConfirmationValor = valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            } else {
                this.displayConfirmationChavePix = null;
                this.displayConfirmationNome = null;
                this.displayConfirmationValor = null;
            }
            this.displayConfirmationMessage = mensagem;
            this.displayConfirmation = true;
        },
        executarConsultaTransferenciaTitulo() {
            const bancoU = unref(this.banco);
            const isXgate = bancoU?.bank_type === 'xgate';
            const isApix = bancoU?.bank_type === 'apix';
            const cp = unref(this.contaspagar);
            const valor = cp?.valor ?? 0;
            this.loadingFullScreen = true;
            this.emprestimoService
                .efetuarPagamentoTituloConsulta(this.route.params.id)
                .then((response) => {
                    const data = response.data;
                    const requerDoc = data?.requer_escolha_documento_xgate === true;
                    if (isXgate && requerDoc && !this.xgateDocumentoTipo) {
                        this.pendingXgateConsultaParaConfirmacao = { data, valor, isXgate, isApix };
                        this.displayModalDocumentoXgate = true;
                        return;
                    }
                    this.abrirDialogoConfirmacaoTitulo(data, valor, isXgate, isApix);
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
        changeLoading() {
            this.loading = !this.loading;
        },
        openConfirmation() {
            this.displayConfirmation = true;
        },

        closeConfirmation() {
            this.displayConfirmation = false;
            this.displayConfirmationChavePix = null;
            this.displayConfirmationNome = null;
            this.displayConfirmationValor = null;
            this.xgateDocumentoTipo = null;
            this.pendingXgateConsultaParaConfirmacao = null;
            this.toast.add({ severity: 'info', summary: 'Cancelar', detail: 'Pagamento não realizado!', life: 3000 });
        },
        getcontaspagar() {
            if (this.route.params?.id) {
                this.contaspagar = ref(null);
                this.loading = true;
                this.contaspagarService
                    .get(this.route.params.id)
                    .then((response) => {
                        this.contaspagar = response.data?.data ?? response.data;
                        this.selectedTipoDocumento = { name: this.contaspagar?.tipodoc, value: this.contaspagar?.tipodoc };
                        this.costcenter = this.contaspagar?.costcenter;
                        this.fornecedor = this.contaspagar?.fornecedor;
                        this.banco = this.contaspagar?.banco;
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
            if (!this.route.params?.id) {
                return;
            }

            const bancoU = unref(this.banco);
            const isXgate = bancoU?.bank_type === 'xgate';
            if (isXgate && this.fornecedorPrecisaEscolhaDocumentoXgate()) {
                this.displayModalDocumentoXgate = true;
                return;
            }

            const isApix = bancoU?.bank_type === 'apix';
            if (bancoU?.wallet || isXgate || isApix) {
                this.executarConsultaTransferenciaTitulo();
            } else {
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
            const payload = {};
            if (unref(this.banco)?.bank_type === 'xgate' && this.xgateDocumentoTipo) {
                payload.documento_xgate = this.xgateDocumentoTipo;
            }
            this.emprestimoService
                .efetuarPagamentoTitulo(this.route.params.id, payload)
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
                    this.xgateDocumentoTipo = null;
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

    <Dialog
        header="Documento para a transferência (XGate)"
        v-model:visible="displayModalDocumentoXgate"
        :style="{ width: '440px' }"
        :modal="true"
    >
        <p class="m-0 line-height-3">
            Este fornecedor tem <strong>CPF</strong> no campo principal e <strong>CNPJ</strong> adicional cadastrado. Qual documento enviar à XGate na transferência PIX?
        </p>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="fecharModalDocumentoXgate" />
            <Button label="Usar CPF" icon="pi pi-id-card" class="p-button-secondary" @click="confirmarDocumentoXgate('cpf')" />
            <Button label="Usar CNPJ" icon="pi pi-briefcase" @click="confirmarDocumentoXgate('cnpj')" />
        </template>
    </Dialog>

    <Dialog header="Confirmation" v-model:visible="displayConfirmation" :style="{ width: displayConfirmationChavePix ? '420px' : '350px' }" :modal="true">
        <div class="flex flex-column gap-2">
            <div class="flex align-items-center">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
                <span>{{ displayConfirmationMessage }}</span>
            </div>
            <div v-if="displayConfirmationChavePix" class="mt-3 p-3 surface-100 border-round">
                <p class="font-semibold mb-2 text-color-secondary">Verifique os dados antes de autorizar o pagamento:</p>
                <p class="my-1"><strong>Beneficiário:</strong> {{ displayConfirmationNome }}</p>
                <p class="my-1"><strong>Chave PIX para pagamento:</strong></p>
                <p class="my-1 p-2 surface-200 border-round word-break">{{ displayConfirmationChavePix }}</p>
                <p class="my-1"><strong>Valor:</strong> {{ displayConfirmationValor }}</p>
            </div>
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

                    <div v-if="contaspagar?.anexos_urls && contaspagar.anexos_urls.length > 0" class="field col-12 md:col-12">
                        <label>Comprovantes</label>
                        <div class="flex flex-wrap gap-2 p-3 surface-100 border-round">
                            <i class="pi pi-paperclip text-green-600" style="font-size: 1.5rem"></i>
                            <a
                                v-for="(url, idx) in contaspagar.anexos_urls"
                                :key="idx"
                                :href="url"
                                target="_blank"
                                class="font-semibold text-primary flex align-items-center gap-1"
                            >
                                <i class="pi pi-external-link"></i>
                                {{ contaspagar.anexos_urls.length > 1 ? `Comprovante ${idx + 1}` : 'Comprovante' }}
                            </a>
                        </div>
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
