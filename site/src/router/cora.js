const coraRoutes = [
	{
		path: '/cora',
		redirect: '/cora/teste',
		children: [
			{
				path: '/cora/teste',
				name: 'coraTest',
				component: () => import('@/views/cora/CoraTest.vue'),
				meta: { title: 'Teste Cora' }
			}
		]
	}
];

export default coraRoutes;

