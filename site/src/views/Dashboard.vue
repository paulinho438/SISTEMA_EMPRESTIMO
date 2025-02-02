<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import ProductService from '@/service/ProductService';
import DashboardService from '@/service/DashboardService';
import { useLayout } from '@/layout/composables/layout';
import PermissionsService from '@/service/PermissionsService';

const { isDarkTheme } = useLayout();

const products = ref(null);
const infoConta = ref(null);

const lineData = reactive({
    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
    datasets: [
        {
            label: 'First Dataset',
            data: [65, 59, 80, 81, 56, 55, 40],
            fill: false,
            backgroundColor: '#2f4860',
            borderColor: '#2f4860',
            tension: 0.4
        },
        {
            label: 'Second Dataset',
            data: [28, 48, 40, 19, 86, 27, 90],
            fill: false,
            backgroundColor: '#00bb7e',
            borderColor: '#00bb7e',
            tension: 0.4
        }
    ]
});
const items = ref([
    { label: 'Add New', icon: 'pi pi-fw pi-plus' },
    { label: 'Remove', icon: 'pi pi-fw pi-minus' }
]);
const lineOptions = ref(null);
const productService = new ProductService();
const dashboardService = new DashboardService();
const permissionService = new PermissionsService();

const getStatusClass = (status) => {
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
        default:
            return 'p-button-rounded p-button-danger mr-2 mb-2'; // Padrão
    }
};
const toggleMenu = (id) => {
    const menuRef = this.$refs[`menu_${id}`];
    menuRef.toggle(event);
};

const getOverlayMenuItems = (data) => {
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
};

onMounted(() => {
    permissionService.hasPermissionsView('view_dashboard');

    dashboardService.infoConta().then((data) => {
        infoConta.value = data.data;
        console.log('data', data.data);
    });
});

const formatCurrency = (value) => {
    return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
};
const applyLightTheme = () => {
    lineOptions.value = {
        plugins: {
            legend: {
                labels: {
                    color: '#495057'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#495057'
                },
                grid: {
                    color: '#ebedef'
                }
            },
            y: {
                ticks: {
                    color: '#495057'
                },
                grid: {
                    color: '#ebedef'
                }
            }
        }
    };
};

const applyDarkTheme = () => {
    lineOptions.value = {
        plugins: {
            legend: {
                labels: {
                    color: '#ebedef'
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#ebedef'
                },
                grid: {
                    color: 'rgba(160, 167, 181, .3)'
                }
            },
            y: {
                ticks: {
                    color: '#ebedef'
                },
                grid: {
                    color: 'rgba(160, 167, 181, .3)'
                }
            }
        }
    };
};

watch(
    isDarkTheme,
    (val) => {
        if (val) {
            applyDarkTheme();
        } else {
            applyLightTheme();
        }
    },
    { immediate: true }
);
</script>

