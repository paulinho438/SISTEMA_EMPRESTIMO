<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import ClientService from '@/service/ClientService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'PessoaFisicaList',
	setup() {
		return {
			clientService: new ClientService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			Clientes: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		initFilters() {
			this.filters = {
				global: { value: null, matchMode: FilterMatchMode.CONTAINS },
				nome_completo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				cpf: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				telefone_celular_1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] }
			};
		},
		getClientes() {
			this.loading = true;
			this.clientService
				.getByTipoPessoa('PF')
				.then((response) => {
					this.Clientes = response.data?.data || [];
					this.Clientes = this.Clientes.map((c) => {
						if (c.created_at) {
							const parts = c.created_at.split(' ');
							const datePart = parts[0]?.split('/')?.reverse()?.join('-') || parts[0];
							const timePart = parts[1] || '00:00:00';
							c.created_at = new Date(`${datePart}T${timePart}`);
						}
						if (c.data_nascimento) {
							const datePart = c.data_nascimento.split('/')?.reverse()?.join('-') || c.data_nascimento;
							c.data_nascimento = new Date(`${datePart}T00:00:00`);
						}
						return c;
					});
				})
				.catch((error) => {
					this.toast.add({
						severity: ToastSeverity.ERROR,
						detail: error?.message || 'Erro ao carregar pessoas físicas',
						life: 3000
					});
				})
				.finally(() => {
					this.loading = false;
				});
		},
		editCliente(id) {
			if (undefined === id) this.router.push({ name: 'pfAdd' });
			else this.router.push({ name: 'pfEdit', params: { id } });
		},
		deleteCliente(id) {
			this.loading = true;
			this.clientService
				.delete(id)
				.then((e) => {
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: e?.data?.message || 'Pessoa física excluída com sucesso',
						life: 3000
					});
					this.getClientes();
				})
				.catch((error) => {
					this.toast.add({
						severity: ToastSeverity.ERROR,
						detail: error?.response?.data?.message || 'Erro ao excluir',
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
		this.permissionsService.hasPermissionsView('view_clientes');
		this.getClientes();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.USER"></i> Pessoas Físicas</h5>
				</div>
				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_clientes_create')" label="Nova Pessoa Física" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.PLUS" @click.prevent="editCliente()" />
				</div>
			</div>
			<div class="col-12">
				<div class="card">
					<DataTable
						:value="Clientes"
						:paginator="true"
						class="p-datatable-gridlines"
						:rows="10"
						dataKey="id"
						:rowHover="true"
						v-model:filters="filters"
						filterDisplay="menu"
						:loading="loading"
						responsiveLayout="scroll"
						:globalFilterFields="['nome_completo', 'cpf', 'telefone_celular_1']"
					>
						<template #header>
							<div class="flex justify-content-between flex-column sm:flex-row">
								<Button type="button" :icon="icons.FILTER_SLASH" label="Limpar" class="p-button-outlined mb-2" @click="clearFilter()" />
								<span class="p-input-icon-left mb-2">
									<i class="pi pi-search" />
									<InputText v-model="filters['global'].value" placeholder="Pesquisar ..." style="width: 100%" />
								</span>
							</div>
						</template>
						<template #empty>Nenhuma pessoa física encontrada.</template>
						<template #loading>Carregando...</template>

						<Column field="nome_completo" header="Nome Completo" style="min-width: 14rem">
							<template #body="{ data }">
								{{ data.nome_completo }}
							</template>
						</Column>
						<Column field="cpf" header="CPF" style="min-width: 12rem">
							<template #body="{ data }">
								{{ data.cpf }}
							</template>
						</Column>
						<Column field="telefone_celular_1" header="Telefone" style="min-width: 10rem">
							<template #body="{ data }">
								{{ data.telefone_celular_1 }}
							</template>
						</Column>
						<Column field="email" header="E-mail" style="min-width: 12rem">
							<template #body="{ data }">
								{{ data.email }}
							</template>
						</Column>
						<Column header="Dt. Nascimento" filterField="data_nascimento" dataType="date" style="min-width: 10rem">
							<template #body="{ data }">
								{{ data.data_nascimento ? data.data_nascimento.toLocaleDateString('pt-BR') : '—' }}
							</template>
						</Column>
						<Column header="Dt. Criação" filterField="created_at" dataType="date" style="min-width: 10rem">
							<template #body="{ data }">
								{{ data.created_at ? data.created_at.toLocaleDateString('pt-BR') : '—' }}
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_clientes_edit')" header="Editar" :sortable="false" style="min-width: 6rem">
							<template #body="slotProps">
								<Button
									class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
									type="button"
									:icon="icons.FILE_EDIT"
									v-tooltip.top="'Editar'"
									@click.prevent="editCliente(slotProps.data.id)"
								/>
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_clientes_delete')" header="Excluir" :sortable="false" style="min-width: 6rem">
							<template #body="slotProps">
								<Button
									class="p-button p-button-icon-only p-button-text p-button-danger m-0 p-0"
									type="button"
									:icon="icons.TRASH"
									v-tooltip.top="'Excluir'"
									@click.prevent="deleteCliente(slotProps.data.id)"
								/>
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
	</div>
</template>
