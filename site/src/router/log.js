const logRoutes = [
	{
		path: '/log',
		redirect: '/log',
		children: [
			{
				path: '/log',
				name: 'logList',
				component: () => import('@/views/log/LogList.vue')
			},
		]
	}
];

export default logRoutes;
