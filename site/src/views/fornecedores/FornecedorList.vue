<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
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
		initFilters() {
            this.filters = {
                nome_completo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

				cpfcnpj: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

				telefone_celular_1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

				telefone_celular_2: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

				created_at: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                }

				
            };
        },
		getFornecedores() {
			this.loading = true;

			this.fornecedorService.getAll()
			.then((response) => {
				this.fornecedores = response.data.data;

				this.fornecedores = response.data.data.map((fornecedores) => {
                        if (fornecedores.created_at) {
                            const parts = fornecedores.created_at.split(' ');
                            const datePart = parts[0].split('/').reverse().join('-'); // Converte dd/mm/yyyy para yyyy-mm-dd
                            const timePart = parts[1];
                            fornecedores.created_at = new Date(`${datePart}T${timePart}`); // Concatena e cria um objeto Date
                        }
                        return fornecedores;
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
			<div class="col-12">
                <div class="card">
                    <DataTable :value="fornecedores" :paginator="true" class="p-datatable-gridlines" :rows="10" dataKey="id" :rowHover="true" v-model:filters="filters" filterDisplay="menu" :loading="loading" :filters="filters" responsiveLayout="scroll">
                        <template #empty> Nenhum Fornecedor Encontrado. </template>
                        <template #loading> Carregando os Fornecedores. Aguarde! </template>

                        <Column field="nome_completo" header="Nome Completo" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_completo }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar nome" />
                            </template>
                        </Column>

                        <Column field="cpfcnpj" header="CPF / CNPJ" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.cpfcnpj }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar CPF / CNPJ" />
                            </template>
                        </Column>

						<Column field="telefone_celular_1" header="Telefone Principal" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.telefone_celular_1 }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Telefone Principal" />
                            </template>
                        </Column>

						<Column field="telefone_celular_2" header="Telefone Secundário" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.telefone_celular_2 }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Telefone Secundário" />
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
