<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import EmprestimoService from '@/service/EmprestimoService';
import BancoService from '@/service/BancoService';
import UtilService from '@/service/UtilService';
import EmprestimoParcelas from '../parcelas/Parcelas.vue';
import skeletonEmprestimos from '../skeleton/SkeletonEmprestimos.vue';
import EmprestimoAdd from '../emprestimos/EmprestimosAdd.vue';
import EmprestimosRefin from '../emprestimos/EmprestimosRefin.vue';
import EmprestimoRecalc from '../emprestimos/EmprestimosRecalc.vue';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'cicomForm',
    setup() {
        return {
            route: useRoute(),
            router: useRouter(),
            emprestimoService: new EmprestimoService(),
            bancoService: new BancoService(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    components: {
        EmprestimoParcelas,
        skeletonEmprestimos,
        EmprestimoAdd,
        EmprestimoRecalc,
        EmprestimosRefin
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
            loading: ref(false),
            selectedTipoSexo: ref(''),
            sexo: ref([
                { name: 'Masculino', value: 'M' },
                { name: 'Feminino', value: 'F' }
            ]),
            display: ref(false),
            displayMigrarBanco: ref(false),
            bancoDestinoMigrar: ref(null),
            bancosParaMigrar: ref([]),
            loadingBancosMigrar: ref(false),
            loadingMigrar: ref(false),
            saldoTotal: ref(0),
            valorDesconto: ref(0),
            error: ref('')
        };
    },
    methods: {
        open() {
            this.display.value = true;
        },
        async refinanciamento() {
            try {
                await this.emprestimoService.refinanciamento(this.route.params.id, this.saldoTotal);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
                    life: 3000
                });

                setTimeout(() => {
                    this.router.push({ name: 'emprestimosList' });
                }, 1200);
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        async renovacao() {
            try {
                await this.emprestimoService.renovacao(this.route.params.id, this.client.valor, this.client.valor_deposito);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
                    life: 3000
                });

                setTimeout(() => {
                    this.router.push({ name: 'emprestimosList' });
                }, 1200);
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        async close() {
            if (this.saldoTotal < this.valorDesconto) {
                this.error = `O valor de desconto de ${this.valorDesconto.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })} não pode ultrapassar o valor a pagar ${this.saldoTotal.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                })}`;
                setTimeout(() => {
                    this.error = '';
                }, 4000);
                return false;
            }

            try {
                await this.emprestimoService.baixaDesconto(this.route.params.id, this.saldoTotal - this.valorDesconto, this.saldoTotal);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
                    life: 3000
                });

                setTimeout(() => {
                    this.router.push({ name: 'emprestimosList' });
                }, 1200);
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        convertToNumber(valor) {
            return parseFloat(valor.replace('R$', '').replace('.', '').replace(',', '.'));
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
        eliminarParcelasModal() {
            this.parcelas = [];
        },
        async saveInfoDoEmprestimo(emprestimo) {
            this.client.valor = emprestimo.valor;
            this.client.lucro = emprestimo.lucro;
            this.client.juros = emprestimo.juros;
            this.client.liberar_minimo = emprestimo.liberar_minimo;

            await this.refinanciamento();

            this.save();
        },
		async saveInfoDaRenovacao(emprestimo) {
            this.client.valor = emprestimo.valor;
            this.client.lucro = emprestimo.lucro;
            this.client.juros = emprestimo.juros;
			this.client.valor_deposito = emprestimo.valor - emprestimo.pendente;

            await this.renovacao();

            this.saveRenovacao();
        },
        async getemprestimo() {
            if (this.route.params?.id) {
                this.client = ref(null);
                this.loading = true;
                
                try {
                    // Fazendo chamadas paralelas para melhor performance
                    const [basicoRes, parcelasRes, clienteRes, bancoRes, relacionadosRes] = await Promise.all([
                        this.emprestimoService.getBasico(this.route.params.id),
                        this.emprestimoService.getParcelas(this.route.params.id),
                        this.emprestimoService.getCliente(this.route.params.id),
                        this.emprestimoService.getBanco(this.route.params.id),
                        this.emprestimoService.getRelacionados(this.route.params.id)
                    ]);

                    // Montando o objeto client com dados básicos
                    this.client = {
                        id: basicoRes.data.id,
                        dt_lancamento: basicoRes.data.dt_lancamento,
                        valor: basicoRes.data.valor,
                        valor_deposito: basicoRes.data.valor_deposito,
                        lucro: basicoRes.data.lucro,
                        juros: basicoRes.data.juros,
                        cliente_cadastrado: basicoRes.data.cliente_cadastrado,
                        saldoareceber: basicoRes.data.saldoareceber,
                        saldoatrasado: basicoRes.data.saldoatrasado,
                        porcentagem: basicoRes.data.porcentagem,
                        saldo_total_parcelas_pagas: basicoRes.data.saldo_total_parcelas_pagas,
                        status: basicoRes.data.status,
                        telefone_empresa: basicoRes.data.telefone_empresa,
                        dt_envio_mensagem_renovacao: basicoRes.data.dt_envio_mensagem_renovacao
                    };

                    // Dados relacionados
                    this.city = clienteRes.data?.data || clienteRes.data;
                    this.banco = bancoRes.data?.data || bancoRes.data;
                    this.costcenter = basicoRes.data.costcenter;
                    this.consultor = basicoRes.data.consultor;
                    this.parcelas = parcelasRes.data?.data || parcelasRes.data;

                    const parcelasNaoBaixadas = this.parcelas.filter((parcela) => parcela.dt_baixa === '' || parcela.dt_baixa === null);

                    console.log('parcelasNaoBaixadas', parcelasNaoBaixadas);

                    this.saldoTotal = parcelasNaoBaixadas.reduce((total, parcela) => {
                        return total + parcela.saldo;
                    }, 0);
                } catch (error) {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error),
                        life: 3000
                    });
                } finally {
                    this.loading = false;
                }
            } else {
                this.client = ref({});
                this.client.address = [];
            }
        },
        back() {
            this.router.push(`/emprestimos`);
        },
        changeEnabled(enabled) {
            this.client.enabled = enabled;
        },
		saveRenovacao() {
            this.changeLoading();
            this.errors = [];

            this.client.cliente = this.city;
            this.client.banco = this.banco;
            this.client.costcenter = this.costcenter;
            this.client.consultor = this.consultor;
            this.client.parcelas = this.parcelas;

			console.log('client', this.client);	

            this.toast.add({
                severity: ToastSeverity.SUCCESS,
                detail: 'Gerando Chaves Pix, aguarde!',
                life: 3000
            });
			//saveRenovacao
            this.emprestimoService
                .saveRenovacao(this.client)
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
        save() {
            this.changeLoading();
            this.errors = [];

            this.client.cliente = this.city;
            this.client.banco = this.banco;
            this.client.costcenter = this.costcenter;
            this.client.consultor = this.consultor;
            this.client.parcelas = this.parcelas;

			console.log('client', this.client);
			console.log('parcelas enviadas (primeiras 3):', this.parcelas.slice(0, 3).map(p => ({
				parcela: p.parcela,
				valor: p.valor,
				lucro_real: p.lucro_real
			})));	

            this.toast.add({
                severity: ToastSeverity.SUCCESS,
                detail: 'Gerando Chaves Pix, aguarde!',
                life: 3000
            });
			//saveRenovacao
            this.emprestimoService
                .saveRefinanciamento(this.client)
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
        async openMigrarBancoModal() {
            this.displayMigrarBanco = true;
            this.bancoDestinoMigrar = null;
            this.bancosParaMigrar = [];
            this.loadingBancosMigrar = true;
            try {
                const response = await this.bancoService.getAll();
                const todos = response.data?.data ?? response.data ?? [];
                const permitidos = ['bcodex', 'apix', 'xgate', 'velana', 'cora'];
                this.bancosParaMigrar = (Array.isArray(todos) ? todos : []).filter((b) => {
                    const type = b.bank_type || (b.wallet ? 'bcodex' : 'normal');
                    const ehPermitido = permitidos.includes(type) || b.wallet === true || b.wallet === 1;
                    return ehPermitido && b.id !== this.banco?.id;
                });
            } catch (e) {
                this.toast.add({
                    severity: ToastSeverity.ERROR,
                    detail: UtilService.message(e?.response?.data || e),
                    life: 3000
                });
            } finally {
                this.loadingBancosMigrar = false;
            }
        },
        async migrarBancoConfirm() {
            if (!this.bancoDestinoMigrar) {
                this.toast.add({
                    severity: ToastSeverity.WARN,
                    detail: 'Selecione o banco de destino.',
                    life: 3000
                });
                return;
            }
            this.loadingMigrar = true;
            try {
                await this.emprestimoService.migrarBanco(this.route.params.id, this.bancoDestinoMigrar);
                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Empréstimo migrado com sucesso. As cobranças PIX serão geradas no novo banco.',
                    life: 4000
                });
                this.displayMigrarBanco = false;
                this.bancoDestinoMigrar = null;
                this.getemprestimo();
            } catch (e) {
                this.toast.add({
                    severity: ToastSeverity.ERROR,
                    detail: UtilService.message(e?.response?.data || e),
                    life: 4000
                });
            } finally {
                this.loadingMigrar = false;
            }
        }
    },
    computed: {
        title() {
            return 'Visualizar Empréstimo';
        }
    },
    mounted() {
        this.getemprestimo();
    }
};
</script>

