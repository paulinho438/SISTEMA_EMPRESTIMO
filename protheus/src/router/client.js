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
				path: '/clientes/:id/edit',
				name: 'clientEdit',
				component: () => import('@/views/clientes/ClientForm.vue')
			},
			{
				path: '/clientes/add',
				name: 'clientAdd',
				component: () => import('@/views/clientes/ClientForm.vue')
			}
		]
	}
];

export default clientRoutes;
