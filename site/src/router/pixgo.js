const pixgoRoutes = [
	{
		path: '/pixgo',
		redirect: '/pixgo/teste'
	},
	{
		path: '/pixgo/teste',
		name: 'pixgoTest',
		component: () => import('@/views/pixgo/PixGoTest.vue'),
		meta: { breadcrumb: [{ parent: 'Testes', label: 'PixGo' }] }
	}
];

export default pixgoRoutes;
