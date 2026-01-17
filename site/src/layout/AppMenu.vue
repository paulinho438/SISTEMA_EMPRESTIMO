<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import ClientService from '@/service/ClientService';
import { useRouter, useRoute } from 'vue-router';

import store from '@/store';
const confirmPopup = useConfirm();


const router = useRouter();
const route = useRoute();


const clientService = new ClientService();


const toast = useToast();

const changed = ref(false);
const selectedTipo = ref('');

const companiesList = ref([]);


const model = ref([
    {
        label: 'Home',
        permission: 'view_dashboard',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/', permission: 'view_dashboard' }]
    },
    {
        label: '',
        permission: 'view_dashboard',
        icon: 'pi pi-fw pi-briefcase',
        to: '/pages',
        items: [
            {
                label: 'Cadastros',
                permission: 'view_dashboard',
                icon: 'pi pi-fw pi-database',
                items: [
                    {
                        label: 'Permissões',
                        icon: 'pi pi-fw pi-database',
                        to: '/permissoes',
                        permission: 'view_permissions'
                    },
                    {
                        label: 'Categorias',
                        icon: 'pi pi-fw pi-database',
                        to: '/categorias',
                        permission: 'view_categories'
                    },
                    {
                        label: 'Centro de Custo',
                        icon: 'pi pi-fw pi-database',
                        to: '/centro_de_custo',
                        permission: 'view_costcenter'
                    },
                    {
                        label: 'Bancos',
                        icon: 'pi pi-fw pi-database',
                        to: '/bancos',
                        permission: 'view_bancos'
                    },
                    {
                        label: 'Clientes',
                        icon: 'pi pi-fw pi-database',
                        to: '/clientes',
                        permission: 'view_clientes'
                    },
                    {
                        label: 'Fornecedores',
                        icon: 'pi pi-fw pi-database',
                        to: '/fornecedores',
                        permission: 'view_fornecedores'
                    },
                    {
                        label: 'Feriados',
                        icon: 'pi pi-fw pi-database',
                        to: '/feriados',
                        permission: 'edit_empresa'
                    },
                    {
                        label: 'Usuários',
                        icon: 'pi pi-fw pi-database',
                        to: '/usuarios',
                        permission: 'criar_usuarios'
                    }
                ]
            },
            {
                label: 'Movimentações',
                permission: 'view_dashboard',
                icon: 'pi pi-fw pi-arrow-right-arrow-left',
                items: [
                    {
                        label: 'Emprestimos',
                        icon: 'pi pi-fw pi-arrow-right-arrow-left',
                        to: '/emprestimos',
                        permission: 'view_emprestimos'
                    }
                ]
            },
            {
                label: 'Financeiro',
                permission: 'view_dashboard',
                icon: 'pi pi-fw pi-chart-line',
                items: [
                    {
                        label: 'Aprovação',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/aprovacao',
                        permission: 'view_contaspagar'
                    },
                    {
                        label: 'Contas a Pagar',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/contaspagar',
                        permission: 'view_contaspagar'
                    },
                    {
                        label: 'Contas a Receber',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/contasreceber',
                        permission: 'view_contasreceber'
                    },
                    {
                        label: 'Movimentacao Financeira',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/movimentacaofinanceira',
                        permission: 'view_movimentacaofinanceira'
                    },
                    { 
                        label: 'Controle Bcodex', 
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/controlebcodex', 
                        permission: 'view_movimentacaofinanceira' 
                    },
                    {
                        label: 'Fechamento de Caixa',
                        icon: 'pi pi-fw pi-chart-line',
                        to: '/fechamentocaixa',
                        permission: 'view_fechamentocaixa'
                    },
                    {
                        label: 'Relatório Fiscal',
                        icon: 'pi pi-fw pi-file-pdf',
                        to: '/relatorios/fiscal',
                        permission: 'view_dashboard'
                    }
                ]
            },
            {
                label: 'Gestão e Cobrança',
                permission: 'view_dashboard',
                icon: 'pi pi-fw pi-verified',
                items: [
                    {
                        label: 'Finalizados e Renovações',
                        icon: 'pi pi-fw pi-verified',
                        to: '/emprestimosfinalizados',
                        permission: 'view_fechamentocaixa'
                    },
                    { 
                        label: 'Informações da Empresa', 
                        icon: 'pi pi-fw pi-verified', 
                        to: '/empresa', 
                        permission: 'edit_empresa' 
                    },
                    {
                        label: 'Histórico de Uso',
                        icon: 'pi pi-fw pi-verified',
                        to: '/log',
                        permission: 'view_fechamentocaixa'
                    },
                    {
                        label: 'Localização de Usuários',
                        icon: 'pi pi-fw pi-verified',
                        to: '/localizacaousuario',
                        permission: 'view_fechamentocaixa'
                    },
                ]
            }
        ]
    },
    {
        label: 'Gestão de Empresas',
        permission: 'view_criacao_empresas',
        items: [{ label: 'Empresas', icon: 'pi pi-fw pi-building', to: '/empresas', permission: 'view_criacao_empresas' }]
    }
    // {
    //     label: 'UI Componentss',
    //     permission: 'criar_usuarios',
    //     items: [
    //         { label: 'Form Layout', icon: 'pi pi-fw pi-id-card', to: '/uikit/formlayout', permission: 'criar_usuarios' },
    //         { label: 'Input', icon: 'pi pi-fw pi-check-square', to: '/uikit/input', permission: 'criar_usuarios' },
    //         { label: 'Float Label', icon: 'pi pi-fw pi-bookmark', to: '/uikit/floatlabel', permission: 'criar_usuarios' },
    //         { label: 'Invalid State', icon: 'pi pi-fw pi-exclamation-circle', to: '/uikit/invalidstate', permission: 'criar_usuarios' },
    //         { label: 'Button', icon: 'pi pi-fw pi-mobile', to: '/uikit/button', class: 'rotated-icon', permission: 'criar_usuarios' },
    //         { label: 'Table', icon: 'pi pi-fw pi-table', to: '/uikit/table', permission: 'criar_usuarios' },
    //         { label: 'List', icon: 'pi pi-fw pi-list', to: '/uikit/list', permission: 'criar_usuarios' },
    //         { label: 'Tree', icon: 'pi pi-fw pi-share-alt', to: '/uikit/tree', permission: 'criar_usuarios' },
    //         { label: 'Panel', icon: 'pi pi-fw pi-tablet', to: '/uikit/panel', permission: 'criar_usuarios' },
    //         { label: 'Overlay', icon: 'pi pi-fw pi-clone', to: '/uikit/overlay', permission: 'criar_usuarios' },
    //         { label: 'Media', icon: 'pi pi-fw pi-image', to: '/uikit/media', permission: 'criar_usuarios' },
    //         { label: 'Menu', icon: 'pi pi-fw pi-bars', to: '/uikit/menu', preventExact: true, permission: 'criar_usuarios' },
    //         { label: 'Message', icon: 'pi pi-fw pi-comment', to: '/uikit/message', permission: 'criar_usuarios' },
    //         { label: 'File', icon: 'pi pi-fw pi-file', to: '/uikit/file', permission: 'criar_usuarios' },
    //         { label: 'Chart', icon: 'pi pi-fw pi-chart-bar', to: '/uikit/charts', permission: 'criar_usuarios' },
    //         { label: 'Misc', icon: 'pi pi-fw pi-circle', to: '/uikit/misc', permission: 'criar_usuarios' }
    //     ]
    // },
    // {
    //     label: 'Prime Blocks',
    //     permission: 'criar_usuarios',
    //     items: [
    //         { label: 'Free Blocks', icon: 'pi pi-fw pi-eye', to: '/blocks', badge: 'NEW', permission: 'criar_usuarios' },
    //         { label: 'All Blocks', icon: 'pi pi-fw pi-globe', url: 'https://www.primefaces.org/primeblocks-vue', target: '_blank', permission: 'criar_usuarios' }
    //     ]
    // },
    // {
    //     label: 'Utilities',
    //     permission: 'criar_usuarios',
    //     items: [
    //         { label: 'PrimeIcons', icon: 'pi pi-fw pi-prime', to: '/utilities/icons', permission: 'criar_usuarios' },
    //         { label: 'PrimeFlex', icon: 'pi pi-fw pi-desktop', url: 'https://www.primefaces.org/primeflex/', target: '_blank', permission: 'criar_usuarios' }
    //     ]
    // },
    // {
    //     label: 'Pages',
    //     permission: 'criar_usuarios',
    //     icon: 'pi pi-fw pi-briefcase',
    //     to: '/pages',
    //     items: [
    //         {
    //             label: 'Landing',
    //             permission: 'criar_usuarios',
    //             icon: 'pi pi-fw pi-globe',
    //             to: '/landing'
    //         },
    //         {
    //             label: 'Auth',
    //             permission: 'criar_usuarios',
    //             icon: 'pi pi-fw pi-user',
    //             items: [
    //                 {
    //                     label: 'Login',
    //                     permission: 'criar_usuarios',
    //                     icon: 'pi pi-fw pi-sign-in',
    //                     to: '/auth/login'
    //                 },
    //                 {
    //                     label: 'Error',
    //                     permission: 'criar_usuarios',
    //                     icon: 'pi pi-fw pi-times-circle',
    //                     to: '/auth/error'
    //                 },
    //                 {
    //                     label: 'Access Denied',
    //                     permission: 'criar_usuarios',
    //                     icon: 'pi pi-fw pi-lock',
    //                     to: '/auth/access'
    //                 }
    //             ]
    //         },
    //         {
    //             label: 'Crud',
    //             permission: 'criar_usuarios',
    //             icon: 'pi pi-fw pi-pencil',
    //             to: '/pages/crud'
    //         },
    //         {
    //             label: 'Timeline',
    //             permission: 'criar_usuarios',
    //             icon: 'pi pi-fw pi-calendar',
    //             to: '/pages/timeline'
    //         },
    //         {
    //             label: 'Not Found',
    //             permission: 'criar_usuarios',
    //             icon: 'pi pi-fw pi-exclamation-circle',
    //             to: '/pages/notfound'
    //         },
    //         {
    //             label: 'Empty',
    //             permission: 'criar_usuarios',
    //             icon: 'pi pi-fw pi-circle-off',
    //             to: '/pages/empty'
    //         }
    //     ]
    // },
    // {
    //     label: 'Hierarchy',
    //     items: [
    //         {
    //             label: 'Submenu 1',
    //             icon: 'pi pi-fw pi-bookmark',
    //             items: [
    //                 {
    //                     label: 'Submenu 1.1',
    //                     icon: 'pi pi-fw pi-bookmark',
    //                     items: [
    //                         { label: 'Submenu 1.1.1', icon: 'pi pi-fw pi-bookmark' },
    //                         { label: 'Submenu 1.1.2', icon: 'pi pi-fw pi-bookmark' },
    //                         { label: 'Submenu 1.1.3', icon: 'pi pi-fw pi-bookmark' }
    //                     ]
    //                 },
    //                 {
    //                     label: 'Submenu 1.2',
    //                     icon: 'pi pi-fw pi-bookmark',
    //                     items: [{ label: 'Submenu 1.2.1', icon: 'pi pi-fw pi-bookmark' }]
    //                 }
    //             ]
    //         },
    //         {
    //             label: 'Submenu 2',
    //             icon: 'pi pi-fw pi-bookmark',
    //             items: [
    //                 {
    //                     label: 'Submenu 2.1',
    //                     icon: 'pi pi-fw pi-bookmark',
    //                     items: [
    //                         { label: 'Submenu 2.1.1', icon: 'pi pi-fw pi-bookmark' },
    //                         { label: 'Submenu 2.1.2', icon: 'pi pi-fw pi-bookmark' }
    //                     ]
    //                 },
    //                 {
    //                     label: 'Submenu 2.2',
    //                     icon: 'pi pi-fw pi-bookmark',
    //                     items: [{ label: 'Submenu 2.2.1', icon: 'pi pi-fw pi-bookmark' }]
    //                 }
    //             ]
    //         }
    //     ]
    // },
    // {
    //     label: 'Get Started',
    //     items: [
    //         {
    //             label: 'Documentation',
    //             icon: 'pi pi-fw pi-question',
    //             to: '/documentation'
    //         },
    //         {
    //             label: 'Figma',
    //             url: 'https://www.dropbox.com/scl/fi/bhfwymnk8wu0g5530ceas/sakai-2023.fig?rlkey=u0c8n6xgn44db9t4zkd1brr3l&dl=0',
    //             icon: 'pi pi-fw pi-pencil',
    //             target: '_blank'
    //         },
    //         {
    //             label: 'View Source',
    //             icon: 'pi pi-fw pi-search',
    //             url: 'https://github.com/primefaces/sakai-vue',
    //             target: '_blank'
    //         },
    //         {
    //             label: 'Nuxt Version',
    //             url: 'https://github.com/primefaces/sakai-nuxt',
    //             icon: 'pi pi-fw pi-star'
    //         }
    //     ]
    // }
]);

