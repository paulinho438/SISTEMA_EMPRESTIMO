<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import ContaspagarService from '@/service/ContaspagarService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			contaspagarService: new ContaspagarService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			Contaspagar: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		dadosSensiveis(dado) {
			return (this.permissionsService.hasPermissions('view_Contaspagar_sensitive') ? dado : '*********')
		},
		getContaspagar() {
			this.loading = true;

			this.contaspagarService.getAll()
			.then((response) => {
				this.Contaspagar = response.data.data;
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
			if (undefined === id) this.router.push('/contaspagar/add');
			else this.router.push(`/contaspagar/${id}/edit`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.contaspagarService.delete(permissionId)
			.then((e) => {
				console.log(e)
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
				this.getContaspagar();
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
		this.permissionsService.hasPermissionsView('view_contaspagar');
		this.getContaspagar();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Contas a Pagar</h5>
				</div>
				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_contaspagar_create')" label="Novo Título" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
				</div>
			</div>
			<div class="card">
				<div class="mt-3">
					<DataTable
						dataKey="id"
						:value="Contaspagar"
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

						<Column field="name" header="ID" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">ID</span>
								{{ slotProps.data.id }}
							</template>
						</Column>
						<Column field="name" header="Fornecedor" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Fornecedor</span>
								{{ slotProps.data.fornecedor?.completo ?? 'Empréstimo' }}
							</template>
						</Column>
						<Column field="name" header="tipodoc" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Tipo docto</span>
								{{ slotProps.data.tipodoc }}
							</template>
						</Column>
						<Column field="name" header="descricao" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Descrição</span>
								{{ slotProps.data.descricao }}
							</template>
						</Column>
						<Column field="name" header="Cto custo" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Cto custo</span>
								{{ slotProps.data.costcenter.name }}
							</template>
						</Column>
						<Column field="name" header="Venc." :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Venc.</span>
								{{ slotProps.data.venc }}
							</template>
						</Column>

						<Column field="created_at" header="Pagto" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Pagto</span>
								{{ slotProps.data.dt_baixa }}
							</template>
						</Column>

						<Column field="created_at" header="Qnt parc" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Qnt parc</span>
								{{ slotProps.data.emprestimo?.parcelas?.length.toString().padStart(3, '0') }}
							</template>
						</Column>

						<Column field="created_at" header="Valor R$" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Valor R$</span>
								{{ slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
							</template>
						</Column>

						<Column field="banco" header="Conta util" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Conta util</span>
								{{ slotProps.data.banco?.name }}
							</template>
						</Column>

						<Column field="Status" header="Status" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Status</span>
								{{ slotProps.data.status }}
							</template>
						</Column>

						<!-- <Column v-if="permissionsService.hasPermissions('view_contaspagar_baixa')" field="edit" header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCategory(slotProps.data.id)" />
							</template>
						</Column> -->
						<Column v-if="permissionsService.hasPermissions('view_contaspagar_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard && slotProps.data.tipodoc != 'Empréstimo'" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'" @click.prevent="deleteCategory(slotProps.data.id)" />
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>
