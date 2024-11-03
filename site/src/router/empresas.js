const empresasRoutes = [
	{
		path: '/empresas',
		redirect: '/empresas',
		children: [
			{
				path: '/empresas',
				name: 'empresasList',
				component: () => import('@/views/empresas/EmpresasList.vue')
			},
			{
				path: '/empresas/:id/edit',
				name: 'empresasEdit',
				component: () => import('@/views/empresas/EmpresasForm.vue')
			},
			{
				path: '/empresas/add',
				name: 'empresasAdd',
				component: () => import('@/views/empresas/EmpresasForm.vue')
			}
		]
	}
];

export default empresasRoutes;
