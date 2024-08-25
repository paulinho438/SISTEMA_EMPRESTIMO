const fechamentocaixaRoutes = [
	{
		path: '/fechamentocaixa',
		redirect: '/fechamentocaixa',
		children: [
			{
				path: '/fechamentocaixa',
				name: 'fechamentocaixaList',
				component: () => import('@/views/fechamentocaixa/FechamentoCaixaList.vue')
			}
		]
	}
];

export default fechamentocaixaRoutes;
