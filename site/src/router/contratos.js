const contratosRoutes = [
	{
		path: '/contratos',
		children: [
			{
				path: '/contratos',
				name: 'contratosList',
				component: () => import('@/views/contratos/ContratosList.vue')
			}
		]
	}
];

export default contratosRoutes;
