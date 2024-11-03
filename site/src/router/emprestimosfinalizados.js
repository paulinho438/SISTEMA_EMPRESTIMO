const emprestimosfinalizadosRoutes = [
	{
		path: '/emprestimosfinalizados',
		redirect: '/emprestimosfinalizados',
		children: [
			{
				path: '/emprestimosfinalizados',
				name: 'EmprestimosFinalizadosList',
				component: () => import('@/views/emprestimosfinalizados/EmprestimosFinalizadosList.vue')
			}
			
		]
	}
];

export default emprestimosfinalizadosRoutes;