<template>
    <div class="grid">
        <div class="col-12 lg:col-6 xl:col-3">
            <div class="card mb-0">
                <div class="flex justify-content-between mb-3">
                    <div>
                        <span class="block text-500 font-medium mb-3">Total de empretimos</span>
                        <div class="text-900 font-medium text-xl">{{ infoConta?.total_emprestimos ?? 'Carregando...' }}</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-blue-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-shopping-cart text-blue-500 text-xl"></i>
                    </div>
                </div>
                <div class="flex justify-content-between mb-3" style="flex-direction: column; gap: 10px;">
                    <div>
                        <span class="text-green-500 font-medium">Empréstimos Ativos</span>
                        <span class="text-500">{{ infoConta?.total_emprestimos_em_dias + infoConta?.total_emprestimos_muito_atrasados }}</span>
                    </div>
                    <div>
                        <span class="text-green-500 font-medium">Valores Já Investidos </span>
                        <span class="text-500">{{ infoConta?.total_ja_investido.toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                          }) }}</span>
                    </div>
                    <div>
                        <span class="text-green-500 font-medium">Valores A Receber </span>
                        <span class="text-500">{{ infoConta?.total_a_receber.toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                          }) }}</span>
                    </div>
                    <div>
                        <span class="text-green-500 font-medium">Lucro Previsto </span>
                        <span class="text-500">{{ (infoConta?.total_a_receber +  infoConta?.total_ja_recebido - infoConta?.total_ja_investido).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL',
                          }) }}</span>
                    </div>
                </div>

                
            </div>
        </div>
        <div class="col-12 lg:col-6 xl:col-3">
            <div class="card mb-0">
                <div class="flex justify-content-between mb-3">
                    <div>
                        <span class="block text-500 font-medium mb-3">Empréstimos Pagos</span>
                        <div class="text-900 font-medium text-xl">{{ infoConta?.total_emprestimos_pagos ?? 'Carregando...' }}</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-orange-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-map-marker text-orange-500 text-xl"></i>
                    </div>
                </div>
                <span class="text-green-500 font-medium">Valores Recebidos </span>
                <span class="text-500">{{ infoConta?.total_ja_recebido.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</span>
            </div>
        </div>
        <div class="col-12 lg:col-6 xl:col-3">
            <div class="card mb-0">
                <div class="flex justify-content-between mb-3">
                    <div>
                        <span class="block text-500 font-medium mb-3">Empréstimos em dias</span>
                        <div class="text-900 font-medium text-xl">{{ infoConta?.total_emprestimos_em_dias ?? 'Carregando...' }}</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-cyan-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-inbox text-cyan-500 text-xl"></i>
                    </div>
                </div>
                <!-- <span class="text-green-500 font-medium">520 </span>
                <span class="text-500">newly registered</span> -->
            </div>
        </div>
        <div class="col-12 lg:col-6 xl:col-3">
            <div class="card mb-0">
                <div class="flex justify-content-between mb-3">
                    <div>
                        <span class="block text-500 font-medium mb-3">Empréstimos atrasados</span>
                        <div class="text-900 font-medium text-xl">{{ infoConta?.total_emprestimos_muito_atrasados ?? 'Carregando...' }}</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-purple-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-comment text-purple-500 text-xl"></i>
                    </div>
                </div>
                <!-- <span class="text-green-500 font-medium">85 </span>
                <span class="text-500">responded</span> -->
            </div>
        </div>
        <div class="col-12 lg:col-6 xl:col-3">
            <div class="card mb-0">
                <div class="flex justify-content-between mb-3">
                    <div>
                        <span class="block text-500 font-medium mb-3">Total de Clientes</span>
                        <div class="text-900 font-medium text-xl">{{ infoConta?.total_clientes ?? 'Carregando...' }}</div>
                    </div>
                    <div class="flex align-items-center justify-content-center bg-blue-100 border-round" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-shopping-cart text-blue-500 text-xl"></i>
                    </div>
                </div>
                <!-- <span class="text-green-500 font-medium">24 new </span>
                <span class="text-500">since last visit</span> -->
            </div>
        </div>

        <!-- <div class="col-12 xl:col-6">
            <div class="card">
                <h5>Últimos empréstimos</h5>
                <DataTable
                    dataKey="id"
                    :value="infoConta?.ultimos_5_emprestimos"
                    :paginator="true"
                    :rows="10"
                    :loading="loading"
                    :filters="filters"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    :rowsPerPageOptions="[5, 10, 25]"
                    currentPageReportTemplate="Mostrando {first} de {last} de {totalRecords} Emprestimo(s)"
                    responsiveLayout="scroll"
                >
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
                </DataTable>
            </div>
            <div class="card">
                <div class="flex justify-content-between align-items-center mb-5">
                    <h5>Best Selling Products</h5>
                    <div>
                        <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-plain p-button-rounded" @click="$refs.menu2.toggle($event)"></Button>
                        <Menu ref="menu2" :popup="true" :model="items"></Menu>
                    </div>
                </div>
                <ul class="list-none p-0 m-0">
                    <li class="flex flex-column md:flex-row md:align-items-center md:justify-content-between mb-4">
                        <div>
                            <span class="text-900 font-medium mr-2 mb-1 md:mb-0">Space T-Shirt</span>
                            <div class="mt-1 text-600">Clothing</div>
                        </div>
                        <div class="mt-2 md:mt-0 flex align-items-center">
                            <div class="surface-300 border-round overflow-hidden w-10rem lg:w-6rem" style="height: 8px">
                                <div class="bg-orange-500 h-full" style="width: 50%"></div>
                            </div>
                            <span class="text-orange-500 ml-3 font-medium">%50</span>
                        </div>
                    </li>
                    <li class="flex flex-column md:flex-row md:align-items-center md:justify-content-between mb-4">
                        <div>
                            <span class="text-900 font-medium mr-2 mb-1 md:mb-0">Portal Sticker</span>
                            <div class="mt-1 text-600">Accessories</div>
                        </div>
                        <div class="mt-2 md:mt-0 ml-0 md:ml-8 flex align-items-center">
                            <div class="surface-300 border-round overflow-hidden w-10rem lg:w-6rem" style="height: 8px">
                                <div class="bg-cyan-500 h-full" style="width: 16%"></div>
                            </div>
                            <span class="text-cyan-500 ml-3 font-medium">%16</span>
                        </div>
                    </li>
                    <li class="flex flex-column md:flex-row md:align-items-center md:justify-content-between mb-4">
                        <div>
                            <span class="text-900 font-medium mr-2 mb-1 md:mb-0">Supernova Sticker</span>
                            <div class="mt-1 text-600">Accessories</div>
                        </div>
                        <div class="mt-2 md:mt-0 ml-0 md:ml-8 flex align-items-center">
                            <div class="surface-300 border-round overflow-hidden w-10rem lg:w-6rem" style="height: 8px">
                                <div class="bg-pink-500 h-full" style="width: 67%"></div>
                            </div>
                            <span class="text-pink-500 ml-3 font-medium">%67</span>
                        </div>
                    </li>
                    <li class="flex flex-column md:flex-row md:align-items-center md:justify-content-between mb-4">
                        <div>
                            <span class="text-900 font-medium mr-2 mb-1 md:mb-0">Wonders Notebook</span>
                            <div class="mt-1 text-600">Office</div>
                        </div>
                        <div class="mt-2 md:mt-0 ml-0 md:ml-8 flex align-items-center">
                            <div class="surface-300 border-round overflow-hidden w-10rem lg:w-6rem" style="height: 8px">
                                <div class="bg-green-500 h-full" style="width: 35%"></div>
                            </div>
                            <span class="text-green-500 ml-3 font-medium">%35</span>
                        </div>
                    </li>
                    <li class="flex flex-column md:flex-row md:align-items-center md:justify-content-between mb-4">
                        <div>
                            <span class="text-900 font-medium mr-2 mb-1 md:mb-0">Mat Black Case</span>
                            <div class="mt-1 text-600">Accessories</div>
                        </div>
                        <div class="mt-2 md:mt-0 ml-0 md:ml-8 flex align-items-center">
                            <div class="surface-300 border-round overflow-hidden w-10rem lg:w-6rem" style="height: 8px">
                                <div class="bg-purple-500 h-full" style="width: 75%"></div>
                            </div>
                            <span class="text-purple-500 ml-3 font-medium">%75</span>
                        </div>
                    </li>
                    <li class="flex flex-column md:flex-row md:align-items-center md:justify-content-between mb-4">
                        <div>
                            <span class="text-900 font-medium mr-2 mb-1 md:mb-0">Robots T-Shirt</span>
                            <div class="mt-1 text-600">Clothing</div>
                        </div>
                        <div class="mt-2 md:mt-0 ml-0 md:ml-8 flex align-items-center">
                            <div class="surface-300 border-round overflow-hidden w-10rem lg:w-6rem" style="height: 8px">
                                <div class="bg-teal-500 h-full" style="width: 40%"></div>
                            </div>
                            <span class="text-teal-500 ml-3 font-medium">%40</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div> -->
        <!-- <div class="col-12 xl:col-6">
            <div class="card">
                <h5>Sales Overview</h5>
                <Chart type="line" :data="lineData" :options="lineOptions" />
            </div>
            <div class="card">
                <div class="flex align-items-center justify-content-between mb-4">
                    <h5>Notifications</h5>
                    <div>
                        <Button icon="pi pi-ellipsis-v" class="p-button-text p-button-plain p-button-rounded" @click="$refs.menu1.toggle($event)"></Button>
                        <Menu ref="menu1" :popup="true" :model="items"></Menu>
                    </div>
                </div>

                <span class="block text-600 font-medium mb-3">TODAY</span>
                <ul class="p-0 mx-0 mt-0 mb-4 list-none">
                    <li class="flex align-items-center py-2 border-bottom-1 surface-border">
                        <div class="w-3rem h-3rem flex align-items-center justify-content-center bg-blue-100 border-circle mr-3 flex-shrink-0">
                            <i class="pi pi-dollar text-xl text-blue-500"></i>
                        </div>
                        <span class="text-900 line-height-3"
                            >Richard Jones
                            <span class="text-700">has purchased a blue t-shirt for <span class="text-blue-500">79$</span></span>
                        </span>
                    </li>
                    <li class="flex align-items-center py-2">
                        <div class="w-3rem h-3rem flex align-items-center justify-content-center bg-orange-100 border-circle mr-3 flex-shrink-0">
                            <i class="pi pi-download text-xl text-orange-500"></i>
                        </div>
                        <span class="text-700 line-height-3">Your request for withdrawal of <span class="text-blue-500 font-medium">2500$</span> has been initiated.</span>
                    </li>
                </ul>

                <span class="block text-600 font-medium mb-3">YESTERDAY</span>
                <ul class="p-0 m-0 list-none">
                    <li class="flex align-items-center py-2 border-bottom-1 surface-border">
                        <div class="w-3rem h-3rem flex align-items-center justify-content-center bg-blue-100 border-circle mr-3 flex-shrink-0">
                            <i class="pi pi-dollar text-xl text-blue-500"></i>
                        </div>
                        <span class="text-900 line-height-3"
                            >Keyser Wick
                            <span class="text-700">has purchased a black jacket for <span class="text-blue-500">59$</span></span>
                        </span>
                    </li>
                    <li class="flex align-items-center py-2 border-bottom-1 surface-border">
                        <div class="w-3rem h-3rem flex align-items-center justify-content-center bg-pink-100 border-circle mr-3 flex-shrink-0">
                            <i class="pi pi-question text-xl text-pink-500"></i>
                        </div>
                        <span class="text-900 line-height-3"
                            >Jane Davis
                            <span class="text-700">has posted a new questions about your product.</span>
                        </span>
                    </li>
                </ul>
            </div>
            <div
                class="px-4 py-5 shadow-2 flex flex-column md:flex-row md:align-items-center justify-content-between mb-3"
                style="border-radius: 1rem; background: linear-gradient(0deg, rgba(0, 123, 255, 0.5), rgba(0, 123, 255, 0.5)), linear-gradient(92.54deg, #1c80cf 47.88%, #ffffff 100.01%)"
            >
                <div>
                    <div class="text-blue-100 font-medium text-xl mt-2 mb-3">TAKE THE NEXT STEP</div>
                    <div class="text-white font-medium text-5xl">Try PrimeBlocks</div>
                </div>
                <div class="mt-4 mr-auto md:mt-0 md:mr-0">
                    <a href="https://www.primefaces.org/primeblocks-vue" class="p-button font-bold px-5 py-3 p-button-warning p-button-rounded p-button-raised"> Get Started </a>
                </div>
            </div>
        </div> -->
    </div>
</template>
