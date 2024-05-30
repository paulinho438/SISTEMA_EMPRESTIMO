<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import PermissionsService from '@/service/PermissionsService';
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

			this.permissionsService.getAll()
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
		},
		editCicom(id) {
			if (undefined === id) this.router.push('/permissoes/add');
			else this.router.push(`/permissoes/${id}/edit`);
		},
		deletePermission(permissionId) {
			this.loading = true;

			this.permissionsService.deletePermission(permissionId)
			.then((e) => {
				console.log(e)
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
				name: { value: null, matchMode: FilterMatchMode.CONTAINS }
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
			<div class="card">
				<div class="mt-3">
					<DataTable
						dataKey="id"
						:value="permissions"
						:paginator="true"
						:rows="10"
						:loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} permissão(ões)"
						responsiveLayout="scroll"
					>
					<template #header>
						<div class="flex justify-content-between">
							<Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros" class="p-button-outlined p-button-sm" @click="clearFilter()" />
							<span class="p-input-icon-left">
								<i class="pi pi-search" />
								<InputText v-model="filters.name.value" placeholder="Informe o Nome" class="p-inputtext-sm" />
							</span>
						</div>
					</template>

						<Column field="name" header="Nome" :sortable="true" class="w-9">
							<template #body="slotProps">
								<span class="p-column-title">Nome</span>
								{{ slotProps.data.name }}
							</template>
						</Column>
						<Column field="total_users" header="Qtd. de ativos" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Qtd. de ativos</span>
								{{ slotProps.data.total_users }}
							</template>
						</Column>

						<Column v-if="permissionsService.hasPermissions('view_permissions_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCicom(slotProps.data.id)" />
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_permissions_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'" @click.prevent="deletePermission(slotProps.data.id)" />
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>
