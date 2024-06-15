const fechamentocaixaRoutes = [
	{
		path: '/fechamentocaixa',
		redirect: '/fechamentocaixa',
		children: [
			{
				path: '/fechamentocaixa',
				name: 'fechamentocaixaList',
				component: () => import('@/views/fechamentocaixa/FechamentoCaixaList.vue')
			},
			{
				path: '/fechamentocaixa/:id/edit',
				name: 'fechamentocaixaEdit',
				component: () => import('@/views/fechamentocaixa/FeriadosForm.vue')
			},
			{
				path: '/fechamentocaixa/add',
				name: 'fechamentocaixaAdd',
				component: () => import('@/views/fechamentocaixa/FeriadosForm.vue')
			}
		]
	}
];

export default fechamentocaixaRoutes;
