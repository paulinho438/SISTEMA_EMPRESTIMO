<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import FeriadoService from '@/service/FeriadoService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			feriadoService: new FeriadoService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			feriados: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		initFilters() {
            this.filters = {
                description: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				
                data_feriado: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                }
            };
        },
		dadosSensiveis(dado) {
			return (this.permissionsService.hasPermissions('view_Feriadoes_sensitive') ? dado : '*********')
		},
		getFeriados() {
			this.loading = true;

			this.feriadoService.getAll()
				.then((response) => {
					this.feriados = response.data?.data;

					this.feriados = response.data.data.map((feriados) => {

						if (feriados.data_feriado) {
							const datePart = feriados.data_feriado.split('/').reverse().join('-');
                            feriados.data_feriado = new Date(`${datePart}T00:00:00`); // Concatena e cria um objeto Date
                        }

                        return feriados;
                    });
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
			if (undefined === id) this.router.push('/feriados/add');
			else this.router.push(`/feriados/${id}/edit`);
		},
		deleteCategory(permissionId) {
			this.loading = true;

			this.feriadoService.delete(permissionId)
				.then((e) => {
					console.log(e)
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: e?.data?.message,
						life: 3000
					});
					this.getFeriados();
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
		clearFilter() {
			this.initFilters();
		}
	},
	beforeMount() {
		this.initFilters();
	},
	mounted() {
		this.permissionsService.hasPermissionsView('view_clientes');
		this.getFeriados();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Feriados</h5>
				</div>
				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_clientes_create')" label="Novo Feriado"
						class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus"
						@click.prevent="editCategory()" />
				</div>
			</div>
			<div class="col-12">
                <div class="card">
                    <DataTable :value="feriados" :paginator="true" class="p-datatable-gridlines" :rows="10" dataKey="id" :rowHover="true" v-model:filters="filters" filterDisplay="menu" :loading="loading" :filters="filters" responsiveLayout="scroll">
                        <template #empty> Nenhum Feriado Encontrado. </template>
                        <template #loading> Carregando os Feriados. Aguarde! </template>

                        <Column field="description" header="Descrição" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.description }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Descrição" />
                            </template>
                        </Column>

						<Column header="Data do Feriado" filterField="data_feriado" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.data_feriado ? data.data_feriado.toLocaleDateString('pt-BR') : '-' }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
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
						<Column v-if="permissionsService.hasPermissions('view_clientes_delete')" field="edit"
							header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard"
									class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
									type="button" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'"
									@click.prevent="deleteCategory(slotProps.data.id)" />
							</template>
						</Column>
                    </DataTable>
                </div>
            </div>
		</div>
</div></template>
