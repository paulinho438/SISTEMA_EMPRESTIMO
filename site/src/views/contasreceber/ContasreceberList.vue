<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import ContasreceberService from '@/service/ContasreceberService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			contasreceberService: new ContasreceberService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			Contasreceber: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		dadosSensiveis(dado) {
			return (this.permissionsService.hasPermissions('view_Contasreceber_sensitive') ? dado : '*********')
		},
		getContasreceber() {
			this.loading = true;

			this.contasreceberService.getAll()
			.then((response) => {
				this.Contasreceber = response.data.data;
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
			if (undefined === id) this.router.push('/contasreceber/add');
			else this.router.push(`/contasreceber/${id}/edit`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.contasreceberService.delete(permissionId)
			.then((e) => {
				console.log(e)
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
				this.getContasreceber();
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
		this.permissionsService.hasPermissionsView('view_contasreceber');
		this.getContasreceber();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Contas Receber</h5>
				</div>
				<!-- <div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_contasreceber_create')" label="Novo Título" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
				</div> -->
			</div>
			<div class="card">
				<div class="mt-3">
					<DataTable
						dataKey="id"
						:value="Contasreceber"
						:paginator="true"
						:rows="10"
						:loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} título(s)"
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
						<Column field="cliente" header="Cliente" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Cliente</span>
								{{ slotProps.data.cliente.nome_completo }}
							</template>
						</Column>
						<Column field="descricao" header="Descrição" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Descrição</span>
								{{ slotProps.data.descricao }}
							</template>
						</Column>
						<Column field="venc" header="Dt. Venc." :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Dt. Venc.</span>
								{{ slotProps.data.venc }}
							</template>
						</Column>
						<Column field="dt_baixa" header="Dt. Pag." :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Dt. Pag.</span>
								{{ slotProps.data.dt_baixa }}
							</template>
						</Column>
						<Column field="forma_recebto" header="Forma recebto" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Forma recebto</span>
								{{ slotProps.data.forma_recebto }}
							</template>
						</Column>
						<Column field="valor" header="Valor R$" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Valor R$</span>
								{{ slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
							</template>
						</Column>

						<Column field="banco" header="Conta util" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Conta util</span>
								{{ slotProps.data.banco.name }}
							</template>
						</Column>

						<Column field="status" header="Status" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Status</span>
								{{ slotProps.data.status }}
							</template>
						</Column>

						<Column field="tipodoc" header="Tipo" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Tipo</span>
								{{ slotProps.data.tipodoc }}
							</template>
						</Column>

						<!-- <Column v-if="permissionsService.hasPermissions('view_contasreceber_baixa')" field="edit" header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCategory(slotProps.data.id)" />
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_contasreceber_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'" @click.prevent="deleteCategory(slotProps.data.id)" />
							</template>
						</Column> -->
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>
