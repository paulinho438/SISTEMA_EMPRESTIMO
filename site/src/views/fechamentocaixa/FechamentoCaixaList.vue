<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import MovimentacaofinanceiraService from '@/service/MovimentacaofinanceiraService';
import FullScreenLoading from '@/components/FullScreenLoading.vue'; 
import BancoService from '@/service/BancoService';
import PermissionsService from '@/service/PermissionsService';
import EmprestimoService from '@/service/EmprestimoService';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

import UtilService from '@/service/UtilService';

export default {
    name: 'CicomList',
    components: {
        FullScreenLoading, // Registra o componente
    },
    setup() {
        return {
            movimentacaofinanceiraService: new MovimentacaofinanceiraService(),
            bancoService: new BancoService(),
            permissionsService: new PermissionsService(),
            emprestimoService: new EmprestimoService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            loading: ref(false), 
            MovimentacaofinanceiraReal: ref([]),
            Movimentacaofinanceira: ref([]),
            loading: ref(false),
            filters: ref(null),
            bancos: ref([]),
            banco: ref(null),
            form: ref({}),
            valorRecebido: ref(0),
            valorPago: ref(0),
            confirmPopup: useConfirm(),
            displayFechamento: ref({
                enabled: false,
                saldobanco: 0,
                saldocaixa: 0,
                saldocaixapix: 0
            }),
            displayDepositar: ref({
                enabled: false,
                valor: 0
            }),
            displaySacar: ref({
                enabled: false,
                valor: 0,
                mensagemRecebedor: null
            }),
            displayChavepix: ref({
                enabled: false,
                valor: ''
            })
        };
    },
    methods: {
        async searchBanco(event) {
            try {
                let response = await this.emprestimoService.searchbancofechamento(event.query);
                this.bancos = response.data.data;
            } catch (e) {
                console.log(e);
            }
        },
        async selecionarBanco() {
            try {
                let response = await this.emprestimoService.searchbancofechamento('');
                this.bancos = response.data.data;
                this.banco = this.bancos[0];
            } catch (e) {
                console.log(e);
            }
        },
        async close() {
            try {
                await this.bancoService.alterarcaixa(this.banco.id, this.displayFechamento.saldobanco, this.displayFechamento.saldocaixa, this.displayFechamento.saldocaixapix);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Alteração de Caixa Concluido!',
                    life: 3000
                });

                setTimeout(() => {
                    this.banco.saldo = this.displayFechamento.saldobanco;
                    this.banco.caixa_empresa = this.displayFechamento.saldocaixa;
                    this.banco.caixa_pix = this.displayFechamento.saldocaixapix;
                    this.displayFechamento.enabled = false;
                }, 1200);
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        async depositar() {
            try {
                let a = await this.bancoService.depositar(this.banco.id, this.displayDepositar.valor);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Chave pix para depósito criada com sucesso!',
                    life: 3000
                });

                this.displayDepositar.valor = 0;
                this.displayDepositar.enabled = false;

                this.displayChavepix.enabled = true;
                this.displayChavepix.valor = a.data.chavepix;
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        async sacar(event) {
            try {
                if (this.banco.wallet) {
                    this.bancoService
                        .saqueConsulta(this.banco.id, this.displaySacar.valor)
                        .then((response) => {
                            this.displaySacar.mensagemRecebedor = `Tem certeza que deseja realizar o saque de ${this.displaySacar.valor?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })} para ${response.data.creditParty.name}?`;
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
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        async efetivarSaque(event) {
            try {
                if (this.banco.wallet) {
                    this.bancoService
                        .efetuarSaque(this.banco.id, this.displaySacar.valor)
                        .then((response) => {
                            this.displaySacar.enabled = false;
                            this.banco.saldo_banco -= this.displaySacar.valor;
                            this.toast.add({
                                    severity: ToastSeverity.SUCCESS,
                                    detail: 'Saque Efetuado com sucesso',
                                    life: 3000
                                });
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
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
        },
        confirm(event) {
            this.confirmPopup.require({
                target: event.target,
                message: 'Tem certeza de que deseja realizar o fechamento de caixa? Este processo é irreversível.',
                icon: 'pi pi-exclamation-triangle',
                acceptLabel: 'Sim',
                rejectLabel: 'Não',
                accept: () => {
                    this.fecharCaixa();
                },
                reject: () => {
                    this.toast.add({ severity: 'info', summary: 'Cancelar', detail: 'Rotina não iniciada!', life: 3000 });
                }
            });
        },
        async fecharCaixa() {
            try {
                this.loading = true;
                await this.bancoService.fechamentoCaixa(this.banco.id);

                this.toast.add({
                    severity: ToastSeverity.SUCCESS,
                    detail: 'Fechamento de Caixa Concluido!',
                    life: 3000
                });

                setTimeout(() => {
                    this.banco.saldo += this.banco.caixa_pix;
                    this.banco.caixa_pix = 0;
                }, 1200);
            } catch (e) {
                console.log(e);
            }

            this.display = false;
            this.valorDesconto = 0;
            this.loading = false;
        },
        modalFechamento() {
            this.displayFechamento.enabled = !this.displayFechamento.enabled;
        },
        modalSacar() {
            this.displaySacar.enabled = !this.displaySacar.enabled;
        },
        modalDepositar() {
            this.displayDepositar.enabled = !this.displayDepositar.enabled;
        },
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********';
        },
        getMovimentacaofinanceira() {
            this.loading = true;

            this.movimentacaofinanceiraService
                .getAll()
                .then((response) => {
                    this.Movimentacaofinanceira = response.data.data;
                    this.MovimentacaofinanceiraReal = response.data.data;

                    this.valorRecebido = 0;

                    response.data.data.forEach((item) => {
                        if (item.tipomov === 'E') {
                            this.valorRecebido += item.valor;
                        }
                    });

                    this.valorPago = 0;

                    response.data.data.forEach((item) => {
                        if (item.tipomov === 'S') {
                            this.valorPago += item.valor;
                        }
                    });

                    this.setLastWeekDates();
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: error.message,
                        life: 3000
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        setLastWeekDates() {
            const today = new Date();
            const lastWeekEnd = new Date(today);
            lastWeekEnd.setDate(today.getDate());
            const lastWeekStart = new Date(lastWeekEnd);
            lastWeekStart.setDate(lastWeekEnd.getDate() - 6);

            this.form.dt_inicio = lastWeekStart;
            this.form.dt_final = lastWeekEnd;

            this.busca(); // Call the search method to filter data
        },
        busca() {
            if (!this.form.dt_inicio || !this.form.dt_final) {
                this.toast.add({
                    severity: ToastSeverity.WARN,
                    detail: 'Selecione as datas de início e fim',
                    life: 3000
                });
                return;
            }

            const dt_inicio = new Date(this.form.dt_inicio);
            const dt_final = new Date(this.form.dt_final);
            dt_final.setHours(23, 59, 59, 999); // Ensure the end date covers the entire day

            this.Movimentacaofinanceira = this.MovimentacaofinanceiraReal.filter((mov) => {
                const [day, month, year] = mov.dt_movimentacao.split('/').map(Number);
                const dt_mov = new Date(year, month - 1, day); // JavaScript Date uses zero-based month index
                return dt_mov >= dt_inicio && dt_mov <= dt_final;
            });

            this.valorRecebido = 0;
            this.valorPago = 0;

            this.Movimentacaofinanceira.forEach((item) => {
                if (item.tipomov === 'E') {
                    this.valorRecebido += item.valor;
                } else if (item.tipomov === 'S') {
                    this.valorPago += item.valor;
                }
            });
        },
        editCategory(id) {
            if (undefined === id) this.router.push('/Movimentacaofinanceira/add');
            else this.router.push(`/Movimentacaofinanceira/${id}/edit`);
        },
        copiarChavePix() {
            // Cria um elemento de input temporário
            const input = document.createElement('input');
            input.value = this.displayChavepix.valor; // Define o valor como a chave Pix
            document.body.appendChild(input); // Adiciona o input ao DOM
            input.select(); // Seleciona o texto
            document.execCommand('copy'); // Copia para a área de transferência
            document.body.removeChild(input); // Remove o input temporário
            alert('Chave Pix copiada para a área de transferência!'); // Alerta ao usuário
        },
        deleteCategory(permissionId) {
            this.loading = true;

            this.movimentacaofinanceiraService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getMovimentacaofinanceira();
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: error?.data?.message,
                        life: 3000
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        initFilters() {
            this.filters = {
                nome_completo: { value: null, matchMode: FilterMatchMode.CONTAINS }
            };
        },
        clearFilter() {
            this.initFilters();
        }
    },
    beforeMount() {
        this.initFilters();
    },
    mounted() {
        this.permissionsService.hasPermissionsView('view_movimentacaofinanceira');
        this.getMovimentacaofinanceira();

        this.selecionarBanco();
    }
};
</script>

<template>
    <FullScreenLoading :isLoading="loading" />
    <Dialog header="Encerramento de Caixa" v-model:visible="displayFechamento.enabled" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <div class="field col-12 md:col-12">
            <label for="zip">Saldo no Banco</label>
            <InputNumber id="inputnumber" :modelValue="displayFechamento?.saldobanco" v-model="displayFechamento.saldobanco" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
        </div>
        <div class="field col-12 md:col-16 -mt-4">
            <label for="zip">Saldo no Caixa</label>
            <InputNumber id="inputnumber" :modelValue="displayFechamento?.saldocaixa" v-model="displayFechamento.saldocaixa" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
        </div>
        <div class="field col-12 md:col-16 -mt-4">
            <label for="zip">Saldo no Caixa Pix</label>
            <InputNumber id="inputnumber" :modelValue="displayFechamento?.saldocaixapix" v-model="displayFechamento.saldocaixapix" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
        </div>
        <template #footer>
            <Button label="Fechar Caixa" @click="close" icon="pi pi-check" class="p-button-outlined" />
        </template>
    </Dialog>

    <Dialog header="Depositar" v-model:visible="displayDepositar.enabled" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <div class="field col-12 md:col-12">
            <label for="zip">Valor</label>
            <InputNumber id="inputnumber" :modelValue="displayDepositar?.valor" v-model="displayDepositar.valor" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
        </div>
        <template #footer>
            <Button label="Depositar" @click="depositar" icon="pi pi-check" class="p-button-outlined" />
        </template>
    </Dialog>

    <Dialog header="Sacar" v-model:visible="displaySacar.enabled" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw' }" :modal="true">
        <div class="field col-12 md:col-12">
            <label for="zip">Valor</label>
            <InputNumber id="inputnumber" :modelValue="displaySacar?.valor" v-model="displaySacar.valor" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></InputNumber>
        </div>
        <div class="field col-12 md:col-12">
            <label for="zip">{{ displaySacar?.mensagemRecebedor }}</label>
        </div>
        <template #footer>
            <Button  v-if="displaySacar?.mensagemRecebedor == null" label="Sacar" @click="sacar($event)" icon="pi pi-check" class="p-button-outlined" />
            <Button  v-if="displaySacar?.mensagemRecebedor != null" label="Efetuar Saque" @click="efetivarSaque($event)" icon="pi pi-check" class="p-button-outlined" />
        </template>
    </Dialog>

    <Dialog header="Geração chave pix deposito" v-model:visible="displayChavepix.enabled" :breakpoints="{ '960px': '75vw' }" :style="{ width: '30vw', display: 'flex' }" :modal="true">
        <div class="chave-pix-container">
            <button @click="copiarChavePix" class="copiar-botao" style="marginright: 10px" title="Copiar chave Pix">
                <i class="pi pi-copy"></i>
            </button>

            <label for="zip" @click="copiarChavePix" class="chave-pix-text">
                {{ displayChavepix.valor }}
            </label>
        </div>
    </Dialog>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Fechamento de Caixa</h5>
                </div>

                <div class="col-4 px-0 py-0 text-right">
                    <Button v-if="permissionsService.hasPermissions('view_movimentacaofinanceira_create')" label="Novo Cliente" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
                </div>
            </div>
            <div class="card">
                <div class="grid">
                    <div class="grid col-12 md:col-12">
                        <div class="col-12 md:col-2">
                            <div class="flex flex-column gap-2 m-2 mt-1">
                                <label for="username">Selecione o Banco</label>
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
                        </div>

                        <div v-if="banco && permissionsService.hasPermissions('view_encerrarfechamentocaixa')" class="col-12 md:col-2">
                            <div class="flex flex-column gap-2 m-2 mt-1">
                                <Button label="Encerrar Caixa" @click="confirm($event)" class="p-button-success mr-2 mb-2 mt-4" />
                            </div>
                        </div>

                        <div v-if="banco && permissionsService.hasPermissions('view_alterarfechamentocaixa')" class="col-12 md:col-2">
                            <div class="flex flex-column gap-2 m-2 mt-1">
                                <Button label="Alterar Caixa" @click.prevent="modalFechamento()" class="p-button-primary mr-2 mb-2 mt-4" />
                            </div>
                        </div>
                        <div v-if="banco && banco.wallet == 1 && permissionsService.hasPermissions('view_sacarfechamentocaixa')" class="col-12 md:col-2">
                            <div class="flex flex-column gap-2 m-2 mt-1">
                                <Button label="Sacar" @click.prevent="modalSacar()" class="p-button-primary mr-2 mb-2 mt-4" />
                            </div>
                        </div>
                        <div v-if="banco && banco.wallet == 1 && permissionsService.hasPermissions('view_depositarfechamentocaixa')" class="col-12 md:col-2">
                            <div class="flex flex-column gap-2 m-2 mt-1">
                                <Button label="Depositar" @click.prevent="modalDepositar()" class="p-button-primary mr-2 mb-2 mt-4" />
                            </div>
                        </div>
                    </div>

                    <div v-if="banco?.wallet == true" class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Saldo Banco wallet</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                parseFloat(banco?.saldo_banco).toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                }) ?? 'R$ 0,00'
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width: 2.5rem; height: 2.5rem">
                                        <i class="pi pi-money-bill text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Saldo Banco Sistema</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                banco?.saldo.toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                }) ?? 'R$ 0,00'
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width: 2.5rem; height: 2.5rem">
                                        <i class="pi pi-money-bill text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Saldo Caixa</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                banco?.caixa_empresa?.toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                }) ?? 'R$ 0,00'
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width: 2.5rem; height: 2.5rem">
                                        <i class="pi pi-money-bill text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Saldo Caixa Pix</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                banco?.caixa_pix?.toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                }) ?? 'R$ 0,00'
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width: 2.5rem; height: 2.5rem">
                                        <i class="pi pi-money-bill text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="banco?.wallet == true" class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Diferença Entre Bancos</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                (parseFloat(banco?.saldo_banco) - banco?.saldo).toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                }) ?? 'R$ 0,00'
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-red-100 border-round" style="width: 2.5rem; height: 2.5rem">
                                        <i class="pi pi-money-bill text-red-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Saldo Banco Sistema + Saldo Caixa Pix</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                (banco?.saldo + banco?.caixa_pix).toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                }) ?? 'R$ 0,00'
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex align-items-center justify-content-center bg-green-100 border-round" style="width: 2.5rem; height: 2.5rem">
                                        <i class="pi pi-money-bill text-green-500 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <DataTable
                        dataKey="id"
                        :value="banco?.parcelas_baixa_manual"
                        :paginator="true"
                        :rows="10"
                        :loading="loading"
                        :filters="filters"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} parcela(s)"
                        responsiveLayout="scroll"
                    >
                        <Column field="id" header="ID" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">ID</span>
                                {{ slotProps.data.id }}
                            </template>
                        </Column>
                        <Column field="dt_movimentacao" header="Emprestimo ID" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Emprestimo ID</span>
                                {{ slotProps.data.emprestimo_id }}
                            </template>
                        </Column>
                        <Column field="banco" header="Parcela" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Parcela</span>
                                {{ slotProps.data.parcela }}
                            </template>
                        </Column>
                        <Column field="descricao" header="Valor Parcela" :sortable="true" class="w-4">
                            <template #body="slotProps">
                                <span class="p-column-title">Transação realizada</span>
                                {{ slotProps.data.saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                            </template>
                        </Column>
                        <Column field="descricao" header="Valor Recebido" :sortable="true" class="w-4">
                            <template #body="slotProps">
                                <span class="p-column-title">Transação realizada</span>
                                {{ slotProps.data.valor_recebido.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Estilização do container */
.chave-pix-container {
    display: flex;
    align-items: center;
    gap: 8px; /* Espaçamento entre o texto e o ícone */
    overflow: hidden; /* Garante que o texto não ultrapasse os limites */
}

/* Estilização do texto com truncamento */
.chave-pix-text {
    cursor: pointer;
    color: #000;
    font-size: 16px;
    white-space: nowrap; /* Garante que o texto fique em uma linha */
    overflow: hidden; /* Esconde o excesso de texto */
    text-overflow: ellipsis; /* Adiciona reticências (...) */
}

/* Estilização do botão de cópia */
.copiar-botao {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #007bff; /* Azul para ícone */
    padding: 0;
}

.copiar-botao:hover {
    color: #0056b3; /* Azul mais escuro ao passar o mouse */
}
</style>