const confirm = (event) => {
    confirmPopup.require({
        target: event.target,
        message: 'Tem certeza que deseja cobrar todos os clientes?',
        icon: 'pi pi-exclamation-triangle',
		acceptLabel: 'Sim',
        rejectLabel: 'Não',
        accept: () => {
			cobrarTodosClientes();
        },
        reject: () => {
            toast.add({ severity: 'info', summary: 'Cancelar', detail: 'Rotina não iniciada!', life: 3000 });
        }
    });
};

const cobrarTodosClientes = async (event) => {

	try {
		
		await clientService.cobrarClientes();

		toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Clientes cobrados com sucesso!', life: 3000 });

	} catch (e) {
		toast.add({ severity: 'error', summary: 'Erro', detail: 'Rotina já foi iniciada!', life: 3000 });
	}

};

const searchCostcenter = async (event) => {
    try {
        console.log(store?.getters?.companies);
        console.log(event.query.toLowerCase());

        companiesList.value = store?.getters?.companies.filter((company) =>
            company.company.toLowerCase().includes(event.query.toLowerCase())
        );

    } catch (e) {
        console.log(e);
    }
}

const changeCompany = async () => {
    const selected = selectedTipo.value;

    if (selected?.id) {
        store.commit('setCompany', selected);

        const res = store.getters?.allPermissions.filter(item => item.company_id === selected.id);
        store.commit('setPermissions', res[0]?.permissions ?? []);

        window.location.reload();
    } else {
        toast.add({
            severity: 'error',
            summary: 'Atenção',
            detail: 'Selecione uma empresa!',
            life: 3000
        });
    }
};


