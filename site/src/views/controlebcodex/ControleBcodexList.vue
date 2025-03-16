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

            itens_pagos_real: ref([]),
            itens_pagos: ref([]),
            itens_nao_pagos_real: ref([]),
            itens_nao_pagos: ref([]),
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
        getMovimentacaofinanceira(dt_inicio, dt_final) {
            this.loading = true;

            this.movimentacaofinanceiraService
                .getAllControleBcodex(dt_inicio, dt_final)
                .then((response) => {
                    console.log('res', response);
                    this.itens_pagos_real = response.data.itens_pagos;
                    this.itens_pagos = response.data.itens_pagos;

                    this.itens_nao_pagos_real = response.data.itens_nao_pagos;
                    this.itens_nao_pagos = response.data.itens_nao_pagos;

                    // this.Movimentacaofinanceira = response.data.data;
                    // this.MovimentacaofinanceiraReal = response.data.data;

                    // this.valorRecebido = 0;

                    // response.data.data.forEach((item) => {
                    //     if (item.tipomov === 'E') {
                    //         this.valorRecebido += item.valor;
                    //     }
                    // });

                    // this.valorPago = 0;

                    // response.data.data.forEach((item) => {
                    //     if (item.tipomov === 'S') {
                    //         this.valorPago += item.valor;
                    //     }
                    // });

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

            const dt_inicio = new Date(this.form.dt_inicio);
            const dt_final = new Date(this.form.dt_final);
            dt_inicio.setHours(0, 0, 0, 0); // Ensure the start date covers the entire day
            dt_final.setHours(23, 59, 59, 999); // Ensure the end date covers the entire day

            this.getMovimentacaofinanceira(dt_inicio.toISOString(), dt_final.toISOString());
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
        this.setLastWeekDates();

    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Controle Bcodex</h5>
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
                                        <span class="block text-500 font-medium mb-3">Cobranças não liquidadas</span>
                                        <div class="text-600 font-medium text-xl mb-3">
                                            {{ itens_nao_pagos.total_registros }}
                                        </div>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                parseFloat(itens_nao_pagos.total_registros_valor).toLocaleString('pt-BR', {
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
                                        <span class="block text-500 font-medium mb-3">Cobranças liquidadas</span>

                                        <div class="text-600 font-medium text-xl mb-3">
                                            {{ itens_pagos.total_registros }}
                                        </div>
                                        <div class="text-900 font-medium text-xl">
                                            {{
                                                parseFloat(itens_pagos.total_registros_valor).toLocaleString('pt-BR', {
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
</style>
