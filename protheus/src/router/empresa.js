const empresaRoutes = [
	{
		path: '/empresa',
		redirect: '/empresa',
		children: [
			{
				path: '/empresa',
				name: 'empresaList',
				component: () => import('@/views/empresa/EmpresaForm.vue')
			},
			// {
			// 	path: '/aprovacao/:id/view',
			// 	name: 'aprovacaoView',
			// 	component: () => import('@/views/aprovacao/AprovacaoView.vue')
			// },
		]
	}
];

export default empresaRoutes;