</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
    <ConfirmPopup></ConfirmPopup>
    <button ref="popup" @click="confirm($event)" class="p-link hidden-on-small">
        <i class="pi pi-whatsapp" style="margin-right: 10px"></i>
        <span>Cobrar Todos Clientes</span>
    </button>
    <button v-if="store?.getters?.companies.length > 1" @click="() => {changed = true}" class="p-link hidden-on-small">
        <i class="pi pi-link" style="margin-right: 10px"></i>
        <span>Alterar Empresa</span>
    </button>

    <Dialog header="Selecione a Empresa" modal class="w-8 mx-8" v-model:visible="changed" :closable="true">
        <div class="grid flex align-content-center flex-wrap">
            <div class="col-12 flex align-content-center flex-wrap md:col-2 lg:col-1">
                <label>Empresa:</label>
            </div>
            <div class="col-12 flex align-content-center flex-wrap sm:col-12 md:col-8">
                <AutoComplete
                    :modelValue="selectedTipo"
                    :dropdown="true"
                    v-model="selectedTipo"
                    :suggestions="companiesList"
                    placeholder="Informe o nome da Empresa"
                    class="w-full"
                    inputClass="w-full p-inputtext-sm"
                    @complete="searchCostcenter($event)"
                    optionLabel="company"
                />
            </div>
            <div class="col-12 flex align-content-center flex-wrap md:col-12 lg:col-3">
                <Button icon="pi pi-check" label="Entrar" class="p-button-sm p-button-sucess w-full" @click.prevent="changeCompany"  />
            </div>
        </div>
    </Dialog>
</template>

<style lang="scss" scoped>
.hidden-on-small {
    margin-top: 17px;
    display: none;
}

@media (max-width: 991px) {
    .hidden-on-small {
        display: block;
    }
}
</style>
