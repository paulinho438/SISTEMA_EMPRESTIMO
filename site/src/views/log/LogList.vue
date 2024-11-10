<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import LogService from '@/service/LogService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'LogList',
    setup() {
        return {
            logService: new LogService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            LogReal: ref([]),
            Log: ref([]),
            loading: ref(false),
            filters: ref(null),
            form: ref({}),
            valorRecebido: ref(0),
            valorPago: ref(0)
        };
    },
    methods: {
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********';
        },
        getLog() {
            this.loading = true;

            this.logService
                .getAll()
                .then((response) => {
                    this.Log = response.data;
                    this.LogReal = response.data;
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
            lastWeekStart.setDate(lastWeekEnd.getDate());

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


            dt_inicio.setHours(0, 0, 0, 999); // Ensure the end date covers the entire day
            dt_final.setHours(23, 59, 59, 999); // Ensure the end date covers the entire day

            this.Log = this.LogReal.filter((mov) => {
                const dt_mov = new Date(mov.created_at); // Converte a string de data para um objeto Date
                return dt_mov >= dt_inicio && dt_mov <= dt_final;
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
                    this.getLog();
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
        this.getLog();
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Log</h5>
                </div>
            </div>
            <div class="card">
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
                </div>
                <div class="mt-3">
                    <DataTable
                        dataKey="id"
                        :value="Log"
                        :paginator="true"
                        :rows="10"
                        :loading="loading"
                        :filters="filters"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} movimentações(s)"
                        responsiveLayout="scroll"
                    >
                        <!-- <template #header>
						<div class="flex justify-content-between">
							<Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros" class="p-button-outlined p-button-sm" @click="clearFilter()" />
							<span class="p-input-icon-left">
								<i class="pi pi-search" />
								<InputText v-model="filters.nome_completo.value" placeholder="Informe o Nome" class="p-inputtext-sm" />
							</span>
						</div>
					</template> -->

                        <Column field="id" header="ID" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">ID</span>
                                {{ slotProps.data.id }}
                            </template>
                        </Column>
                        <Column field="user_id" header="Usuário" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">User Id</span>
								{{ slotProps.data.user_id }}
							</template>
						</Column>
						<Column field="operation" header="Operação" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Operação</span>
								{{ slotProps.data.operation }}
							</template>
						</Column>
						<Column field="content" header="Transação realizada" :sortable="true" class="w-4">
							<template #body="slotProps">
								<span class="p-column-title">Descrição</span>
								{{ slotProps.data.content }}
							</template>
						</Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>
