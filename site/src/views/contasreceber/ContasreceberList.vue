<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
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
		initFilters() {
            this.filters = {
                id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                nome_fornecedor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                tipodoc: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                descricao: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

                nome_costcenter: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                venc: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                },

                dt_baixa: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                },

                valor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                nome_banco: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

				status: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
            };
        },
		formatValorReal(r) {
            return r.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            });
        },
		getContasreceber() {
			this.loading = true;

			this.contasreceberService.getAll()
			.then((response) => {
				this.Contasreceber = response.data.data;

				this.Contasreceber = response.data.data.map((Contasreceber) => {
                        if (Contasreceber.created_at) {
                            const parts = Contasreceber.created_at.split(' ');
                            const datePart = parts[0].split('/').reverse().join('-'); // Converte dd/mm/yyyy para yyyy-mm-dd
                            const timePart = parts[1];
                            Contasreceber.created_at = new Date(`${datePart}T${timePart}`); // Concatena e cria um objeto Date
                        }

                        if (Contasreceber.dt_baixa) {
                            const datePart = Contasreceber.dt_baixa.split('/').reverse().join('-');
                            Contasreceber.dt_baixa = new Date(`${datePart}T00:00:00`); // Concatena e cria um objeto Date
                        }

                        if (Contasreceber.venc) {
                            const datePart = Contasreceber.venc.split('/').reverse().join('-');
                            Contasreceber.venc = new Date(`${datePart}T00:00:00`); // Concatena e cria um objeto Date
                        }

                        Contasreceber.nome_cliente = Contasreceber.cliente?.nome_completo ?? null;

                        Contasreceber.nome_banco = Contasreceber.banco.name;

                        return Contasreceber;
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
			<div class="col-12">
                <div class="card">
                    <DataTable
                        :value="Contasreceber"
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
                        <template #empty> Nenhum Titulo Encontrado. </template>
                        <template #loading> Carregando os Titulos. Aguarde! </template>

                        <Column field="id" header="ID" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.id }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar ID" />
                            </template>
                        </Column>

                        <Column field="nome_cliente" header="Cliente" style="min-width: 5rem">
                            <template #body="{ data }">
								{{ data.nome_cliente }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pelo Nome" />
                            </template>
                        </Column>

                        <Column field="tipodoc" header="Tipo docto" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.tipodoc }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pelo Tipo" />
                            </template>
                        </Column>

                        <Column field="descricao" header="Descrição" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.descricao }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pela descrição" />
                            </template>
                        </Column>

                        <Column header="Dt. Venc." filterField="venc" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.venc ? data.venc.toLocaleDateString('pt-BR') : '-' }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
                            </template>
                        </Column>

                        <Column header="Pagto" filterField="dt_baixa" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.dt_baixa ? data.dt_baixa.toLocaleDateString('pt-BR') : '-' }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
                            </template>
                        </Column>

						<Column header="Valor R$" filterField="valor" dataType="numeric" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatValorReal(data.valor) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputNumber v-model="filterModel.value" mode="currency" currency="BRL" locale="pt-BR" />
                            </template>
                        </Column>

                        <Column field="nome_banco" header="Conta util" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.nome_banco }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pelo Banco" />
                            </template>
                        </Column>

                        <Column field="status" header="Status" style="min-width: 5rem">
                            <template #body="slotProps">
                                <span class="p-column-title">Status</span>
                                {{ slotProps.data.status }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pelo Status" />
                            </template>
                        </Column>
                        
                    </DataTable>
                </div>
            </div>
			
		</div>
	</div>
</template>
