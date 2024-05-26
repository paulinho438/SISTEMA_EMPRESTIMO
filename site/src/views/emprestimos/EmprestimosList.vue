<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity } from 'primevue/api';
import EmprestimoService from '@/service/EmprestimoService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			emprestimoService: new EmprestimoService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast(),
			menu: ref({})

		};
	},
	data() {
		return {
			Emprestimos: ref([]),
			loading: ref(false),
			filters: ref({
				global: { value: null, matchMode: FilterMatchMode.CONTAINS },
				name: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
				'country.name': { value: null, matchMode: FilterMatchMode.STARTS_WITH },
				representative: { value: null, matchMode: FilterMatchMode.IN },
				status: { value: null, matchMode: FilterMatchMode.EQUALS },
				verified: { value: null, matchMode: FilterMatchMode.EQUALS }
			}),
			overlayMenuItems: ref([
				{
					label: 'Save',
					icon: 'pi pi-save',
					command: () => this.handleItemClick('Item 1')
				},
				{
					label: 'Update',
					icon: 'pi pi-refresh'
				},
				{
					label: 'Delete',
					icon: 'pi pi-trash'
				},
				{
					separator: true
				},
				{
					label: 'Home',
					icon: 'pi pi-home'
				}
			]),

		};
	},
	methods: {
		getStatusClass(status) {
			// Adicione classes de botões com base no status
			switch (status) {
				case 'Em Dias':
					return 'p-button-rounded p-button-success mr-2 mb-2';
				case 'Atrasado':
					return 'p-button-rounded p-button-info mr-2 mb-2';
				case 'Muito Atrasado':
					return 'p-button-rounded p-button-warning';
				default:
					return 'p-button-rounded p-button-danger mr-2 mb-2'; // Padrão
			}
		},
		showMenu(data) {
			// Lógica para decidir se mostrar ou não o menu com base nos dados
			return true;// Substitua someCondition pela sua lógica específica
		},
		getOverlayMenuItems(data) {

			const contextMenuItems = [
				{
					label: 'Visualizar',
					icon: 'pi pi-eye',
					command: () => this.viewCategory(data.id)
				}

			];

			if (data.porcentagem === '0.0') {
				contextMenuItems.push({
					label: 'Editar',
					icon: 'pi pi-refresh',
					command: () => this.editCategory(data.id)
				});
			}

			if (data.porcentagem === '0.0') {
				contextMenuItems.push({
					label: 'Delete',
					icon: 'pi pi-trash',
					command: () => this.deleteCategory(data.id)
				});
			}

			return contextMenuItems;
		},
		toggleMenu(id) {
			const menuRef = this.$refs[`menu_${id}`];
			menuRef.toggle(event);
		},
		handleItemClick(itemLabel) {
			console.log(`Item clicado: ${itemLabel}`);
			// Execute ações específicas com base no item clicado
		},
		dadosSensiveis(dado) {
			return (this.permissionsService.hasPermissions('view_emprestimos_sensitive') ? dado : '*********')
		},
		getEmprestimos() {
			this.loading = true;

			this.emprestimoService.getAll()
				.then((response) => {
					this.Emprestimos = response.data.data;
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
			if (undefined === id) this.router.push('/emprestimos/add');
			else this.router.push(`/emprestimos/${id}/edit`);
		},
		viewCategory(id) {
			this.router.push(`/emprestimos/${id}/view`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.emprestimoService.delete(permissionId)
				.then((e) => {
					console.log(e)
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: e?.data?.message,
						life: 3000
					});
					this.getEmprestimos();
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
		this.permissionsService.hasPermissionsView('view_emprestimos');
		this.getEmprestimos();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Emprestimos</h5>
				</div>

				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_emprestimos_create')" label="Novo Emprestimo"
						class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus"
						@click.prevent="editCategory()" />
				</div>
			</div>
			<div class="card">
				<DataTable v-model:filters="filters" :value="Emprestimos" paginator :rows="10" dataKey="id"
					filterDisplay="row" :loading="loading"
					:globalFilterFields="['name', 'country.name', 'representative.name', 'status']">
					<template #header>
						<div class="flex justify-content-end">
							<IconField iconPosition="left">
								<InputIcon>
									<i class="pi pi-search" />
								</InputIcon>
								<InputText v-model="filters['global'].value" placeholder="Keyword Search" />
							</IconField>
						</div>
					</template>
					<template #empty> No customers found. </template>
					<template #loading> Loading customers data. Please wait. </template>
					<Column field="name" header="Name" style="min-width: 12rem">
						<template #body="{ data }">
							{{ data.name }}
						</template>
						<template #filter="{ filterModel, filterCallback }">
							<InputText v-model="filterModel.value" type="text" @input="filterCallback()"
								class="p-column-filter" placeholder="Search by name" />
						</template>
					</Column>
					<Column header="Country" filterField="country.name" style="min-width: 12rem">
						<template #body="{ data }">
							<div class="flex align-items-center gap-2">
								<img alt="flag" src="https://primefaces.org/cdn/primevue/images/flag/flag_placeholder.png"
									:class="`flag flag-${data.country.code}`" style="width: 24px" />
								<span>{{ data.country.name }}</span>
							</div>
						</template>
						<template #filter="{ filterModel, filterCallback }">
							<InputText v-model="filterModel.value" type="text" @input="filterCallback()"
								class="p-column-filter" placeholder="Search by country" />
						</template>
					</Column>
					<Column header="Agent" filterField="representative" :showFilterMenu="false"
						:filterMenuStyle="{ width: '14rem' }" style="min-width: 14rem">
						<template #body="{ data }">
							<div class="flex align-items-center gap-2">
								<img :alt="data.representative.name"
									:src="`https://primefaces.org/cdn/primevue/images/avatar/${data.representative.image}`"
									style="width: 32px" />
								<span>{{ data.representative.name }}</span>
							</div>
						</template>
						<template #filter="{ filterModel, filterCallback }">
							<MultiSelect v-model="filterModel.value" @change="filterCallback()" :options="representatives"
								optionLabel="name" placeholder="Any" class="p-column-filter" style="min-width: 14rem"
								:maxSelectedLabels="1">
								<template #option="slotProps">
									<div class="flex align-items-center gap-2">
										<img :alt="slotProps.option.name"
											:src="`https://primefaces.org/cdn/primevue/images/avatar/${slotProps.option.image}`"
											style="width: 32px" />
										<span>{{ slotProps.option.name }}</span>
									</div>
								</template>
							</MultiSelect>
						</template>
					</Column>
					<Column field="status" header="Status" :showFilterMenu="false" :filterMenuStyle="{ width: '14rem' }"
						style="min-width: 12rem">
						<template #body="{ data }">
							<Tag :value="data.status" :severity="getSeverity(data.status)" />
						</template>
						<template #filter="{ filterModel, filterCallback }">
							<Dropdown v-model="filterModel.value" @change="filterCallback()" :options="statuses"
								placeholder="Select One" class="p-column-filter" style="min-width: 12rem" :showClear="true">
								<template #option="slotProps">
									<Tag :value="slotProps.option" :severity="getSeverity(slotProps.option)" />
								</template>
							</Dropdown>
						</template>
					</Column>
					<Column field="verified" header="Verified" dataType="boolean" style="min-width: 6rem">
						<template #body="{ data }">
							<i class="pi"
								:class="{ 'pi-check-circle text-green-500': data.verified, 'pi-times-circle text-red-400': !data.verified }"></i>
						</template>
						<template #filter="{ filterModel, filterCallback }">
							<TriStateCheckbox v-model="filterModel.value" @change="filterCallback()" />
						</template>
					</Column>
				</DataTable>
			</div>
			<div class="card">
				<div class="mt-3">
					<DataTable dataKey="id" :value="Emprestimos" :paginator="true" :rows="10" :loading="loading"
						:filters="filters"
						paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
						:rowsPerPageOptions="[5, 10, 25]"
						currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} Emprestimo(s)"
						responsiveLayout="scroll">
						<template #header>
							<div class="flex justify-content-between">
								<Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros"
									class="p-button-outlined p-button-sm" @click="clearFilter()" />
								<span class="p-input-icon-left">
									<i class="pi pi-search" />
									<InputText v-model="filters.nome_completo.value" placeholder="Informe o Nome"
										class="p-inputtext-sm" />
								</span>
							</div>
						</template>
						<Column field="status" header="status" :sortable="true" class="w-2">
							<template #body="slotProps">
								<Button :label="slotProps.data.status" :class="getStatusClass(slotProps.data.status)" />
							</template>
						</Column>
						<Column field="id" header="Id" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Id</span>
								{{ slotProps.data.id }}
							</template>
						</Column>
						<Column field="name" header="Dt. Lançamento" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Dt. Lançamento</span>
								{{ slotProps.data.dt_lancamento }}
							</template>
						</Column>
						<Column field="name" header="Cliente" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Cliente</span>
								{{ slotProps.data.cliente.nome_completo }}
							</template>
						</Column>
						<Column field="name" header="Consultor" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Consultor</span>
								{{ slotProps.data.consultor.nome_completo }}
							</template>
						</Column>
						<Column field="valor" header="Valor Emprestimo" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Valor</span>
								{{ slotProps.data.valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
							</template>
						</Column>

						<Column field="saldo" header="Saldo a Receber" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Saldo a Pagar</span>
								{{ (slotProps.data.saldoareceber - slotProps.data.saldo_total_parcelas_pagas
								).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}


							</template>
						</Column>
						<Column field="saldo_total_parcelas_pagas" header="Valor Pago" :sortable="true" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Valor Pago</span>
								{{ slotProps.data.saldo_total_parcelas_pagas.toLocaleString('pt-BR', {
									style: 'currency',
									currency: 'BRL'
								}) }}

							</template>
						</Column>
						<Column field="name" header="Parcelas Pagas" :sortable="false" class="w-1">
							<template #body="slotProps">
								<span class="p-column-title">Parcelas Pagas</span>
								{{ `${slotProps.data.parcelas_pagas.length.toString().padStart(3, '0')} /
																${slotProps.data.parcelas.length.toString().padStart(3, '0')}` }}
							</template>
						</Column>
						<Column field="porcentagem" header="Progresso" :sortable="true" class="w-1">
							<template #body="slotProps">
								<ProgressBar :value="slotProps.data.porcentagem" :showValue="true" style="height: 3rem">
								</ProgressBar>
							</template>
						</Column>

						<Column v-if="permissionsService.hasPermissions('view_emprestimos_delete')" field="edit"
							header="Opções" :sortable="false" class="w-1">
							<template #body="slotProps">
							<Menu :ref="`menu_${slotProps.data.id}`" :model="getOverlayMenuItems(slotProps.data)"
								:popup="true" />
							<Button type="button" label="Opções" icon="pi pi-angle-down"
								@click="toggleMenu(slotProps.data.id)" style="width: auto" />
						</template>
					</Column>
				</DataTable>
			</div>
		</div>
	</div>
</div></template>
