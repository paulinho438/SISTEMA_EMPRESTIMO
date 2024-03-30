const contaspagarRoutes = [
	{
		path: '/contaspagar',
		redirect: '/contaspagar',
		children: [
			{
				path: '/contaspagar',
				name: 'contaspagarList',
				component: () => import('@/views/contaspagar/ContaspagarList.vue')
			},
			{
				path: '/contaspagar/:id/edit',
				name: 'contaspagarEdit',
				component: () => import('@/views/contaspagar/ContaspagarForm.vue')
			},
			{
				path: '/contaspagar/add',
				name: 'contaspagarAdd',
				component: () => import('@/views/contaspagar/ContaspagarForm.vue')
			},
			// {
			// 	path: '/contaspagar/:id/view',
			// 	name: 'contaspagarView',
			// 	component: () => import('@/views/contaspagar/ContaspagarView.vue')
			// },
		]
	}
];

export default contaspagarRoutes;
