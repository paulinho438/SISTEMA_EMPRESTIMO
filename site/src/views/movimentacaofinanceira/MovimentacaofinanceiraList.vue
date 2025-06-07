<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import MovimentacaofinanceiraService from '@/service/MovimentacaofinanceiraService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'CicomList',
    setup() {
        return {
            movimentacaofinanceiraService: new MovimentacaofinanceiraService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            MovimentacaofinanceiraReal: ref([]),
            Movimentacaofinanceira: ref([]),
            loading: ref(false),
            form: ref({}),
            valorRecebido: ref(0),
            valorPago: ref(0),
            filters: ref({
                global: { value: null, matchMode: FilterMatchMode.CONTAINS },

                id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                valor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                descricao: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                'banco.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] }
            })
        };
    },
    methods: {
        initFilters() {
            this.filters = {
                global: { value: null, matchMode: FilterMatchMode.CONTAINS },

                id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                valor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                descricao: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                tipomov: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                'banco.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] }

                // cpf: {
                //     operator: FilterOperator.AND,
                //     constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }]
                // },
                // rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // telefone_celular_1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // telefone_celular_2: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // saldo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                // created_at: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // },
                // data_nascimento: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // }
            };
        },
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********';
        },
        getMovimentacaofinanceira() {
            this.loading = true;

            const hoje = new Date().toISOString().split('T')[0]; // Formata a data no formato AAAA-MM-DD


            this.movimentacaofinanceiraService
                .getAll(hoje, hoje)
                .then((response) => {
                    this.Movimentacaofinanceira = response.data.data;
                    this.MovimentacaofinanceiraReal = response.data.data;

                    this.valorRecebido = 0;

                    response.data.data.forEach((item) => {
                        if (item.tipomov === 'E') {
                            if (!(item.descricao.includes('desconto') || item.descricao.includes('Refinanciamento') || item.descricao.includes('manual'))) {
                                this.valorRecebido += item.valor;
                            }
                        }
                    });

                    this.valorPago = 0;

                    response.data.data.forEach((item) => {
                        if (item.tipomov === 'S') {
                            if (!(item.descricao.includes('desconto') || item.descricao.includes('Refinanciamento') || item.descricao.includes('manual'))) {
                                this.valorPago += item.valor;
                            }
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

            const dt_inicio = new Date(this.form.dt_inicio).toISOString().split('T')[0]; // Formatar para AAAA-MM-DD
            const dt_final = new Date(this.form.dt_final).toISOString().split('T')[0]; // Formatar para AAAA-MM-DD

            this.loading = true;

            this.movimentacaofinanceiraService
                .getAll(dt_inicio, dt_final) // Nova função de serviço para buscar com base nas datas
                .then((response) => {
                    this.Movimentacaofinanceira = response.data.data;
                    this.MovimentacaofinanceiraReal = response.data.data;

                    this.valorRecebido = 0;

                    response.data.data.forEach((item) => {
                        if (item.tipomov === 'E') {
                            if (!(item.descricao.includes('desconto') || item.descricao.includes('Refinanciamento') || item.descricao.includes('manual'))) {
                                this.valorRecebido += item.valor;
                            }
                        }
                    });

                    this.valorPago = 0;

                    response.data.data.forEach((item) => {
                        if (item.tipomov === 'S') {
                            if (!(item.descricao.includes('desconto') || item.descricao.includes('Refinanciamento') || item.descricao.includes('manual'))) {
                                this.valorPago += item.valor;
                            }
                        }
                    });
                })
                .catch((error) => {
                    console.error('Erro ao buscar movimentações:', error);
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: 'Erro ao buscar movimentações. Por favor, tente novamente.',
                        life: 3000
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        calcularValores() {
            this.valorRecebido = 0;
            this.valorPago = 0;

            this.Movimentacaofinanceira.forEach((item) => {
                if (item.tipomov === 'E') {
                    if (!(item.descricao.includes('desconto') || item.descricao.includes('Refinanciamento') || item.descricao.includes('manual'))) {
                        this.valorRecebido += item.valor;
                    }
                } else if (item.tipomov === 'S') {
                    if (!(item.descricao.includes('desconto') || item.descricao.includes('Refinanciamento') || item.descricao.includes('manual'))) {
                        this.valorPago += item.valor;
                    }
                }
            });
        },
        editCategory(id) {
            if (undefined === id) this.router.push('/Movimentacaofinanceira/add');
            else this.router.push(`/Movimentacaofinanceira/${id}/edit`);
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
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Movimentacao Financeira</h5>
                </div>

                <div class="col-4 px-0 py-0 text-right">
                    <Button v-if="permissionsService.hasPermissions('view_movimentacaofinanceira_create')" label="Novo Cliente" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
                </div>
            </div>
            <div class="card">
                <div class="flex justify-content-between"></div>

                <div class="grid">
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="username">Data Inicio</label>
                            <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data de Inicio'" v-model="form.dt_inicio" showIcon :showOnFocus="false" class="" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <label for="username">Data Final</label>
                            <Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data Final'" v-model="form.dt_final" showIcon :showOnFocus="false" class="" />
                        </div>
                    </div>
                    <div class="col-12 md:col-2">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <Button label="Pesquisar" @click.prevent="busca()" class="p-button-primary mr-2 mb-2 mt-4" />
                        </div>
                    </div>

                    <div class="col-12 md:col-3">
                        <div class="flex flex-column gap-2 m-2 mt-1">
                            <div class="surface-card shadow-2 p-3 border-round">
                                <div class="flex justify-content-between mb-3">
                                    <div>
                                        <span class="block text-500 font-medium mb-3">Total Pago</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                valorPago.toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                })
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
                                        <span class="block text-500 font-medium mb-3">Total Recebido</span>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                valorRecebido.toLocaleString('pt-BR', {
                                                    style: 'currency',
                                                    currency: 'BRL'
                                                })
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
                        :value="Movimentacaofinanceira"
                        :paginator="true"
                        class="p-datatable-gridlines"
                        :rows="10"
                        dataKey="id"
                        :rowHover="true"
                        v-model:filters="filters"
                        filterDisplay="menu"
                        :loading="loading"
                        :filters="filters"
                        responsiveLayout="scroll"
                        :globalFilterFields="['status']"
                    >
                        <!-- <template #header>
                            <div class="flex justify-content-between flex-column sm:flex-row">
                                <Button type="button" icon="pi pi-filter-slash" label="Clear" class="p-button-outlined mb-2" @click="clearFilter()" />
                                <span class="p-input-icon-left mb-2">
                                    <i class="pi pi-search" />
                                    <InputText v-if="filters && filters.global" v-model="filters.global.value" placeholder="Pesquisar ..." style="width: 100%" />
                                </span>
                            </div>
                        </template> -->
                        <template #empty> Nenhum Cliente Encontrado. </template>
                        <template #loading> Carregando os Clientes. Aguarde! </template>

                        <Column field="id" header="ID" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.id }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar ID" />
                            </template>
                        </Column>

                        <Column field="dt_movimentacao" header="Data lançamento" :sortable="true" class="w-2">
                            <template #body="slotProps">
                                <span class="p-column-title">Data lançamento</span>
                                {{ slotProps.data.dt_movimentacao }}
                            </template>
                        </Column>

                        <Column field="banco.name" header="Banco" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.banco.name }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Nome Banco" />
                            </template>
                        </Column>

                        <Column field="descricao" header="Transação realizada" style="min-width: 12rem">
                            <template #body="{ data }">
                                <span
                                    :class="{
                                        'text-red': data.descricao.includes('Baixa com desconto no'),
                                        'text-green-500': data.descricao.includes('Refinanciamento'),
                                        'text-roxo': data.descricao.includes('protestou'),
                                        'text-blue-500': data.descricao.includes('baixa manual')
                                    }"
                                >
                                    {{ data.descricao }}
                                </span>
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Descrição" />
                            </template>
                        </Column>

                        <Column field="valor" header="Valor R$" style="min-width: 12rem">
                            <template #body="{ data }">
                                <span class="p-column-title">Valor R$</span>
                                <a v-if="data.tipomov == 'S'" class="text-red-500"> - {{ data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</a>
                                <a v-if="data.tipomov == 'E'" class="text-green-500"> + {{ data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</a>
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Valor" />
                            </template>
                        </Column>

                        <Column field="tipomov" header="Tipo Mov." style="min-width: 12rem">
                            <template #body="{ data }">
                                <span class="p-column-title">Valor R$</span>
                                <a v-if="data.tipomov == 'S'" class="text-red-500"> {{ data.tipomov }}</a>
                                <a v-if="data.tipomov == 'E'" class="text-green-500"> {{ data.tipomov }}</a>
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Pelo tipo" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.text-red {
    color: red;
}

.text-red {
    color: red;
}

.text-roxo {
    color: rgb(123, 17, 210);
}
</style>