<template>
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
                            <label for="firstname2">Cadastrado por</label>
                            <Chip :label="client?.cliente_cadastrado" class="w-full p-inputtext-sm"></Chip>
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
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Lucro Previsto</label>
                            <Chip :label="client?.lucro?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></Chip>
                        </div>
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Parcelas</label>
                            <Chip :label="`${parcelas?.length.toString().padStart(3, '0')}`" class="w-full p-inputtext-sm"> </Chip>
                        </div>
                        <div class="field col-12 md:col-3">
                            <label for="firstname2">Juros</label>
                            <Chip :label="`% ${client?.juros}`" class="w-full p-inputtext-sm"></Chip>
                        </div>
                    </div>
                </div>

                <div v-if="saldoTotal > 0" class="grid flex flex-wrap mb-3 px-4 pt-2">
                    <div class="col-12 px-0 py-0 text-right">
                        <Button label="Migrar para outro banco" class="p-button-sm p-button-outlined p-button-secondary mr-2" icon="pi pi-refresh" @click="openMigrarBancoModal" />
                        <Button label="Realizar Baixa com Desconto" class="p-button-sm p-button-info" :icon="icons.PLUS" @click="display = true" />
                    </div>
                </div>

                <EmprestimoRecalc
                    :address="this.client"
                    :oldCicom="this.oldClient"
                    :loading="loading"
                    :parcela="this.parcelas[0]"
                    @updateCicom="clearCicom"
                    @addCityBeforeSave="addCityBeforeSave"
                    @changeLoading="changeLoading"
                    @saveParcela="saveNewParcela"
                    @eliminarParcelas="eliminarParcelasModal"
                    @saveInfoEmprestimo="saveInfoDoEmprestimo"
                    v-if="true"
                />

                <EmprestimosRefin
                    :address="this.client"
                    :oldCicom="this.oldClient"
                    :loading="loading"
                    :parcela="this.parcelas[0]"
                    :emprestimoFront="this.emprestimo"
                    @updateCicom="clearCicom"
                    @addCityBeforeSave="addCityBeforeSave"
                    @changeLoading="changeLoading"
                    @saveParcela="saveNewParcela"
                    @eliminarParcelas="eliminarParcelasModal"
                    @saveInfoRenovacao="saveInfoDaRenovacao"
                    v-if="this.client.saldoareceber > 0 && this.client.dt_envio_mensagem_renovacao != null"
                />

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
                    :aprovacao="false"
                    @updateCicom="clearCicom"
                    @addCityBeforeSave="addCityBeforeSave"
                    @changeLoading="changeLoading"
                    v-if="true"
                />

                <Dialog header="Migrar para outro banco" v-model:visible="displayMigrarBanco" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true" :closable="!loadingMigrar">
                    <p class="line-height-3 mb-4">
                        As cobranças PIX serão geradas no novo banco. Continuar?
                    </p>
                    <div class="p-fluid formgrid grid">
                        <div class="field col-12 md:col-12">
                            <label for="bancoDestino">Banco destino</label>
                            <Dropdown
                                id="bancoDestino"
                                v-model="bancoDestinoMigrar"
                                :options="bancosParaMigrar"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Selecione o banco"
                                class="w-full"
                                :loading="loadingBancosMigrar"
                            />
                        </div>
                    </div>
                    <template #footer>
                        <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="displayMigrarBanco = false" :disabled="loadingMigrar" />
                        <Button label="Migrar" icon="pi pi-check" class="p-button-outlined" @click="migrarBancoConfirm" :loading="loadingMigrar" />
                    </template>
                </Dialog>

                <Dialog header="Baixa com desconto" v-model:visible="display" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
                    <p class="line-height-3 mb-4">
                        Para realizar a baixa com desconto atualmente o valor que falta receber é de
                        <b style="color: red">{{
                            saldoTotal.toLocaleString('pt-BR', {
                                style: 'currency',
                                currency: 'BRL'
                            })
                        }}</b>
                        <br /><br />
                        Digite abaixo o valor do desconto, automaticamente o sistema efetuara a baixa de todas as parcelas, em movimentação financeira vai conter exatamente o valor de
                        <b style="color: red">{{ saldoTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }} - o valor a ser descontado</b>.
                    </p>
                    <div class="p-fluid formgrid grid">
                        <div class="field col-12 md:col-12">
                            <label for="zip">Valor do Desconto</label>
                            <InputNumber id="inputnumber" :modelValue="valorDesconto" v-model="valorDesconto" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
                        </div>
                    </div>
                    <Message v-if="error" severity="error">{{ error }}</Message>
                    <template #footer>
                        <Button label="Realizar Baixa" @click="close" icon="pi pi-check" class="p-button-outlined" />
                    </template>
                </Dialog>
            </template>
        </Card>
    </div>
</template>
