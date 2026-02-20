const contratosRoutes = [
	{
		path: '/contratos',
		children: [
			{
				path: '/contratos',
				name: 'contratosList',
				component: () => import('@/views/contratos/ContratosList.vue')
			},
			{
				path: '/contratos/:id/assinatura',
				name: 'contratoAssinaturaReview',
				component: () => import('@/views/contratos/ContratoAssinaturaReview.vue')
			}
		]
	}
];

export default contratosRoutes;
