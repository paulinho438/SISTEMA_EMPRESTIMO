const FornecedorRoutes = [
	{
		path: '/fornecedores',
		redirect: '/fornecedores',
		children: [
			{
				path: '/fornecedores',
				name: 'fornecedorList',
				component: () => import('@/views/fornecedores/FornecedorList.vue')
			},
			{
				path: '/fornecedores/:id/edit',
				name: 'fornecedorEdit',
				component: () => import('@/views/fornecedores/FornecedorForm.vue')
			},
			{
				path: '/fornecedores/add',
				name: 'fornecedorAdd',
				component: () => import('@/views/fornecedores/FornecedorForm.vue')
			}
		]
	}
];

export default FornecedorRoutes;
