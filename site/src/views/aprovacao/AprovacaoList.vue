<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
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
        dadosSensiveis(dado) {
            return this.permissionsService.hasPermissions('view_Contaspagar_sensitive') ? dado : '*********';
        },
        getPagamentosPendentes() {
            this.loading = true;

            this.contaspagarService
                .getPagamentosPendentes()
                .then((response) => {
                    this.Contaspagar = response.data.data;

                    this.Contaspagar = response.data.data.map((Contaspagar) => {
                        if (Contaspagar.created_at) {
                            const parts = Contaspagar.created_at.split(' ');
                            const datePart = parts[0].split('/').reverse().join('-'); // Converte dd/mm/yyyy para yyyy-mm-dd
                            const timePart = parts[1];
                            Contaspagar.created_at = new Date(`${datePart}T${timePart}`); // Concatena e cria um objeto Date
                        }

                        if (Contaspagar.dt_baixa) {
                            const datePart = Contaspagar.dt_baixa.split('/').reverse().join('-');
                            Contaspagar.dt_baixa = new Date(`${datePart}T00:00:00`); // Concatena e cria um objeto Date
                        }

                        if (Contaspagar.venc) {
                            const datePart = Contaspagar.venc.split('/').reverse().join('-');
                            Contaspagar.venc = new Date(`${datePart}T00:00:00`); // Concatena e cria um objeto Date
                        }

                        Contaspagar.nome_costcenter = Contaspagar.costcenter.name;

                        Contaspagar.nome_fornecedor = Contaspagar.fornecedor?.nome_completo ?? null;

                        Contaspagar.nome_banco = Contaspagar.banco.name;

                        return Contaspagar;
                    });

					console.log('this.Contaspagar', this.Contaspagar)
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
        editCategory(data) {
            if (undefined === data.emprestimo?.id) this.router.push(`/emprestimos/${data.id}/aprovacao_contaspagar`);
            else this.router.push(`/emprestimos/${data.emprestimo?.id}/aprovacao`);
        },
		formatValorReal(r) {
            return r.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            });
        },
        deleteCategory(permissionId) {
            this.loading = true;

            this.contaspagarService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getPagamentosPendentes();
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
        this.permissionsService.hasPermissionsView('view_contaspagar');
        this.getPagamentosPendentes();
    }
};
</script>

<template>
    <Toast />
    <div class="grid">
        <div class="col-12">
            <div class="grid flex flex-wrap mb-3 px-4 pt-2">
                <div class="col-8 px-0 py-0">
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Aprovação</h5>
                </div>
                <!-- <div class="col-4 px-0 py-0 text-right">
					<Button v-if="permissionsService.hasPermissions('view_contaspagar_create')" label="Novo Título" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
				</div> -->
            </div>
            <div class="col-12">
                <div class="card">
                    <DataTable
                        :value="Contaspagar"
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
                        <template #empty> Nenhuma Aprovação Encontrada. </template>
                        <template #loading> Carregando as aprovações. Aguarde! </template>

                        <Column field="id" header="ID" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.id }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar ID" />
                            </template>
                        </Column>

                        <Column field="nome_fornecedor" header="Fornecedor" style="min-width: 5rem">
                            <template #body="{ data }">
								{{ data.nome_fornecedor ?? 'Empréstimo' }}
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

                        <Column field="nome_costcenter" header="Cto custo" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.nome_costcenter }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pelo Centro de Custo" />
                            </template>
                        </Column>

                        <Column header="Venc." filterField="venc" dataType="date" style="min-width: 10rem">
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

                        <Column header="Saldo no Banco" filterField="valor" dataType="numeric" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatValorReal(data.banco.saldo) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputNumber v-model="filterModel.value" mode="currency" currency="BRL" locale="pt-BR" />
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
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar pelo Banco" />
                            </template>
                        </Column>

                        <Column v-if="permissionsService.hasPermissions('view_contaspagar_baixa')" field="edit" header="Visualizar" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.EYE" v-tooltip.top="'Visualizar'" @click.prevent="editCategory(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
            <!-- <div class="card">
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
                        <Column field="name" header="ID" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">ID</span>
                                {{ slotProps.data.id }}
                            </template>
                        </Column>
                        <Column field="name" header="Fornecedor" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">Fornecedor</span>
                                {{ slotProps.data.fornecedor?.nome_completo ?? 'Empréstimo' }}
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
                                {{ slotProps.data.banco.name }}
                            </template>
                        </Column>

                        <Column field="Status" header="Status" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">Status</span>
                                {{ slotProps.data.status }}
                            </template>
                        </Column>

                        <Column v-if="permissionsService.hasPermissions('view_contaspagar_baixa')" field="edit" header="Visualizar" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0" type="button" :icon="icons.EYE" v-tooltip.top="'Visualizar'" @click.prevent="editCategory(slotProps.data)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div> -->
        </div>
    </div>
</template>
