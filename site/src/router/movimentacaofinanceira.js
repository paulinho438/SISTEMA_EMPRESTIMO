const movimentacaofinanceiraRoutes = [
	{
		path: '/movimentacaofinanceira',
		redirect: '/movimentacaofinanceira',
		children: [
			{
				path: '/movimentacaofinanceira',
				name: 'movimentacaofinanceiraList',
				component: () => import('@/views/movimentacaofinanceira/MovimentacaofinanceiraList.vue')
			},
			{
				path: '/movimentacaofinanceira/:id/edit',
				name: 'movimentacaofinanceiraEdit',
				component: () => import('@/views/movimentacaofinanceira/MovimentacaofinanceiraForm.vue')
			},
			{
				path: '/movimentacaofinanceira/add',
				name: 'movimentacaofinanceiraAdd',
				component: () => import('@/views/movimentacaofinanceira/MovimentacaofinanceiraForm.vue')
			},
			// {
			// 	path: '/movimentacaofinanceira/:id/view',
			// 	name: 'movimentacaofinanceiraView',
			// 	component: () => import('@/views/movimentacaofinanceira/MovimentacaofinanceiraView.vue')
			// },
		]
	}
];

export default movimentacaofinanceiraRoutes;
