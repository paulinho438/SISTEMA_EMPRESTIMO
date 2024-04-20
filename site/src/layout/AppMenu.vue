<script setup>
import { ref } from 'vue';

import AppMenuItem from './AppMenuItem.vue';

const model = ref([
    {
        label: 'Home',
        permission: 'view_dashboard',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/', permission: 'view_dashboard' }]
    },
    {
        label: '',
        permission: 'criar_usuarios',
        icon: 'pi pi-fw pi-briefcase',
        to: '/pages',
        items: [
            {
                label: 'Cadastros',
                permission: 'view_menu_cadastro',
                icon: 'pi pi-fw pi-database',
                items: [
                    
                    { 
                        label: 'Permissões', 
                        icon: 'pi pi-fw pi-lock', 
                        to: '/permissoes', 
                        permission: 'view_permissions' 
                    },
                    { 
                        label: 'Categorias', 
                        icon: 'pi pi-fw pi-tags', 
                        to: '/categorias', 
                        permission: 'view_categories' 
                    },
                    { 
                        label: 'Centro de Custo', 
                        icon: 'pi pi-fw pi-chart-line', 
                        to: '/centro_de_custo', 
                        permission: 'view_costcenter' 
                    },
                    { 
                        label: 'Bancos', 
                        icon: 'pi pi-fw pi-wallet', 
                        to: '/bancos', 
                        permission: 'view_bancos' 
                    },
                    { 
                        label: 'Clientes', 
                        icon: 'pi pi-fw pi-star', 
                        to: '/clientes', 
                        permission: 'view_clientes' 
                    },
                    { 
                        label: 'Fornecedores', 
                        icon: 'pi pi-fw pi-star-fill', 
                        to: '/fornecedores', 
                        permission: 'view_fornecedores' 
                    },
                   
                ]
            },
            {
                label: 'Movimentações',
                permission: 'view_menu_cadastro',
                icon: 'pi pi-fw pi-arrow-right-arrow-left',
                items: [
                    
                    { 
                        label: 'Emprestimos', 
                        icon: 'pi pi-fw pi-dollar', 
                        to: '/emprestimos', 
                        permission: 'view_permissions' 
                    },
                   
                   
                ]
            },
            {
                label: 'Financeiro',
                permission: 'view_menu_cadastro',
                icon: 'pi pi-fw pi-chart-line',
                items: [
                    { 
                        label: 'Aprovação', 
                        icon: 'pi pi-fw pi-verified', 
                        to: '/aprovacao', 
                        permission: 'view_contaspagar' 
                    },
                    { 
                        label: 'Contas a Pagar', 
                        icon: 'pi pi-fw pi-angle-double-right', 
                        to: '/contaspagar', 
                        permission: 'view_contaspagar' 
                    },
                    { 
                        label: 'Contas a Receber', 
                        icon: 'pi pi-fw pi-angle-double-left', 
                        to: '/contasreceber', 
                        permission: 'view_contasreceber' 
                    },
                    { 
                        label: 'Movimentacao Financeira', 
                        icon: 'pi pi-fw pi-globe', 
                        to: '/movimentacaofinanceira', 
                        permission: 'view_movimentacaofinanceira' 
                    },
                   
                ]
            },
  
        ]
    },
    {
        label: 'Empresa',
        permission: 'edit_empresa',
        items: [{ label: 'Informações da Empresa', icon: 'pi pi-fw pi-building', to: '/empresa', permission: 'edit_empresa' }]
    },
    {
        label: 'UI Components',
        permission: 'criar_usuarios',
        items: [
            { label: 'Form Layout', icon: 'pi pi-fw pi-id-card', to: '/uikit/formlayout', permission: 'criar_usuarios' },
            { label: 'Input', icon: 'pi pi-fw pi-check-square', to: '/uikit/input', permission: 'criar_usuarios' },
            { label: 'Float Label', icon: 'pi pi-fw pi-bookmark', to: '/uikit/floatlabel', permission: 'criar_usuarios' },
            { label: 'Invalid State', icon: 'pi pi-fw pi-exclamation-circle', to: '/uikit/invalidstate', permission: 'criar_usuarios' },
            { label: 'Button', icon: 'pi pi-fw pi-mobile', to: '/uikit/button', class: 'rotated-icon', permission: 'criar_usuarios' },
            { label: 'Table', icon: 'pi pi-fw pi-table', to: '/uikit/table', permission: 'criar_usuarios' },
            { label: 'List', icon: 'pi pi-fw pi-list', to: '/uikit/list', permission: 'criar_usuarios' },
            { label: 'Tree', icon: 'pi pi-fw pi-share-alt', to: '/uikit/tree', permission: 'criar_usuarios' },
            { label: 'Panel', icon: 'pi pi-fw pi-tablet', to: '/uikit/panel', permission: 'criar_usuarios' },
            { label: 'Overlay', icon: 'pi pi-fw pi-clone', to: '/uikit/overlay', permission: 'criar_usuarios' },
            { label: 'Media', icon: 'pi pi-fw pi-image', to: '/uikit/media', permission: 'criar_usuarios' },
            { label: 'Menu', icon: 'pi pi-fw pi-bars', to: '/uikit/menu', preventExact: true, permission: 'criar_usuarios' },
            { label: 'Message', icon: 'pi pi-fw pi-comment', to: '/uikit/message', permission: 'criar_usuarios' },
            { label: 'File', icon: 'pi pi-fw pi-file', to: '/uikit/file', permission: 'criar_usuarios' },
            { label: 'Chart', icon: 'pi pi-fw pi-chart-bar', to: '/uikit/charts', permission: 'criar_usuarios' },
            { label: 'Misc', icon: 'pi pi-fw pi-circle', to: '/uikit/misc', permission: 'criar_usuarios' }
        ]
    },
    {
        label: 'Prime Blocks',
        permission: 'criar_usuarios',
        items: [
            { label: 'Free Blocks', icon: 'pi pi-fw pi-eye', to: '/blocks', badge: 'NEW', permission: 'criar_usuarios' },
            { label: 'All Blocks', icon: 'pi pi-fw pi-globe', url: 'https://www.primefaces.org/primeblocks-vue', target: '_blank', permission: 'criar_usuarios' }
        ]
    },
    {
        label: 'Utilities',
        permission: 'criar_usuarios',
        items: [
            { label: 'PrimeIcons', icon: 'pi pi-fw pi-prime', to: '/utilities/icons', permission: 'criar_usuarios' },
            { label: 'PrimeFlex', icon: 'pi pi-fw pi-desktop', url: 'https://www.primefaces.org/primeflex/', target: '_blank', permission: 'criar_usuarios' }
        ]
    },
    {
        label: 'Pages',
        permission: 'criar_usuarios',
        icon: 'pi pi-fw pi-briefcase',
        to: '/pages',
        items: [
            {
                label: 'Landing',
                permission: 'criar_usuarios',
                icon: 'pi pi-fw pi-globe',
                to: '/landing'
            },
            {
                label: 'Auth',
                permission: 'criar_usuarios',
                icon: 'pi pi-fw pi-user',
                items: [
                    {
                        label: 'Login',
                        permission: 'criar_usuarios',
                        icon: 'pi pi-fw pi-sign-in',
                        to: '/auth/login'
                    },
                    {
                        label: 'Error',
                        permission: 'criar_usuarios',
                        icon: 'pi pi-fw pi-times-circle',
                        to: '/auth/error'
                    },
                    {
                        label: 'Access Denied',
                        permission: 'criar_usuarios',
                        icon: 'pi pi-fw pi-lock',
                        to: '/auth/access'
                    }
                ]
            },
            {
                label: 'Crud',
                permission: 'criar_usuarios',
                icon: 'pi pi-fw pi-pencil',
                to: '/pages/crud'
            },
            {
                label: 'Timeline',
                permission: 'criar_usuarios',
                icon: 'pi pi-fw pi-calendar',
                to: '/pages/timeline'
            },
            {
                label: 'Not Found',
                permission: 'criar_usuarios',
                icon: 'pi pi-fw pi-exclamation-circle',
                to: '/pages/notfound'
            },
            {
                label: 'Empty',
                permission: 'criar_usuarios',
                icon: 'pi pi-fw pi-circle-off',
                to: '/pages/empty'
            }
        ]
    },
    {
        label: 'Hierarchy',
        items: [
            {
                label: 'Submenu 1',
                icon: 'pi pi-fw pi-bookmark',
                items: [
                    {
                        label: 'Submenu 1.1',
                        icon: 'pi pi-fw pi-bookmark',
                        items: [
                            { label: 'Submenu 1.1.1', icon: 'pi pi-fw pi-bookmark' },
                            { label: 'Submenu 1.1.2', icon: 'pi pi-fw pi-bookmark' },
                            { label: 'Submenu 1.1.3', icon: 'pi pi-fw pi-bookmark' }
                        ]
                    },
                    {
                        label: 'Submenu 1.2',
                        icon: 'pi pi-fw pi-bookmark',
                        items: [{ label: 'Submenu 1.2.1', icon: 'pi pi-fw pi-bookmark' }]
                    }
                ]
            },
            {
                label: 'Submenu 2',
                icon: 'pi pi-fw pi-bookmark',
                items: [
                    {
                        label: 'Submenu 2.1',
                        icon: 'pi pi-fw pi-bookmark',
                        items: [
                            { label: 'Submenu 2.1.1', icon: 'pi pi-fw pi-bookmark' },
                            { label: 'Submenu 2.1.2', icon: 'pi pi-fw pi-bookmark' }
                        ]
                    },
                    {
                        label: 'Submenu 2.2',
                        icon: 'pi pi-fw pi-bookmark',
                        items: [{ label: 'Submenu 2.2.1', icon: 'pi pi-fw pi-bookmark' }]
                    }
                ]
            }
        ]
    },
    {
        label: 'Get Started',
        items: [
            {
                label: 'Documentation',
                icon: 'pi pi-fw pi-question',
                to: '/documentation'
            },
            {
                label: 'Figma',
                url: 'https://www.dropbox.com/scl/fi/bhfwymnk8wu0g5530ceas/sakai-2023.fig?rlkey=u0c8n6xgn44db9t4zkd1brr3l&dl=0',
                icon: 'pi pi-fw pi-pencil',
                target: '_blank'
            },
            {
                label: 'View Source',
                icon: 'pi pi-fw pi-search',
                url: 'https://github.com/primefaces/sakai-vue',
                target: '_blank'
            },
            {
                label: 'Nuxt Version',
                url: 'https://github.com/primefaces/sakai-nuxt',
                icon: 'pi pi-fw pi-star'
            }
        ]
    }
]);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
        <li>
            <a href="https://www.primefaces.org/primeblocks-vue/#/" target="_blank">
                <img src="/layout/images/banner-primeblocks.png" alt="Prime Blocks" class="w-full mt-3" />
            </a>
        </li>
    </ul>
</template>

<style lang="scss" scoped></style>
