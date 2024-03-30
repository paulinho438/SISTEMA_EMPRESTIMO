const contasreceberRoutes = [
	{
		path: '/contasreceber',
		redirect: '/contasreceber',
		children: [
			{
				path: '/contasreceber',
				name: 'contasreceberList',
				component: () => import('@/views/contasreceber/ContasreceberList.vue')
			},
			{
				path: '/contasreceber/:id/edit',
				name: 'contasreceberEdit',
				component: () => import('@/views/contasreceber/ContasreceberForm.vue')
			},
			{
				path: '/contasreceber/add',
				name: 'contasreceberAdd',
				component: () => import('@/views/contasreceber/ContasreceberForm.vue')
			},
			// {
			// 	path: '/contasreceber/:id/view',
			// 	name: 'contasreceberView',
			// 	component: () => import('@/views/contasreceber/ContasreceberView.vue')
			// },
		]
	}
];

export default contasreceberRoutes;
