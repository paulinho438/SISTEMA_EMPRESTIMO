const velanaRoutes = [
	{
		path: '/velana',
		redirect: '/velana/teste',
		children: [
			{
				path: '/velana/teste',
				name: 'velanaTest',
				component: () => import('@/views/velana/VelanaTest.vue')
			}
		]
	}
];

export default velanaRoutes;

