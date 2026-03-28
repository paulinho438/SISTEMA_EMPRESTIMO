<script>
import { ref, unref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import EmprestimoService from '@/service/EmprestimoService';
import UtilService from '@/service/UtilService';
import EmprestimoParcelas from '../parcelas/Parcelas.vue';
import skeletonEmprestimos from '../skeleton/SkeletonEmprestimos.vue';
import EmprestimoAdd from '../emprestimos/EmprestimosAdd.vue';
import PermissionsService from '@/service/PermissionsService';
import { ToastSeverity, PrimeIcons } from 'primevue/api';
import { useConfirm } from 'primevue/useconfirm';
import FullScreenLoading from '@/components/FullScreenLoading.vue'; 

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'cicomForm',
   
    setup() {
        return {
            route: useRoute(),
            router: useRouter(),
            emprestimoService: new EmprestimoService(),
            permissionsService: new PermissionsService(),
            icons: PrimeIcons,
            toast: useToast(),
            confirmPopup: useConfirm()
        };
    },
    components: {
        EmprestimoParcelas,
        skeletonEmprestimos,
        EmprestimoAdd,
        FullScreenLoading
    },
    data() {
        return {
            client: ref({}),
            oldClient: ref(null),
            errors: ref([]),
            city: ref(null),
            cities: ref([]),
            bancos: ref([]),
            banco: ref(null),
            costcenters: ref([]),
            costcenter: ref(null),
            consultores: ref([]),
            consultor: ref(null),
            parcelas: ref([]),
            address: ref({
                id: 1,
                name: 'ok',
                geolocalizacao: '17.23213, 12.455345'
            }),
            loading: ref(true),
            selectedTipoSexo: ref(''),
            sexo: ref([
                { name: 'Masculino', value: 'M' },
                { name: 'Feminino', value: 'F' }
            ]),
            displayConfirmation: ref(false),
            displayConfirmationMessage: ref(''),
            displayConfirmationChavePix: ref(null),
            displayConfirmationNome: ref(null),
            displayConfirmationValor: ref(null),
            loadingFullScreen: ref(false),
            /** XGate: cliente com CPF e CNPJ — escolha antes da consulta/confirmação */
            displayModalDocumentoXgate: ref(false),
            xgateDocumentoTipo: ref(null),
            /** Consulta já retornou mas falta escolher documento antes da confirmação */
            pendingXgateConsultaParaConfirmacao: null
        };
    },
    methods: {
        /** city/client vêm como ref() no data — sem unref, cnpj fica invisível nos métodos */
        getClienteEmprestimoDesembrulhado() {
            const cidade = unref(this.city);
            if (cidade && typeof cidade === 'object' && 'nome_completo' in cidade) {
                return cidade;
            }
            const emp = unref(this.client);
            return emp?.cliente ?? null;
        },
        clienteTemCnpjPreenchido() {
            const cliente = this.getClienteEmprestimoDesembrulhado();
            const raw = cliente?.cnpj;
            if (raw === null || raw === undefined || String(raw).trim() === '') {
                return false;
            }
            const digits = String(raw).replace(/\D/g, '');
            return digits.length >= 14;
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
                this.abrirDialogoConfirmacaoTransferencia(data, valor, isXgate, isApix);
                return;
            }
            this.executarConsultaTransferenciaWalletOuPix();
        },
        abrirDialogoConfirmacaoTransferencia(consultData, valor, isXgate, isApix) {
            const cliente = this.getClienteEmprestimoDesembrulhado();
            const nomeBeneficiario =
                consultData?.creditParty?.name ?? cliente?.nome_completo ?? 'Não informado';
            const mensagem = `Tem certeza que deseja realizar a transferência de ${valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })} para ${nomeBeneficiario}?`;
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
        executarConsultaTransferenciaWalletOuPix() {
            const emp = unref(this.client);
            let valor = 0;
            if (emp?.valor_deposito) {
                valor = emp.valor_deposito;
            } else if (emp?.valor) {
                valor = emp.valor;
            }

            const bancoU = unref(this.banco);
            const isXgate = bancoU?.bank_type === 'xgate';
            const isApix = bancoU?.bank_type === 'apix';

            this.loadingFullScreen = true;
            this.emprestimoService
                .efetuarPagamentoEmprestimoConsulta(this.route.params.id)
                .then((response) => {
                    const data = response.data;
                    const requerDoc = data?.requer_escolha_documento_xgate === true;
                    if (isXgate && requerDoc && !this.xgateDocumentoTipo) {
                        this.pendingXgateConsultaParaConfirmacao = { data, valor, isXgate, isApix };
                        this.displayModalDocumentoXgate = true;
                        return;
                    }
                    this.abrirDialogoConfirmacaoTransferencia(data, valor, isXgate, isApix);
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
        async searchCliente(event) {
            try {
                let response = await this.emprestimoService.searchClient(event.query);
                this.cities = response.data.data;
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
        async searchConsultor(event) {
            try {
                let response = await this.emprestimoService.searchConsultor(event.query);
                this.consultores = response.data;
            } catch (e) {
                console.log(e);
            }
        },
        saveNewParcela(address) {
            this.parcelas.push(address);
        },
        saveInfoDoEmprestimo(emprestimo) {
            this.client.valor = emprestimo.valor;
            this.client.lucro = emprestimo.lucro;
            this.client.juros = emprestimo.juros;
        },
        realizarTransferencia() {
            if (!this.route.params?.id) {
                return;
            }

            const bancoU = unref(this.banco);
            const isXgate = bancoU?.bank_type === 'xgate';
            if (isXgate && this.clienteTemCnpjPreenchido()) {
                this.displayModalDocumentoXgate = true;
                return;
            }

            const isApix = bancoU?.bank_type === 'apix';
            if (bancoU?.wallet || isXgate || isApix) {
                this.executarConsultaTransferenciaWalletOuPix();
            } else {
                this.loadingFullScreen = true;
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
        },
        reprovarEmprestimo() {
            if (this.route.params?.id) {
                this.emprestimoService
                    .reprovarEmprestimo(this.route.params.id)
                    .then((response) => {
                        if (response) {
                            this.toast.add({
                                severity: ToastSeverity.SUCCESS,
                                detail: 'Empréstimo Reprovado!',
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
                    .finally(() => {});
            }
        },
        getemprestimo() {
            if (this.route.params?.id) {
                this.client = ref(null);
                this.loading = true;
                this.emprestimoService
                    .get(this.route.params.id)
                    .then((response) => {
                        this.client = response.data?.data;
                        this.city = response.data?.data.cliente;
                        this.banco = response.data?.data.banco;
                        this.costcenter = response.data?.data.costcenter;
                        this.consultor = response.data?.data.consultor;
                        this.parcelas = response.data?.data.parcelas;
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
                this.client = ref({});
                this.client.address = [];
            }
        },
        back() {
            this.router.push(`/aprovacao`);
        },
        changeEnabled(enabled) {
            this.client.enabled = enabled;
        },
        save() {
            this.changeLoading();
            this.errors = [];

            this.client.cliente = this.city;
            this.client.banco = this.banco;
            this.client.costcenter = this.costcenter;
            this.client.consultor = this.consultor;
            this.client.parcelas = this.parcelas;

            this.toast.add({
                severity: ToastSeverity.SUCCESS,
                detail: 'Gerando Chaves Pix, aguarde!',
                life: 3000
            });

            this.emprestimoService
                .save(this.client)
                .then((response) => {
                    if (undefined != response.data.data) {
                        this.client = response.data.data;
                    }

                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
                        life: 3000
                    });

                    setTimeout(() => {
                        this.router.push({ name: 'emprestimosList' });
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

        clearclient() {
            this.loading = true;
        },
        addCityBeforeSave(city) {
            // this.client.cities.push(city);
            this.changeLoading();
        },
        clearCicom() {
            this.getemprestimo();
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

        closeConfirmationPagamento() {
            this.displayConfirmation = false;
            this.loadingFullScreen = true;
            const payload = {};
            if (unref(this.banco)?.bank_type === 'xgate' && this.xgateDocumentoTipo) {
                payload.documento_xgate = this.xgateDocumentoTipo;
            }
            this.emprestimoService
                .efetuarPagamentoEmprestimo(this.route.params.id, payload)
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
        }
    },
    computed: {
        title() {
            return 'Aprovar Empréstimo';
        }
    },
    mounted() {
        this.getemprestimo();
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
            Este cliente possui <strong>CPF</strong> e <strong>CNPJ</strong> cadastrados. Qual documento deve ser enviado à API da XGate na transferência PIX?
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
    <div class="grid flex flex-wrap mb-3 px-4 pt-2">
        <div class="col-8 px-0 py-0">
            <h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.BUILDING"></i> {{ title }}</h5>
        </div>
        <div class="col-4 px-0 py-0 text-right">
            <Button label="Voltar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.ANGLE_LEFT" @click.prevent="back" />
            <Button v-if="!this.route.params?.id" label="Salvar" class="p-button p-button-info p-button-sm ml-3" :icon="icons.SAVE" type="button" @click.prevent="save" />
        </div>
    </div>
    <skeletonEmprestimos :loading="loading" />
    <div v-if="!loading">
        <Toast />
        <Card>
            <template #content>
                <div class="col-12">
                    <div class="p-fluid formgrid grid">
                        
                        <div class="field col-12 md:col-12">
                            <label for="firstname2">Consultor</label>
                            <Chip :label="consultor?.nome_completo" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-12">
                            <label for="firstname2">Cliente</label>
                            <Chip :label="city?.nome_completo_cpf" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-12">
                            <label for="firstname2">Cliente cadastrado por</label>
                            <Chip :label="city?.nome_usuario_criacao ?? 'Sem informação cadastrada'" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-12">
                            <label for="firstname2">Banco</label>
                            <Chip :label="banco?.name_agencia_conta" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-12">
                            <label for="firstname2">Centro de Custo</label>
                            <Chip :label="costcenter?.name" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Valor do Emprestimo</label>
                            <Chip :label="client?.valor?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div v-if="client?.valor_deposito != null" class="field col-12 md:col-3">
                            <label for="firstname2">Valor a depositar</label>
                            <Chip :label="client?.valor_deposito?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Lucro Previsto</label>
                            <Chip :label="client?.lucro?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Parcelas</label>
                            <Chip :label="`${parcelas?.length.toString().padStart(3, '0')}`" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Juros</label>
                            <Chip :label="`% ${client?.juros}`" class="w-full p-inputtext-sm"></Chip>
                        </div>
                    </div>
                    <div class="col-12 px-0 py-0 text-right">
                        <Button
                            v-if="permissionsService.hasPermissions('view_emprestimos_autorizar_pagamentos')"
                            label="Realizar Transferência"
                            class="p-button p-button-success p-button-sm"
                            :icon="icons.CHECK"
                            @click.prevent="realizarTransferencia()"
                        />
                        <Button label="Reprovar Empréstimo" class="p-button p-button-danger p-button-sm ml-3" :icon="icons.TIMES" type="button" @click.prevent="reprovarEmprestimo" />
                    </div>
                </div>
                <EmprestimoAdd
                    :address="this.client"
                    :oldCicom="this.oldClient"
                    :loading="loading"
                    @updateCicom="clearCicom"
                    @addCityBeforeSave="addCityBeforeSave"
                    @changeLoading="changeLoading"
                    @saveParcela="saveNewParcela"
                    @saveInfoEmprestimo="saveInfoDoEmprestimo"
                    v-if="true"
                />
                <EmprestimoParcelas
                    :address="this.parcelas"
                    :oldCicom="this.oldClient"
                    :loading="loading"
                    :viewCreated="false"
                    :aprovacao="true"
                    @updateCicom="clearCicom"
                    @addCityBeforeSave="addCityBeforeSave"
                    @changeLoading="changeLoading"
                    v-if="true"
                />
            </template>
        </Card>
    </div>
</template>
