const controlebcodexRoutes = [
	{
		path: '/controlebcodex',
		redirect: '/controlebcodex',
		children: [
			{
				path: '/controlebcodex',
				name: 'controlebcodexList',
				component: () => import('@/views/controlebcodex/ControleBcodexList.vue')
			}
			// {
			// 	path: '/movimentacaofinanceira/:id/view',
			// 	name: 'movimentacaofinanceiraView',
			// 	component: () => import('@/views/movimentacaofinanceira/MovimentacaofinanceiraView.vue')
			// },
		]
	}
];

export default controlebcodexRoutes;
