const emprestimosRoutes = [
	{
		path: '/emprestimos',
		redirect: '/emprestimos',
		children: [
			{
				path: '/emprestimos',
				name: 'emprestimosList',
				component: () => import('@/views/emprestimos/EmprestimosList.vue')
			},
			{
				path: '/emprestimos/:id/edit',
				name: 'emprestimosEdit',
				component: () => import('@/views/emprestimos/EmprestimosForm.vue')
			},
			{
				path: '/emprestimos/add',
				name: 'emprestimosAdd',
				component: () => import('@/views/emprestimos/EmprestimosForm.vue')
			},
			{
				path: '/emprestimos/:id/view',
				name: 'emprestimosView',
				component: () => import('@/views/emprestimos/EmprestimosView.vue')
			},
			{
				path: '/emprestimos/:id/aprovacao',
				name: 'emprestimosAprovacao',
				component: () => import('@/views/emprestimos/EmprestimosAprovacao.vue')
			},
			{
				path: '/emprestimos/:id/aprovacao_contaspagar',
				name: 'emprestimosAprovacaoBoleto',
				component: () => import('@/views/contaspagar/ContaspagarAprovacao.vue')
			},
			{
				path: '/emprestimos/simulacao',
				name: 'emprestimosSimulacao',
				component: () => import('@/views/emprestimos/LoanSimulator.vue')
			},
		]
	}
];

export default emprestimosRoutes;
