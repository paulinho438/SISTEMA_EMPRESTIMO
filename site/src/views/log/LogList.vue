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
            valorPago: ref(0),
            totalPages: ref(0),
            totalRecords: ref(0),
            currentPage: ref(1),
            perPage: ref(10)
        };
    },
    methods: {
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********';
        },
        formatDateTime(dateTime) {
            if (!dateTime) return '-';
            
            // Se já for uma string formatada, retornar como está
            if (typeof dateTime === 'string' && dateTime.includes('/')) {
                return dateTime;
            }
            
            // Se for um objeto Date ou string ISO
            const date = new Date(dateTime);
            if (isNaN(date.getTime())) return '-';
            
            // Formatar como dd/mm/yyyy HH:mm:ss
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            
            return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
        },
        getLog(page = 1) {
            this.loading = true;
            this.currentPage = page;

            const params = {
                page: this.currentPage,
                per_page: this.perPage
            };

            // Adicionar filtros de data se existirem
            if (this.form.dt_inicio) {
                const dtInicio = new Date(this.form.dt_inicio);
                params.dt_inicio = dtInicio.toISOString().split('T')[0]; // YYYY-MM-DD
            }
            if (this.form.dt_final) {
                const dtFinal = new Date(this.form.dt_final);
                params.dt_final = dtFinal.toISOString().split('T')[0]; // YYYY-MM-DD
            }

            this.logService
                .getAll(params)
                .then((response) => {
                    // Atualizar informações de paginação
                    if (response.data.meta) {
                        this.totalPages = response.data.meta.last_page || 1;
                        this.totalRecords = response.data.meta.total || 0;
                        this.currentPage = response.data.meta.current_page || 1;
                    } else {
                        // Fallback: se não houver meta, usar o tamanho do array
                        this.totalRecords = response.data.data ? response.data.data.length : 0;
                        this.totalPages = this.totalRecords > 0 ? 1 : 0;
                    }

                    this.Log = (response.data.data || []).map(log => {
                        // Garantir que created_at seja processado corretamente
                        if (log.created_at) {
                            // Se for string ISO, converter para Date
                            if (typeof log.created_at === 'string' && log.created_at.includes('T')) {
                                log.created_at = new Date(log.created_at);
                            }
                        }
                        return log;
                    });
                    this.LogReal = this.Log;
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
            lastWeekStart.setDate(lastWeekEnd.getDate() - 7); // Últimos 7 dias

            this.form.dt_inicio = lastWeekStart;
            this.form.dt_final = lastWeekEnd;

            // Não buscar automaticamente - deixar o usuário clicar em Pesquisar
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

            // Buscar dados do servidor com os filtros de data
            this.getLog(1);
        },
        changePage(event) {
            const newPage = event.page + 1; // PrimeVue usa índice 0, backend usa índice 1
            this.getLog(newPage);
        },
        editCategory(id) {
            if (undefined === id) this.router.push('/Movimentacaofinanceira/add');
            else this.router.push(`/Movimentacaofinanceira/${id}/edit`);
        },
        deleteCategory(permissionId) {
            // Logs geralmente não são deletáveis, mas mantendo o método caso seja necessário
            this.toast.add({
                severity: ToastSeverity.INFO,
                detail: 'Logs não podem ser deletados',
                life: 3000
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
        this.setLastWeekDates();
        // Não carregar dados automaticamente - esperar o usuário clicar em Pesquisar
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
                        :rows="perPage"
                        :totalRecords="totalRecords"
                        :lazy="true"
                        :loading="loading"
                        :filters="filters"
                        :first="(currentPage - 1) * perPage"
                        @page="changePage"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[10, 15, 25, 50]"
                        currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} movimentações(s)"
                        @rowsPerPageChange="(event) => { perPage = event.rows; getLog(1); }"
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
                        <Column field="created_at" header="Data/Hora" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Data/Hora</span>
								{{ formatDateTime(slotProps.data.created_at) }}
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
