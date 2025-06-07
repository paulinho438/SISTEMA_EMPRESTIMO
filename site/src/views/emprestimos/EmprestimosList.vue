<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { FilterMatchMode, PrimeIcons, ToastSeverity, FilterOperator } from 'primevue/api';
import EmprestimoService from '@/service/EmprestimoService';
import PermissionsService from '@/service/PermissionsService';
import UtilService from '@/service/UtilService';
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
            totalPages: ref(0),
            currentPage: ref(1),
            perPage: ref(10),
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
                    label: 'Protestar Emprestimo',
                    icon: 'pi pi-lock'
                },
                {
                    separator: true
                },
                {
                    label: 'Home',
                    icon: 'pi pi-home'
                }
            ])
        };
    },
    methods: {
        initFilters() {
            this.filters = {
                global: { value: null, matchMode: FilterMatchMode.CONTAINS },

                status: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },

                id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },

                nome_cliente: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },

                nome_consultor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },

                valor: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },

                saldoareceber: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                saldo_total_parcelas_pagas: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },

                dt_lancamento: {
                    operator: 'and',
                    constraints: [{ value: null, matchMode: 'dateIs' }]
                },

                porcentagem: { value: [0, 100], matchMode: FilterMatchMode.BETWEEN }

                // cpf: {
                //     operator: FilterOperator.AND,
                //     constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }]
                // },
                // rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // telefone_celular_1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // telefone_celular_2: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // rg: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
                // saldo: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
                // created_at: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // },
                // data_nascimento: {
                //     operator: 'and',
                //     constraints: [{ value: null, matchMode: 'dateIs' }]
                // }
            };
        },
        getStatusClass(status) {
            // Adicione classes de botões com base no status
            switch (status) {
                case 'Pago':
                    return 'p-button-rounded p-button-success mr-2 mb-2';
                case 'Em Dias':
                    return 'p-button-rounded p-button-success mr-2 mb-2';
                case 'Atrasado':
                    return 'p-button-rounded p-button-info mr-2 mb-2';
                case 'Muito Atrasado':
                    return 'p-button-rounded p-button-warning';
                case 'Protesto':
                    return 'p-button-rounded p-button-warning';

                default:
                    return 'p-button-rounded p-button-danger mr-2 mb-2'; // Padrão
            }
        },
        showMenu(data) {
            // Lógica para decidir se mostrar ou não o menu com base nos dados
            return true; // Substitua someCondition pela sua lógica específica
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

            // if(data.protesto == 0){
            //     contextMenuItems.push({
            //         label: 'Protestar Emprestimo',
            //         icon: 'pi pi-lock',
            //         command: () => this.protestarEmprestimo(data.id)
            //     });
            // }

            return contextMenuItems;
        },
        formatValorReal(r) {
            return r.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            });
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
            return this.permissionsService.hasPermissions('view_emprestimos_sensitive') ? dado : '*********';
        },
        getEmprestimos(page = 1) {
            this.loading = true;

            this.currentPage = page; // Atualiza a página atual corretamente

            const params = {
                page: this.currentPage,
                per_page: this.perPage
            };

            // Adicionando os filtros dinamicamente
            Object.keys(this.filters).forEach((key) => {
                const constraint = this.filters[key]?.constraints?.[0]; // Verifica se existe constraints[0]
                if (constraint && constraint.value !== null && constraint.value !== undefined) {
                    params[key] = constraint.value;
                }
            });

            // Adiciona o filtro global aos parâmetros
            if (this.filters.global.value) {
                params.global = this.filters.global.value;
            }

            this.emprestimoService
                .getAll(params) // Passa paginação na requisição
                .then((response) => {
                    this.Emprestimos = response.data.data;
                    this.totalPages = response.data.meta.last_page; // Obtém total de páginas

                    // Formata os dados recebidos para melhor uso
                    this.Emprestimos = this.Emprestimos.map((emprestimo) => {
                        emprestimo.nome_cliente = emprestimo.cliente?.nome_completo || 'N/A';
                        emprestimo.nome_consultor = emprestimo.consultor?.nome_completo || 'N/A';

                        return emprestimo;
                    });
                })
                .catch((error) => {
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
        changePage(event) {
            this.getEmprestimos(event.page + 1);
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

            this.emprestimoService
                .delete(permissionId)
                .then((e) => {
                    console.log(e);
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
                        detail: UtilService.message(error.response.data),
                        life: 3000
                    });
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        protestarEmprestimo(permissionId) {
            this.loading = true;

            this.emprestimoService
                .protestarEmprestimo(permissionId)
                .then((e) => {
                    console.log(e);
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
                    <Button v-if="permissionsService.hasPermissions('view_emprestimos_create')" label="Novo Emprestimo" class="p-button-outlined p-button-secondary p-button-sm" icon="pi pi-plus" @click.prevent="editCategory()" />
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <DataTable
                        :value="Emprestimos"
                        :paginator="true"
                        class="p-datatable-gridlines"
                        :rows="perPage"
                        :totalRecords="totalPages * perPage"
                        :lazy="true"
                        dataKey="id"
                        :rowHover="true"
                        v-model:filters="filters"
                        filterDisplay="menu"
                        :loading="loading"
                        responsiveLayout="scroll"
                        @filter="getEmprestimos"
                        @page="changePage"
                    >
                        <template #header>
                            <div class="flex justify-content-between flex-column sm:flex-row">
                                <Button type="button" icon="pi pi-filter-slash" label="Clear" class="p-button-outlined mb-2" @click="clearFilter()" />
                                <span class="p-input-icon-left mb-2">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters['global'].value" placeholder="Pesquisar ..." style="width: 100%" @input="getEmprestimos" />
                                </span>
                            </div>
                        </template>
                        <template #empty> Nenhum Cliente Encontrado. </template>
                        <template #loading> Carregando os Clientes. Aguarde! </template>

                        <Column field="status" header="Status" style="min-width: 9rem">
                            <template #body="slotProps">
                                <Button :label="slotProps.data.status" :class="getStatusClass(slotProps.data.status)" />
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Status" />
                            </template>
                        </Column>

                        <Column field="id" header="ID" style="min-width: 5rem">
                            <template #body="{ data }">
                                {{ data.id }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar ID" />
                            </template>
                        </Column>

                        <Column header="Dt. Lançamento" filterField="dt_lancamento" dataType="date" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ data.dt_lancamento }}
                            </template>
                            <template #filter="{ filterModel }">
                                <Calendar v-model="filterModel.value" dateFormat="dd/mm/yy" placeholder="Selecione uma data" class="p-column-filter" />
                            </template>
                        </Column>

                        <Column field="nome_cliente" header="Cliente" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_cliente }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Nome Completo Cliente" />
                            </template>
                        </Column>

                        <Column field="nome_consultor" header="Consultor" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ data.nome_consultor }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Buscar Nome do Consultor" />
                            </template>
                        </Column>

                        <Column header="Valor" filterField="valor" dataType="numeric" style="min-width: 8rem">
                            <template #body="{ data }">
                                {{ formatValorReal(data.valor) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputNumber v-model="filterModel.value" mode="currency" currency="BRL" locale="pt-BR" />
                            </template>
                        </Column>

                        <Column header="Saldo a Receber" filterField="saldoareceber" dataType="numeric" style="min-width: 8rem">
                            <template #body="{ data }">
                                {{ formatValorReal(data.saldoareceber) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputNumber v-model="filterModel.value" mode="currency" currency="BRL" locale="pt-BR" />
                            </template>
                        </Column>

                        <Column header="Valor Pago" filterField="saldo_total_parcelas_pagas" dataType="numeric" style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatValorReal(data.saldo_total_parcelas_pagas) }}
                            </template>
                            <template #filter="{ filterModel }">
                                <InputNumber v-model="filterModel.value" mode="currency" currency="BRL" locale="pt-BR" />
                            </template>
                        </Column>

                        <Column field="name" header="Parcelas Pagas" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">Parcelas Pagas</span>
                                {{
                                    `${slotProps.data.parcelas_pagas.length.toString().padStart(3, '0')} /
																${slotProps.data.parcelas.length.toString().padStart(3, '0')}`
                                }}
                            </template>
                        </Column>

                        <Column field="porcentagem" header="Progresso" :showFilterMatchModes="false" style="min-width: 12rem">
                            <template #body="{ data }">
                                {{ $filters.percentage(data.porcentagem) }}
                                <ProgressBar :value="data.porcentagem" :showValue="false" style="height: 0.5rem"></ProgressBar>
                            </template>
                            <template #filter="{ filterModel }">
                                <Slider v-model="filterModel.value" :range="true" class="m-3"></Slider>
                                <div class="flex align-items-center justify-content-between px-2">
                                    <span>{{ filterModel.value ? filterModel.value[0] : 0 }}</span>
                                    <span>{{ filterModel.value ? filterModel.value[1] : 100 }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column v-if="permissionsService.hasPermissions('view_emprestimos_delete')" field="edit" header="Opções" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Menu :ref="`menu_${slotProps.data.id}`" :model="getOverlayMenuItems(slotProps.data)" :popup="true" />
                                <Button type="button" label="Opções" icon="pi pi-angle-down" @click="toggleMenu(slotProps.data.id)" style="width: auto" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
            <!-- <div class="card">
                <div class="mt-3">
                    <DataTable
                        dataKey="id"
                        :value="Emprestimos"
                        :paginator="true"
                        :rows="10"
                        :loading="loading"
                        :filters="filters"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[5, 10, 25]"
                        currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} Emprestimo(s)"
                        responsiveLayout="scroll"
                    >
                        <template #header>
                            <div class="flex justify-content-between">
                                <Button type="button" icon="pi pi-filter-slash" label="Limpar Filtros" class="p-button-outlined p-button-sm" @click="clearFilter()" />
                                <span class="p-input-icon-left">
                                    <i class="pi pi-search" />
                                    <InputText v-model="filters.nome_completo.value" placeholder="Informe o Nome" class="p-inputtext-sm" />
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
                                {{ slotProps.data.cliente?.nome_completo }}
                            </template>
                        </Column>
                        <Column field="name" header="Consultor" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">Consultor</span>
                                {{ slotProps.data.consultor?.nome_completo }}
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
                                {{ slotProps.data.saldoareceber.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                            </template>
                        </Column>
                        <Column field="saldo_total_parcelas_pagas" header="Valor Pago" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">Valor Pago</span>
                                {{
                                    slotProps.data.saldo_total_parcelas_pagas.toLocaleString('pt-BR', {
                                        style: 'currency',
                                        currency: 'BRL'
                                    })
                                }}
                            </template>
                        </Column>
                        <Column field="name" header="Parcelas Pagas" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <span class="p-column-title">Parcelas Pagas</span>
                                {{
                                    `${slotProps.data.parcelas_pagas.length.toString().padStart(3, '0')} /
																${slotProps.data.parcelas.length.toString().padStart(3, '0')}`
                                }}
                            </template>
                        </Column>
                        <Column field="porcentagem" header="Progresso" :sortable="true" class="w-1">
                            <template #body="slotProps">
                                <ProgressBar :value="slotProps.data.porcentagem" :showValue="true" style="height: 3rem"> </ProgressBar>
                            </template>
                        </Column>

                        <Column v-if="permissionsService.hasPermissions('view_emprestimos_delete')" field="edit" header="Opções" :sortable="false" class="w-1">
                            <template #body="slotProps">
                                <Menu :ref="`menu_${slotProps.data.id}`" :model="getOverlayMenuItems(slotProps.data)" :popup="true" />
                                <Button type="button" label="Opções" icon="pi pi-angle-down" @click="toggleMenu(slotProps.data.id)" style="width: auto" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div> -->
        </div>
    </div>
</template>
