const aprovacaoRoutes = [
	{
		path: '/aprovacao',
		redirect: '/aprovacao',
		children: [
			{
				path: '/aprovacao',
				name: 'aprovacaoList',
				component: () => import('@/views/aprovacao/AprovacaoList.vue')
			},
			// {
			// 	path: '/aprovacao/:id/view',
			// 	name: 'aprovacaoView',
			// 	component: () => import('@/views/aprovacao/AprovacaoView.vue')
			// },
		]
	}
];

export default aprovacaoRoutes;
