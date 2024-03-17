const bancosRoutes = [
	{
		path: '/bancos',
		redirect: '/bancos',
		children: [
			{
				path: '/bancos',
				name: 'bancosList',
				component: () => import('@/views/bancos/BancosList.vue')
			},
			{
				path: '/bancos/:id/edit',
				name: 'bancosEdit',
				component: () => import('@/views/bancos/BancosForm.vue')
			},
			{
				path: '/bancos/add',
				name: 'bancosAdd',
				component: () => import('@/views/bancos/BancosForm.vue')
			}
		]
	}
];

export default bancosRoutes;
