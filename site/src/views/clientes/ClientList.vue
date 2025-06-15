<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import ClientService from '@/service/ClientService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'CicomList',
    setup() {
        return {
            clientService: new ClientService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            Clientes: ref([]),
            loading: ref(false),
            filters: ref(null)
        };
    },
    methods: {
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_clientes_sensitive') ? dado : '*********';
        },
        initFilters() {
            this.filters = {
                global: { value: null, matchMode: FilterMatchMode.CONTAINS },

                nome_completo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                cpf: {
                    operator: FilterOperator.AND,
                    constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }]
                },
                rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                telefone_celular_1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                telefone_celular_2: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                saldo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                created_at: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                },
                data_nascimento: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                }
            };
        },
        getClientes() {
            this.loading = true;

            this.clientService
                .getAll()
                .then((response) => {
                    this.Clientes = response.data.data;

                    this.Clientes = response.data.data.map((Clientes) => {
                        if (Clientes.created_at) {
                            const parts = Clientes.created_at.split(' ');
                            const datePart = parts[0].split('/').reverse().join('-'); // Converte dd/mm/yyyy para yyyy-mm-dd
                            const timePart = parts[1];
                            Clientes.created_at = new Date(`${datePart}T${timePart}`); // Concatena e cria um objeto Date
                        }

                        if (Clientes.data_nascimento) {
                            const datePart = Clientes.data_nascimento.split('/').reverse().join('-');
                            Clientes.data_nascimento = new Date(`${datePart}T00:00:00`); // Concatena e cria um objeto Date
                        }

                        return Clientes;
                    });
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
        editCategory(id) {
            if (undefined === id) this.router.push('/clientes/add');
            else this.router.push(`/clientes/${id}/edit`);
        },
        deleteCategory(permissionId) {
            this.loading = true;

            this.clientService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getClientes();
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
        criarUsuario(permissionId) {
            this.loading = true;

            this.clientService
                .criarUsuarioAPP(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getClientes();
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
        this.permissionsService.hasPermissionsView('view_clientes');
        this.getClientes();
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Clientes</h5>
                </div>
                <div class="col-4 px-0 py-0 text-right">
                    <Button v-if="permissionsService.hasPermissions('view_clientes_create')" label="Novo Cliente" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <DataTable
                        :value="Clientes"
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
                        :globalFilterFields="['nome_completo', 'cpf', 'rg', 'telefone_celular_1', 'telefone_celular_2']"
                    >
                        <template #header>
                            <div class="flex justify-content-between flex-column sm:flex-row">
                                <Button type="button" icon="pi pi-filter-slash" label="Clear" class="p-button-outlined mb-2" @click="clearFilter()" />
                                <span class="p-input-icon-left mb-2">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Pesquisar ..." style="width: 100%" />
                                </span>
                            </div>
                        </template>
                        <template #empty> Nenhum Cliente Encontrado. </template>
                        <template #loading> Carregando os Clientes. Aguarde! </template>

                        <Column field="nome_completo" header="Nome Completo" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_completo }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar nome" />
                            </template>
                        </Column>

                        <Column field="cpf" header="CPF" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.cpf }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar CPF" />
                            </template>
                        </Column>

                        <Column field="rg" header="RG" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.rg }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar RG" />
                            </template>
                        </Column>

                        <Column field="telefone_celular_1" header="Telefone Principal" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.telefone_celular_1 }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Telefone Principal" />
                            </template>
                        </Column>

                        <Column field="telefone_celular_2" header="Telefone Secundário" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.telefone_celular_2 }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Telefone Secundário" />
                            </template>
                        </Column>

                        <Column header="Dt. Nascimento" filterField="data_nascimento" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.data_nascimento ? data.data_nascimento.toLocaleDateString('pt-BR') : '-' }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
                            </template>
                        </Column>

                        <Column header="Dt. Criação" filterField="created_at" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.created_at ? data.created_at.toLocaleDateString('pt-BR') : '-' }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
                            </template>
                        </Column>

                        <Column v-if="permissionsService.hasPermissions('view_clientes_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard"
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :icon="icons.FILE_EDIT"
                                    v-tooltip.top="'Editar'"
                                    @click.prevent="editCategory(slotProps.data.id)"
                                />
                            </template>
                        </Column>
                        <Column v-if="permissionsService.hasPermissions('view_clientes_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard"
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :disabled="slotProps.data.total_users > 0"
                                    :icon="icons.FILE_EXCEL"
                                    v-tooltip.top="'Excluir'"
                                    @click.prevent="deleteCategory(slotProps.data.id)"
                                />
                            </template>
                        </Column>
                        <Column field="edit" header="Gerar usuário" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard"
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :disabled="slotProps.data.total_users > 0"
                                    :icon="icons.USER"
                                    v-tooltip.top="'Criar Usuário APP'"
                                    @click.prevent="criarUsuario(slotProps.data.id)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>
