const clientRoutes = [
	{
		path: '/clientes',
		redirect: '/clientes',
		children: [
			{
				path: '/clientes',
				name: 'clientList',
				component: () => import('@/views/clientes/ClientList.vue')
			},
			{
				path: '/clientes/add',
				name: 'clientAdd',
				component: () => import('@/views/clientes/ClientForm.vue')
			},
			{
				path: '/clientes/:id/edit',
				name: 'clientEdit',
				component: () => import('@/views/clientes/ClientForm.vue')
			},
			{
				path: '/clientes/pj',
				name: 'pjList',
				component: () => import('@/views/clientes/PessoaJuridicaList.vue')
			},
			{
				path: '/clientes/pj/add',
				name: 'pjAdd',
				component: () => import('@/views/clientes/PessoaJuridicaForm.vue')
			},
			{
				path: '/clientes/pj/:id/edit',
				name: 'pjEdit',
				component: () => import('@/views/clientes/PessoaJuridicaForm.vue')
			},
			{
				path: '/clientes/pf',
				name: 'pfList',
				component: () => import('@/views/clientes/PessoaFisicaList.vue')
			},
			{
				path: '/clientes/pf/add',
				name: 'pfAdd',
				component: () => import('@/views/clientes/PessoaFisicaForm.vue')
			},
			{
				path: '/clientes/pf/:id/edit',
				name: 'pfEdit',
				component: () => import('@/views/clientes/PessoaFisicaForm.vue')
			}
		]
	}
];

export default clientRoutes;
