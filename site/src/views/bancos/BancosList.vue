<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import BancoService from '@/service/BancoService';
import UtilService from '@/service/UtilService';
import PermissionsService from '@/service/PermissionsService';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'CicomList',
    setup() {
        return {
            bancoService: new BancoService(),
            permissionsService: new PermissionsService(),
            router: useRouter(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    data() {
        return {
            bancos: ref([]),
            loading: ref(false),
            filters: ref(null)
        };
    },
    methods: {
        getCostcenter() {
            this.loading = true;

            this.bancoService
                .getAll()
                .then((response) => {
                    this.bancos = response.data.data;

                    this.bancos = response.data.data.map((bancos) => {
                        // Verifica se 'created_at' não é null antes de tentar converter
                        if (bancos.created_at) {
                            const parts = bancos.created_at.split(' ');
                            const datePart = parts[0].split('/').reverse().join('-'); // Converte dd/mm/yyyy para yyyy-mm-dd
                            const timePart = parts[1];
                            bancos.created_at = new Date(`${datePart}T${timePart}`); // Concatena e cria um objeto Date
                        }
                        return bancos;
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
        initFilters() {
            this.filters = {
                name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                agencia: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                conta: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                saldo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
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
        formatValorReal(r) {
            return r.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            });
        },
        editCostcenter(id) {
            if (undefined === id) this.router.push('/bancos/add');
            else this.router.push(`/bancos/${id}/edit`);
        },
        deleteBanco(permissionId) {
            this.loading = true;

            this.bancoService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: e?.data?.message,
                        life: 3000
                    });
                    this.getCostcenter();
                })
                .catch((error) => {
                    console.log('error', error);
                    this.toast.add({
                        severity: ToastSeverity.ERROR,
                        detail: UtilService.message(error.response.data),
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
        this.permissionsService.hasPermissionsView('view_bancos');
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
                    <h5 class="px-0 py-0 align-self-center m-2"><i class="pi pi-building"></i> Lista de Bancos</h5>
                </div>
                <div class="col-4 px-0 py-0 text-right">
                    <Button v-if="permissionsService.hasPermissions('view_bancos_create')" label="Novo Banco" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCostcenter()" />
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <DataTable :value="bancos" :paginator="true" class="p-datatable-gridlines" :rows="10" dataKey="id" :rowHover="true" v-model:filters="filters" filterDisplay="menu" :loading="loading" :filters="filters" responsiveLayout="scroll">
                        <template #empty> Nenhum Banco Encontrado. </template>
                        <template #loading> Carregando os Bancos. Aguarde! </template>
                        <Column field="name" header="Nome" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.name }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar nome" />
                            </template>
                        </Column>

                        <Column field="agencia" header="Descrição" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.agencia }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Agencia" />
                            </template>
                        </Column>

                        <Column field="conta" header="Conta" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.conta }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Conta" />
                            </template>
                        </Column>

                        <Column header="Saldo" filterField="saldo" dataType="numeric" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.wallet ? formatValorReal(data.saldo_banco) : formatValorReal(data.saldo) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputNumber v-model="filterModel.value" mode="currency" currency="BRL" locale="pt-BR" />
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

                        <Column v-if="permissionsService.hasPermissions('view_bancos_edit')" field="edit" header="Editar" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard"
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :icon="icons.FILE_EDIT"
                                    v-tooltip.top="'Editar'"
                                    @click.prevent="editCostcenter(slotProps.data.id)"
                                />
                            </template>
                        </Column>
                        <Column v-if="permissionsService.hasPermissions('view_bancos_delete')" field="edit" header="Excluir" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Button
                                    v-if="!slotProps.data.standard"
                                    class="p-button p-button-icon-only p-button-text p-button-secondary m-0 p-0"
                                    type="button"
                                    :disabled="slotProps.data.total_users > 0"
                                    :icon="icons.FILE_EXCEL"
                                    v-tooltip.top="'Excluir'"
                                    @click.prevent="deleteBanco(slotProps.data.id)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </div>
</template>
