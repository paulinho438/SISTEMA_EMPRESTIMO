const UsuarioRoutes = [
	{
		path: '/usuarios',
		redirect: '/usuarios',
		children: [
			{
				path: '/usuarios',
				name: 'usuarioList',
				component: () => import('@/views/usuarios/UsuarioList.vue')
			},
			{
				path: '/usuarios/:id/edit',
				name: 'usuarioEdit',
				component: () => import('@/views/usuarios/UsuarioForm.vue')
			},
			{
				path: '/usuarios/add',
				name: 'usuarioAdd',
				component: () => import('@/views/usuarios/UsuarioForm.vue')
			}
		]
	}
];

export default UsuarioRoutes;
