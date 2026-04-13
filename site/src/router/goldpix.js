const goldpixRoutes = [
	{
		path: '/goldpix',
		redirect: '/goldpix/teste'
	},
	{
		path: '/goldpix/teste',
		name: 'goldpixTest',
		component: () => import('@/views/goldpix/GoldPixTest.vue'),
		meta: { breadcrumb: [{ parent: 'Testes', label: 'GoldPix' }] }
	}
];

export default goldpixRoutes;
