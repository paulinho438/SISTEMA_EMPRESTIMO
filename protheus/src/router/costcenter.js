const costcenterRoutes = [
	{
		path: '/centro_de_custo',
		redirect: '/centro_de_custo',
		children: [
			{
				path: '/centro_de_custo',
				name: 'costcenterList',
				component: () => import('@/views/costcenter/CostcenterList.vue')
			},
			{
				path: '/centro_de_custo/:id/edit',
				name: 'costcenterEdit',
				component: () => import('@/views/costcenter/CostcenterForm.vue')
			},
			{
				path: '/centro_de_custo/add',
				name: 'costcenterAdd',
				component: () => import('@/views/costcenter/CostcenterForm.vue')
			}
		]
	}
];

export default costcenterRoutes;
