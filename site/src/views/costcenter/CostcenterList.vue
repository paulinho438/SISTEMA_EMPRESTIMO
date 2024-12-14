<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import CostcenterService from '@/service/CostcenterService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'CicomList',
	setup() {
		return {
			costcenterService: new CostcenterService(),
			permissionsService: new PermissionsService(),
			router: useRouter(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			constcenter: ref([]),
			loading: ref(false),
			filters: ref(null)
		};
	},
	methods: {
		getCostcenter() {
			this.loading = true;

			this.costcenterService.getAll()
			.then((response) => {
				this.constcenter = response.data.data;

				this.constcenter = response.data.data.map((constcenter) => {
                        // Verifica se 'created_at' não é null antes de tentar converter
                        if (constcenter.created_at) {
                            const parts = constcenter.created_at.split(' ');
                            const datePart = parts[0].split('/').reverse().join('-'); // Converte dd/mm/yyyy para yyyy-mm-dd
                            const timePart = parts[1];
                            constcenter.created_at = new Date(`${datePart}T${timePart}`); // Concatena e cria um objeto Date
                        }
                        return constcenter;
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
		editCostcenter(id) {
			if (undefined === id) this.router.push('/centro_de_custo/add');
			else this.router.push(`/centro_de_custo/${id}/edit`);
		},
		deleteCostcenter(permissionId) {
			this.loading = true;

			this.costcenterService.delete(permissionId)
			.then((e) => {
				console.log(e)
				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: e?.data?.message,
					life: 3000
				});
				this.getCostcenter();
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
				name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				description: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
				created_at: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                }
                // global: { value: null, matchMode: FilterMatchMode.CONTAINS },
                // name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // 'country.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // representative: { value: null, matchMode: FilterMatchMode.IN },
                // date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
                // balance: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] }, // numerico
                // status: { operator: FilterOperator.OR, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                // activity: { value: [0, 50], matchMode: FilterMatchMode.BETWEEN },
                // verified: { value: null, matchMode: FilterMatchMode.EQUALS },
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
		this.permissionsService.hasPermissionsView('view_costcenter');
		this.getCostcenter();
	}
};
</script>

<template>
	<Toast />
	<div class="grid">
		<div class="col-12">
			<div class="grid flex flex-wrap mb-3 px-4 pt-2">
				<div class="col-8 px-0 py-0">
					<h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Centro de Custo</h5>
				</div>
				<div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_costcenter_create')" label="Novo Centro de Custo" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCostcenter()" />
				</div>
			</div>
			<div class="col-12">
				<div class="card">
					<DataTable
						:value="constcenter"
						:paginator="true"
						class="p-datatable-gridlines"
						:rows="10"
						dataKey="id"
						:rowHover="true"
						v-model:filters="filters"
						filterDisplay="menu"
						:loading="loading"
						:filters="filters"
						responsiveLayout="scroll"
					>
						<template #empty> Nenhuma Categoria Encontrada. </template>
						<template #loading> Carregando Categorias. Aguarde! </template>
						<Column field="name" header="Nome" style="min-width: 12rem">
							<template #body="{ data }">
								{{ data.name }}
							</template>
							<template #filter="{ filterModel }">
								<InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar nome" />
							</template>
						</Column>
	
						<Column field="description" header="Descrição" style="min-width: 12rem">
							<template #body="{ data }">
								{{ data.description }}
							</template>
							<template #filter="{ filterModel }">
								<InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Descrição" />
							</template>
						</Column>
	
						<Column header="Dt. Criação" filterField="created_at" dataType="date" style="min-width: 10rem">
							<template #body="{ data }">
								{{ data.created_at ? data.created_at.toLocaleDateString('pt-BR') : '-' }}
							</template>
							<template #filter="{ filterModel }">
								<Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
							</template>
						</Column>
	
						<Column v-if="permissionsService.hasPermissions('view_costcenter_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.FILE_EDIT" v-tooltip.top="'Editar'" @click.prevent="editCostcenter(slotProps.data.id)" />
							</template>
						</Column>
						<Column v-if="permissionsService.hasPermissions('view_costcenter_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
							<template #body="slotProps">
								<Button v-if="!slotProps.data.standard" class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :disabled="slotProps.data.total_users > 0" :icon="icons.FILE_EXCEL" v-tooltip.top="'Excluir'" @click.prevent="deleteCostcenter(slotProps.data.id)" />
							</template>
						</Column>
					</DataTable>
				</div>
			</div>
		</div>
		
	</div>
</template>
