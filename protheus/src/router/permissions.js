const permissionsRoutes = [
	{
		path: '/permissoes',
		redirect: '/permissoes',
		children: [
			{
				path: '/permissoes',
				name: 'permissionsList',
				component: () => import('@/views/permissions/PermissionsList.vue')
			},
			{
				path: '/permissoes/:id/edit',
				name: 'permissionsEdit',
				component: () => import('@/views/permissions/CicomForm.vue')
			},
			{
				path: '/permissoes/add',
				name: 'permissionsAdd',
				component: () => import('@/views/permissions/CicomForm.vue')
			}
		]
	}
];

export default permissionsRoutes;
