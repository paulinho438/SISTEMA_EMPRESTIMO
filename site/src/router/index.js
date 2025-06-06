import { createRouter, createWebHashHistory } from 'vue-router';
import AppLayout from '@/layout/AppLayout.vue';

import store from '@/store';

import PermissionsRoutes from './permissions.js';
import CategoriesRoutes from './categories.js';
import CostcenterRoutes from './costcenter.js';
import ClientRoutes from './client.js';
import FornecedorRoutes from './fornecedor.js';
import BancosRoutes from './bancos.js';
import EmprestimosRoutes from './emprestimos.js';
import ContaspagarRoutes from './contaspagar.js';
import ContasreceberRoutes from './contasreceber.js';
import MovimentacaofinanceiraRoutes from './movimentacaofinanceira.js';
import ControleBcodexRoutes from './controlebcodex.js';
import AprovacaoRoutes from './aprovacao.js';
import EmpresaRoutes from './empresa.js';
import UsuarioRoutes from './usuarios.js';
import FeriadosRoutes from './feriados.js';
import FechamentocaixaRoutes from './fechamentocaixa.js';
import EmprestimosfinalizadosRoutes from './emprestimosfinalizados.js';
import EmpresasRoutes from './empresas.js';
import LogRoutes from './log.js';
import localizacaousuarioRoutes from './localizacaousuario.js';

const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/',
            component: AppLayout,
            children: [
                {
                    path: '/',
                    name: 'dashboard',
                    component: () => import('@/views/Dashboard.vue')
                },
                ...PermissionsRoutes,
                ...CategoriesRoutes,
                ...CostcenterRoutes,
                ...BancosRoutes,
                ...ClientRoutes,
                ...EmprestimosRoutes,
                ...FornecedorRoutes,
                ...ContaspagarRoutes,
                ...ContasreceberRoutes,
                ...MovimentacaofinanceiraRoutes,
                ...AprovacaoRoutes,
                ...EmpresaRoutes,
                ...UsuarioRoutes,
                ...FeriadosRoutes,
                ...FechamentocaixaRoutes,
                ...EmprestimosfinalizadosRoutes,
                ...EmpresasRoutes,
                ...LogRoutes,
                ...localizacaousuarioRoutes,
                ...ControleBcodexRoutes,
                {
                    path: '/uikit/formlayout',
                    name: 'formlayout',
                    component: () => import('@/views/uikit/FormLayout.vue')
                },
                {
                    path: '/uikit/input',
                    name: 'input',
                    component: () => import('@/views/uikit/Input.vue')
                },
                {
                    path: '/uikit/floatlabel',
                    name: 'floatlabel',
                    component: () => import('@/views/uikit/FloatLabel.vue')
                },
                {
                    path: '/uikit/invalidstate',
                    name: 'invalidstate',
                    component: () => import('@/views/uikit/InvalidState.vue')
                },
                {
                    path: '/uikit/button',
                    name: 'button',
                    component: () => import('@/views/uikit/Button.vue')
                },
                {
                    path: '/uikit/table',
                    name: 'table',
                    component: () => import('@/views/uikit/Table.vue')
                },
                {
                    path: '/uikit/list',
                    name: 'list',
                    component: () => import('@/views/uikit/List.vue')
                },
                {
                    path: '/uikit/tree',
                    name: 'tree',
                    component: () => import('@/views/uikit/Tree.vue')
                },
                {
                    path: '/uikit/panel',
                    name: 'panel',
                    component: () => import('@/views/uikit/Panels.vue')
                },

                {
                    path: '/uikit/overlay',
                    name: 'overlay',
                    component: () => import('@/views/uikit/Overlay.vue')
                },
                {
                    path: '/uikit/media',
                    name: 'media',
                    component: () => import('@/views/uikit/Media.vue')
                },
                {
                    path: '/uikit/menu',
                    component: () => import('@/views/uikit/Menu.vue'),
                    children: [
                        {
                            path: '/uikit/menu',
                            component: () => import('@/views/uikit/menu/PersonalDemo.vue')
                        },
                        {
                            path: '/uikit/menu/seat',
                            component: () => import('@/views/uikit/menu/SeatDemo.vue')
                        },
                        {
                            path: '/uikit/menu/payment',
                            component: () => import('@/views/uikit/menu/PaymentDemo.vue')
                        },
                        {
                            path: '/uikit/menu/confirmation',
                            component: () => import('@/views/uikit/menu/ConfirmationDemo.vue')
                        }
                    ]
                },
                {
                    path: '/uikit/message',
                    name: 'message',
                    component: () => import('@/views/uikit/Messages.vue')
                },
                {
                    path: '/uikit/file',
                    name: 'file',
                    component: () => import('@/views/uikit/File.vue')
                },
                {
                    path: '/uikit/charts',
                    name: 'charts',
                    component: () => import('@/views/uikit/Chart.vue')
                },
                {
                    path: '/uikit/misc',
                    name: 'misc',
                    component: () => import('@/views/uikit/Misc.vue')
                },
                {
                    path: '/blocks',
                    name: 'blocks',
                    component: () => import('@/views/utilities/Blocks.vue')
                },
                {
                    path: '/utilities/icons',
                    name: 'icons',
                    component: () => import('@/views/utilities/Icons.vue')
                },
                {
                    path: '/pages/timeline',
                    name: 'timeline',
                    component: () => import('@/views/pages/Timeline.vue')
                },
                {
                    path: '/pages/empty',
                    name: 'empty',
                    component: () => import('@/views/pages/Empty.vue')
                },
                {
                    path: '/pages/crud',
                    name: 'crud',
                    component: () => import('@/views/pages/Crud.vue')
                },
                {
                    path: '/documentation',
                    name: 'documentation',
                    component: () => import('@/views/utilities/Documentation.vue')
                }
            ]
        },
        {
            path: '/parcela/:id_pedido',
            name: 'landing',
            component: () => import('@/views/pages/Landing.vue')
        },
        // {
        //     path: '/landing',
        //     name: 'landing',
        //     component: () => import('@/views/pages/Landing.vue')
        // },
        {
            path: '/pages/notfound',
            name: 'notfound',
            component: () => import('@/views/pages/NotFound.vue')
        },

        {
            path: '/auth/login',
            name: 'login',
            component: () => import('@/views/pages/auth/Login.vue')
        },
        {
            path: '/auth/access',
            name: 'accessDenied',
            component: () => import('@/views/pages/auth/Access.vue')
        },
        {
            path: '/auth/error',
            name: 'error',
            component: () => import('@/views/pages/auth/Error.vue')
        }
    ]
});

// route guard
router.beforeEach((to, from, next) => {
	// State on store
	const isAuthenticated = store.getters.isAutenticated;

	// Token armazenado no local storage
	const token = localStorage.getItem('app.emp.token');

	// Usuário não Autenticado
	if (!token && !isAuthenticated) {
		if (to.name !== 'login' && to.name !== 'forgot' && to.name !== 'landing') next({ name: 'login' });
	}

	// Segue rota de destino
	next();
});

// router.afterEach((to, from, next) => {});

export default router;
