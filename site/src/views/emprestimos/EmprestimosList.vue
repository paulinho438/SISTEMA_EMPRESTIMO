<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import EmprestimoService from '@/service/EmprestimoService';
import PermissionsService from '@/service/PermissionsService';
import UtilService from '@/service/UtilService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'EmprestimoList',
    setup() {
        return {
            emprestimoService: new EmprestimoService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast(),
        };
    },
    data() {
        return {
            Emprestimos: ref([]),
            loading: ref(false),
            totalPages: ref(0),
            currentPage: ref(1),
            perPage: ref(10), // Número de itens por página
            filters: ref({
                global: { value: null, matchMode: FilterMatchMode.CONTAINS },
                nome_cliente: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                nome_consultor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                valor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                dt_lancamento: { operator: 'and', constraints: [{ value: null, matchMode: 'dateIs' }] },
            }),
        };
    },
    methods: {
        getEmprestimos(page = 1) {
            this.loading = true;
            this.currentPage = page;

            this.emprestimoService
                .getAll({ page: this.currentPage, per_page: this.perPage }) // Passa paginação na requisição
                .then((response) => {
                    this.Emprestimos = response.data.data;
                    this.totalPages = response.data.meta.last_page; // Obtém total de páginas

                    // Formata os dados recebidos para melhor uso
                    this.Emprestimos = this.Emprestimos.map((emprestimo) => {
                        emprestimo.nome_cliente = emprestimo.cliente?.nome_completo || 'N/A';
                        emprestimo.nome_consultor = emprestimo.consultor?.nome_completo || 'N/A';

                        return emprestimo;
                    });
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error.response.data),
                        life: 3000,
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        changePage(event) {
            this.getEmprestimos(event.page + 1);
        },
    },
    mounted() {
        this.getEmprestimos();
    },
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2">
                        <i class="pi pi-building"></i> Lista de Empréstimos
                    </h5>
                </div>
                <div class="col-4 px-0 py-0 text-right">
                    <Button
                        v-if="permissionsService.hasPermissions('view_emprestimos_create')"
                        label="Novo Empréstimo"
                        class="p-button-outlined p-button-secondary p-button-sm"
                        icon="pi pi-plus"
                        @click.prevent="editCategory()"
                    />
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <DataTable
                        :value="Emprestimos"
                        :paginator="true"
                        class="p-datatable-gridlines"
                        :rows="perPage"
                        :totalRecords="totalPages * perPage"
                        :lazy="true"
                        dataKey="id"
                        :rowHover="true"
                        v-model:filters="filters"
                        filterDisplay="menu"
                        :loading="loading"
                        responsiveLayout="scroll"
                        :globalFilterFields="['status', 'nome_cliente', 'nome_consultor', 'valor', 'saldoareceber']"
                        @page="changePage"
                    >
                        <template #header>
                            <div class="flex justify-content-between flex-column sm:flex-row">
                                <Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros" class="p-button-outlined mb-2" @click="clearFilter()" />
                                <span class="p-input-icon-left mb-2">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Pesquisar ..." style="width: 100%" />
                                </span>
                            </div>
                        </template>
                        <template #empty> Nenhum Empréstimo Encontrado. </template>
                        <template #loading> Carregando os Empréstimos. Aguarde! </template>

                        <Column field="status" header="Status" style="min-width: 9rem">
                            <template #body="slotProps">
                                <Button :label="slotProps.data.status" class="p-button-rounded p-button-secondary" />
                            </template>
                        </Column>

                        <Column field="id" header="ID" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.id }}
                            </template>
                        </Column>

                        <Column field="nome_cliente" header="Cliente" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_cliente }}
                            </template>
                        </Column>

                        <Column field="nome_consultor" header="Consultor" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_consultor }}
                            </template>
                        </Column>

                        <Column header="Valor" field="valor" dataType="numeric" style="min-width: 8rem">
                            <template #body="{ data }">
                                {{ data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                            </template>
                        </Column>

                        <Column field="saldoareceber" header="Saldo a Receber" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.saldoareceber.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                            </template>
                        </Column>

                        <Column v-if="permissionsService.hasPermissions('view_emprestimos_delete')" field="edit" header="Opções">
                            <template #body="slotProps">
                                <Button label="Editar" icon="pi pi-pencil" class="p-button-sm p-button-text" @click="editCategory(slotProps.data.id)" />
                            </template>
                        </Column>
                    </DataTable>

                    <Paginator :rows="perPage" :totalRecords="totalPages * perPage" @page="changePage"></Paginator>
                </div>
            </div>
        </div>
    </div>
</template>
