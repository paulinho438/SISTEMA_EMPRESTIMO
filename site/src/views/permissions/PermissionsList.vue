<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import PermissionsService from '@/service/PermissionsService';
import UtilService from '@/service/UtilService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'CicomList',
    setup() {
        return {
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            permissions: ref([]),
            loading: ref(false),
            filters: ref(null)
        };
    },
    methods: {
        getPermissions() {
            this.loading = true;

            this.permissionsService
                .getAll()
                .then((response) => {
                    this.permissions = response.data.data;
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

            this.initFilters();
        },
        initFilters() {
            this.filters = {
                name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                total_users: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
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
        editCicom(id) {
            if (undefined === id) this.router.push('/permissoes/add');
            else this.router.push(`/permissoes/${id}/edit`);
        },
        deletePermission(permissionId) {
            this.loading = true;

            this.permissionsService
                .deletePermission(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getPermissions();
                })
                .catch((error) => {
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error.response.data),
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
        this.permissionsService.hasPermissionsView('view_permissions');
        this.getPermissions();
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de permissões</h5>
                </div>
                <div class="col-4 px-0 py-0 text-right">
                    <Button v-if="permissionsService.hasPermissions('view_permissions_create')" label="Nova Permissão" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCicom()" />
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <DataTable
                        :value="permissions"
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
                    >
                        <template #empty> Nenhuma Permissão Encontrada. </template>
                        <template #loading> Carregando Permissões. Aguarde! </template>
                        <Column field="name" header="Nome" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.name }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Nome" />
                            </template>
                        </Column>

						<Column header="Qt. de Ativos" filterField="total_users" dataType="numeric" style="min-width: 10rem">
							<template #body="{ data }">
								{{ data.total_users }}
							</template>
							<template #filter="{ filterModel }">
								<InputNumber v-model="filterModel.value"/>
							</template>
						</Column>

                        <Column v-if="permissionsService.hasPermissions('view_permissions_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCicom(slotProps.data.id)" />
                            </template>
                        </Column>
                        <Column v-if="permissionsService.hasPermissions('view_permissions_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :disabled="slotProps.data.total_users > 0"
                                    :icon="icons.FILE_EXCEL"
                                    v-tooltip.top="'Excluir'"
                                    @click.prevent="deletePermission(slotProps.data.id)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        
        </div>
    </div>
</template>
