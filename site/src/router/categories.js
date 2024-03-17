const categoriesRoutes = [
	{
		path: '/categorias',
		redirect: '/categorias',
		children: [
			{
				path: '/categorias',
				name: 'categoriesList',
				component: () => import('@/views/categories/CategoriesList.vue')
			},
			{
				path: '/categorias/:id/edit',
				name: 'categoriesEdit',
				component: () => import('@/views/categories/CategoriesForm.vue')
			},
			{
				path: '/categorias/add',
				name: 'categoriesAdd',
				component: () => import('@/views/categories/CategoriesForm.vue')
			}
		]
	}
];

export default categoriesRoutes;
