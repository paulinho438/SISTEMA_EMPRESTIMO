const xgateRoutes = [
	{
		path: '/xgate',
		redirect: '/xgate/teste',
		children: [
			{
				path: '/xgate/teste',
				name: 'xgateTest',
				component: () => import('@/views/xgate/XGateTest.vue')
			}
		]
	}
];

export default xgateRoutes;
