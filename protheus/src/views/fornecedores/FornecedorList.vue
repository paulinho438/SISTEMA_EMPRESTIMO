<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import FornecedorService from '@/service/FornecedorService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			fornecedorService: new FornecedorService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			fornecedores: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		getFornecedores() {
			this.loading = true;

			this.fornecedorService.getAll()
			.then((response) => {
				this.fornecedores = response.data.data;
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
			if (undefined === id) this.router.push('/fornecedores/add');
			else this.router.push(`/fornecedores/${id}/edit`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.fornecedorService.delete(permissionId)
			.then((e) => {
				console.log(e)
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
				this.getFornecedores();
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
		this.permissionsService.hasPermissionsView('view_fornecedores');
		this.getFornecedores();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Fornecedores</h5>
				</div>
				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_fornecedores_create')" label="Novo Fornecedor" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
				</div>
			</div>
			<div class="card">
				<div class="mt-3">
					<DataTable
						dataKey="id"
						:value="fornecedores"
						:paginator="true"
						:rows="10"
						:loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} Fornecedor(es)"
						responsiveLayout="scroll"
					>
					<template #header>
						<div class="flex justify-content-between">
							<Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros" class="p-button-outlined p-button-sm" @click="clearFilter()" />
							<span class="p-input-icon-left">
								<i class="pi pi-search" />
								<InputText v-model="filters.nome_completo.value" placeholder="Informe o Nome" class="p-inputtext-sm" />
							</span>
						</div>
					</template>

						<Column field="name" header="Nome Completo" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Nome Completo</span>
								{{ slotProps.data.nome_completo }}
							</template>
						</Column>
						<Column field="name" header="CPF / CNPJ" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">CPF</span>
								{{ slotProps.data.cpfcnpj }}
							</template>
						</Column>
						
						<Column field="name" header="Telefone Principal" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Telefone Principal</span>
								{{ slotProps.data.telefone_celular_1 }}
							</template>
						</Column>
						<Column field="name" header="Telefone Secundário" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Telefone Secundário</span>
								{{ slotProps.data.telefone_celular_2 }}
							</template>
						</Column>

						<Column field="created_at" header="Dt. Criação" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Dt. Criação</span>
								{{ slotProps.data.created_at }}
							</template>
						</Column>

						<Column v-if="permissionsService.hasPermissions('view_fornecedores_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCategory(slotProps.data.id)" />
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_fornecedores_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'" @click.prevent="deleteCategory(slotProps.data.id)" />
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>
