<script>
import { ref, onBeforeMount } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import UsuarioService from '@/service/UsuarioService';
import PermissionsService from '@/service/PermissionsService';
import CustomerService from '@/service/CustomerService';
import ProductService from '@/service/ProductService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'CicomList',
    setup() {
        return {
            usuarioService: new UsuarioService(),
            permissionsService: new PermissionsService(),
            customerService: new CustomerService(),
            productService: new ProductService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            Usuarios: ref([]),
            loading: ref(false),
            filters: ref(null),
            filters1: ref(null),
            customer1: ref(null),
            products: ref(null),
            loading1: ref(null),
        };
    },
    onBeforeMount() {},
    methods: {
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_usuario_sensitive') ? dado : '*********';
        },
        getUsuario() {


            this.loading = true;

            this.usuarioService
                .getAll()
                .then((response) => {
                    this.Usuarios = response.data.data;
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

				this.initFilters1();

        },
        editCategory(id) {
            if (undefined === id) this.router.push('/usuarios/add');
            else this.router.push(`/usuarios/${id}/edit`);
        },
        formatDate(value) {
            return value.toLocaleDateString('en-US', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },
        formatCurrency(value) {
            return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        },
        deleteCategory(permissionId) {
            this.loading = true;

            this.usuarioService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getUsuario();
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
        initFilters1() {
            this.filters1 = {
                global: { value: null, matchMode: FilterMatchMode.CONTAINS },
				login: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				nome_completo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				email: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				cpf: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				telefone_celular: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				companies: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // global: { value: null, matchMode: FilterMatchMode.CONTAINS },
                // name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // 'country.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // representative: { value: null, matchMode: FilterMatchMode.IN },
                // date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
                // balance: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] }, // numerico
                // status: { operator: FilterOperator.OR, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                // activity: { value: [0, 50], matchMode: FilterMatchMode.BETWEEN },
                // verified: { value: null, matchMode: FilterMatchMode.EQUALS },
            };
        },
        initFilters() {
            this.filters = {
                cpf: { value: null, matchMode: FilterMatchMode.CONTAINS }
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
        this.permissionsService.hasPermissionsView('view_clientes');
        this.getUsuario();
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Usuarios</h5>
                </div>
                <div class="col-4 px-0 py-0 text-right">
                    <Button v-if="permissionsService.hasPermissions('view_clientes_create')" label="Novo Usuario" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
                </div>
            </div>
			<div class="col-12">
                <div class="card">
                    <DataTable
                        :value="Usuarios"
                        :paginator="true"
                        class="p-datatable-gridlines"
                        :rows="10"
                        dataKey="id"
                        :rowHover="true"
                        v-model:filters="filters1"
                        filterDisplay="menu"
                        :loading="loading1"
                        :filters="filters1"
                        responsiveLayout="scroll"
                        :globalFilterFields="['login', 'nome_completo', 'email', 'cpf', 'rg', 'telefone_celular', 'companies']"
                    >
                        <template #header>
                            <div class="flex justify-content-between flex-column sm:flex-row">
                                <Button type="button" icon="pi pi-filter-slash" label="Clear" class="p-button-outlined mb-2" @click="clearFilter()" />
                                <span class="p-input-icon-left mb-2">
                                    <i class="pi pi-search" />
                                    <InputText v-if="filters1 && filters1.global" v-model="filters1.global.value" placeholder="Pesquisar ..." style="width: 100%" />
                                </span>
                            </div>
                        </template>
                        <template #empty> Nenhum Usuário Encontrado. </template>
                        <template #loading> Carregando Usuários. Aguarde! </template>
                        <Column field="login" header="Login" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.login }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar login" />
                            </template>
                        </Column>

						<Column field="nome_completo" header="Nome Completo" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_completo }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Nome" />
                            </template>
                        </Column>

						<Column field="email" header="E-mail" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.email }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar E-mail" />
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

						<Column field="telefone_celular" header="Telefone Celular" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.telefone_celular }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Celular" />
                            </template>
                        </Column>
						<Column field="companies" header="Empresa" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.companies }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Empresa" />
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
                        
                    </DataTable>
                </div>
            </div>
           
        </div>
    </div>
</template>
