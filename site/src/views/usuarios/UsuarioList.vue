<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import UsuarioService from '@/service/UsuarioService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			usuarioService: new UsuarioService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			Usuarios: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		dadosSensiveis(dado) {
			return (this.permissionsService.hasPermissions('view_usuario_sensitive') ? dado : '*********')
		},
		getUsuario() {
			this.loading = true;

			this.usuarioService.getAll()
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
		},
		editCategory(id) {
			if (undefined === id) this.router.push('/usuarios/add');
			else this.router.push(`/usuarios/${id}/edit`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.usuarioService.delete(permissionId)
				.then((e) => {
					console.log(e)
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
					<Button v-if="permissionsService.hasPermissions('view_clientes_create')" label="Novo Usuario"
						class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus"
						@click.prevent="editCategory()" />
				</div>
			</div>
			<div class="card">
				<div class="mt-3">
					<DataTable dataKey="id" :value="Usuarios" :paginator="true" :rows="10" :loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} usuario(s)"
						responsiveLayout="scroll">
						<template #header>
							<div class="flex justify-content-between">
								<Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros"
									class="p-button-outlined p-button-sm" @click="clearFilter()" />
								<span class="p-input-icon-left">
									<i class="pi pi-search" />
									<InputText v-model="filters.cpf.value" placeholder="Informe o CPF"
										class="p-inputtext-sm" />
								</span>
							</div>
						</template>
						<Column field="email" header="Login" :sortable="true" class="w-1">
							<template #body="slotProps">
								{{ slotProps.data.login }}
							</template>
						</Column>
						<Column field="email" header="Nome Completo" :sortable="true" class="w-1">
							<template #body="slotProps">
								{{ slotProps.data.nome_completo }}
							</template>
						</Column>
						<Column field="email" header="Email" :sortable="true" class="w-1">
							<template #body="slotProps">
								{{ slotProps.data.email }}
							</template>
						</Column>
						<Column field="cpf" header="CPF" :sortable="true" class="w-1">
							<template #body="slotProps">
								{{ slotProps.data.cpf }}
							</template>
						</Column>
						<Column field="rg" header="RG" :sortable="true" class="w-1">
							<template #body="slotProps">
								{{ slotProps.data.rg }}
							</template>
						</Column>
						<Column field="telefone_celular" header="Telefone Principal" :sortable="true" class="w-1">
							<template #body="slotProps">
								{{ slotProps.data.telefone_celular }}
							</template>
						</Column>
						<Column field="companies" header="Empresas" :sortable="true" class="w-2">
							<template #body="slotProps">
								{{ slotProps.data.companies }}
							</template>
						</Column>


						<Column v-if="permissionsService.hasPermissions('view_clientes_edit')" field="edit" header="Editar"
							:sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard"
									class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
									type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'"
									@click.prevent="editCategory(slotProps.data.id)" />
							</template>
						</Column>
						<!-- <Column v-if="permissionsService.hasPermissions('view_clientes_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'" @click.prevent="deleteCategory(slotProps.data.id)" />
							</template>
						</Column> -->
					</DataTable>
				</div>
			</div>
		</div>
	</div></template>
