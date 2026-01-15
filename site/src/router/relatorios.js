const relatoriosRoutes = [
	{
		path: '/relatorios',
		redirect: '/relatorios/comissao',
		children: [
			{
				path: '/relatorios/comissao',
				name: 'relatorioComissao',
				component: () => import('@/views/relatorios/RelatorioComissao.vue')
			}
		]
	}
];

export default relatoriosRoutes;

