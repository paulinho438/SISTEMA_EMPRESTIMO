<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import MovimentacaofinanceiraService from '@/service/MovimentacaofinanceiraService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			movimentacaofinanceiraService: new MovimentacaofinanceiraService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			Movimentacaofinanceira: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		dadosSensiveis(dado) {
			return (this.permissionsService.hasPermissions('view_Movimentacaofinanceira_sensitive') ? dado : '*********')
		},
		getMovimentacaofinanceira() {
			this.loading = true;

			this.movimentacaofinanceiraService.getAll()
			.then((response) => {
				this.Movimentacaofinanceira = response.data.data;
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
			if (undefined === id) this.router.push('/Movimentacaofinanceira/add');
			else this.router.push(`/Movimentacaofinanceira/${id}/edit`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.movimentacaofinanceiraService.delete(permissionId)
			.then((e) => {
				console.log(e)
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
				this.getMovimentacaofinanceira();
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
		this.permissionsService.hasPermissionsView('view_movimentacaofinanceira');
		this.getMovimentacaofinanceira();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Movimentacao Financeira</h5>
				</div>
				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_movimentacaofinanceira_create')" label="Novo Cliente" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
				</div>
			</div>
			<div class="card">
				<div class="mt-3">
					<DataTable
						dataKey="id"
						:value="Movimentacaofinanceira"
						:paginator="true"
						:rows="10"
						:loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} cliente(s)"
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
						<Column field="dt_movimentacao" header="Data lançamento" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Data lançamento</span>
								{{ slotProps.data.dt_movimentacao }}
							</template>
						</Column>
						<Column field="banco" header="Conta bancária" :sortable="true" class="w-2">
							<template #body="slotProps">
								<span class="p-column-title">Conta bancária</span>
								{{ slotProps.data.banco.name }}
							</template>
						</Column>
						<Column field="descricao" header="Transação realizada" :sortable="true" class="w-4">
							<template #body="slotProps">
								<span class="p-column-title">Transação realizada</span>
								{{ slotProps.data.descricao }}
							</template>
						</Column>
						<Column field="valor" header="Valor R$" :sortable="true" class="w-1">
							<template #body="slotProps" >
								<span class="p-column-title">Valor R$</span>
								<a v-if="slotProps.data.tipomov == 'S'" class="text-red-500"> - {{ slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</a>
								<a v-if="slotProps.data.tipomov == 'E'" class="text-green-500"> + {{ slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</a>
							</template>
						</Column>

						<Column v-if="permissionsService.hasPermissions('view_Movimentacaofinanceira_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCategory(slotProps.data.id)" />
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_Movimentacaofinanceira_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
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
