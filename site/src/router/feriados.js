const feriadosRoutes = [
	{
		path: '/feriados',
		redirect: '/feriados',
		children: [
			{
				path: '/feriados',
				name: 'feriadosList',
				component: () => import('@/views/feriados/FeriadosList.vue')
			},
			{
				path: '/feriados/:id/edit',
				name: 'feriadosEdit',
				component: () => import('@/views/feriados/FeriadosForm.vue')
			},
			{
				path: '/feriados/add',
				name: 'feriadosAdd',
				component: () => import('@/views/feriados/FeriadosForm.vue')
			}
		]
	}
];

export default feriadosRoutes;
