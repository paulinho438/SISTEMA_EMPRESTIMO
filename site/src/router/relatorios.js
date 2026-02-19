const relatoriosRoutes = [
	{
		path: '/relatorios',
		redirect: '/relatorios/sumario-contratos-ativos',
		children: [
			{
				path: '/relatorios/sumario-contratos-ativos',
				name: 'sumarioContratosAtivos',
				component: () => import('@/views/relatorios/SumarioContratosAtivos.vue')
			},
			{
				path: '/relatorios/comissao',
				name: 'relatorioComissao',
				component: () => import('@/views/relatorios/RelatorioComissao.vue')
			},
			{
				path: '/relatorios/fiscal',
				name: 'relatorioFiscal',
				component: () => import('@/views/relatorios/RelatorioFiscal.vue')
			},
			{
				path: '/relatorios/lucro-real',
				name: 'relatorioLucroReal',
				component: () => import('@/views/relatorios/RelatorioLucroReal.vue')
			}
		]
	}
];

export default relatoriosRoutes;

