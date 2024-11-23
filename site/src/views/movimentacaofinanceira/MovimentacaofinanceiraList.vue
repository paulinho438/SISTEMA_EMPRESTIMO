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
			MovimentacaofinanceiraReal: ref([]),
			Movimentacaofinanceira: ref([]),
			loading: ref(false),
			filters: ref(null),
			form: ref({}),
			valorRecebido: ref(0),
			valorPago: ref(0),
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
					this.MovimentacaofinanceiraReal = response.data.data;


					this.valorRecebido = 0;

					response.data.data.forEach(item => {
						if (item.tipomov === 'E') {
							this.valorRecebido += item.valor;
						}
					});

					this.valorPago = 0;

					response.data.data.forEach(item => {
						if (item.tipomov === 'S') {
							this.valorPago += item.valor;
						}
					});

					this.setLastWeekDates();

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
		setLastWeekDates() {
			const today = new Date();
			const lastWeekEnd = new Date(today);
			lastWeekEnd.setDate(today.getDate());
			const lastWeekStart = new Date(lastWeekEnd);

			this.form.dt_inicio = lastWeekStart;
			this.form.dt_final = lastWeekEnd;

			this.busca(); // Call the search method to filter data
		},
		busca() {
			if (!this.form.dt_inicio || !this.form.dt_final) {
				this.toast.add({
					severity: ToastSeverity.WARN,
					detail: 'Selecione as datas de início e fim',
					life: 3000
				});
				return;
			}

			const dt_inicio = new Date(this.form.dt_inicio);
			const dt_final = new Date(this.form.dt_final);
			dt_inicio.setHours(0, 0, 0, 0); // Ensure the end date covers the entire day
			dt_final.setHours(23, 59, 59, 999); // Ensure the end date covers the entire day

			this.Movimentacaofinanceira = this.MovimentacaofinanceiraReal.filter(mov => {
				const [day, month, year] = mov.dt_movimentacao.split('/').map(Number);
				const dt_mov = new Date(year, month - 1, day); // JavaScript Date uses zero-based month index
				return dt_mov >= dt_inicio && dt_mov <= dt_final;
			});

			this.valorRecebido = 0;
			this.valorPago = 0;

			this.Movimentacaofinanceira.forEach(item => {
				if (item.tipomov === 'E') {
					this.valorRecebido += item.valor;
				} else if (item.tipomov === 'S') {
					this.valorPago += item.valor;
				}
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
					<Button v-if="permissionsService.hasPermissions('view_movimentacaofinanceira_create')"
						label="Novo Cliente" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus"
						@click.prevent="editCategory()" />
				</div>
			</div>
			<div class="card">
				<div class="flex justify-content-between">


				</div>
				
				<div class="grid">
					<div class="col-12 md:col-2">
						<div class="flex flex-column gap-2 m-2 mt-1">
							<label for="username">Data Inicio</label>
							<Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data de Inicio'"
								v-model="form.dt_inicio" showIcon :showOnFocus="false" class="" />
						</div>
					</div>
					<div class="col-12 md:col-2">
						<div class="flex flex-column gap-2 m-2 mt-1">
							<label for="username">Data Final</label>
							<Calendar dateFormat="dd/mm/yy" v-tooltip.left="'Selecione a data Final'"
								v-model="form.dt_final" showIcon :showOnFocus="false" class="" />
						</div>
					</div>
					<div class="col-12 md:col-2">
						<div class="flex flex-column gap-2 m-2 mt-1">
							<Button label="Pesquisar" @click.prevent="busca()"
									class="p-button-primary mr-2 mb-2 mt-4" />
						</div>
					</div>
					
					<div class="col-12 md:col-3">
						<div class="flex flex-column gap-2 m-2 mt-1">
							<div class="surface-card shadow-2 p-3 border-round">
								<div class="flex justify-content-between mb-3">
									<div>
										<span class="block text-500 font-medium mb-3">Total Pago</span>
										<div class="text-900 font-medium text-xl">{{ valorPago.toLocaleString('pt-BR', {
											style: 'currency', currency: 'BRL'
										}) }}</div>
									</div>
									<div class="flex align-items-center justify-content-center bg-red-100 border-round"
										style="width:2.5rem;height:2.5rem">
										<i class="pi pi-money-bill text-red-500 text-xl"></i>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-12 md:col-3">
						<div class="flex flex-column gap-2 m-2 mt-1">
							<div class="surface-card shadow-2 p-3 border-round">
								<div class="flex justify-content-between mb-3">
									<div>
										<span class="block text-500 font-medium mb-3">Total Recebido</span>
										<div class="text-900 font-medium text-xl">{{ valorRecebido.toLocaleString('pt-BR', {
											style: 'currency', currency: 'BRL'
										}) }}</div>
									</div>
									<div class="flex align-items-center justify-content-center bg-green-100 border-round"
										style="width:2.5rem;height:2.5rem">
										<i class="pi pi-money-bill text-green-500 text-xl"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
					
				</div>
				<div class="mt-3">
					<DataTable dataKey="id" :value="Movimentacaofinanceira" :paginator="true" :rows="10" :loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} movimentações(s)"
						responsiveLayout="scroll">
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
							<template #body="slotProps">
								<span class="p-column-title">Valor R$</span>
								<a v-if="slotProps.data.tipomov == 'S'" class="text-red-500"> - {{
									slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
								}}</a>
								<a v-if="slotProps.data.tipomov == 'E'" class="text-green-500"> + {{
									slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
								}}</a>
							</template>
						</Column>

						<Column v-if="permissionsService.hasPermissions('view_Movimentacaofinanceira_edit')" field="edit"
							header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard"
									class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
									type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'"
									@click.prevent="editCategory(slotProps.data.id)" />
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_Movimentacaofinanceira_delete')" field="edit"
							header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard"
									class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
									type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL"
									v-tooltip.top="'Excluir'" @click.prevent="deleteCategory(slotProps.data.id)" />
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>
