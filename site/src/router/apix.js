const apixRoutes = [
	{
		path: '/apix',
		redirect: '/apix/teste'
	},
	{
		path: '/apix/teste',
		name: 'apixTest',
		component: () => import('@/views/apix/ApixTest.vue'),
		meta: { title: 'Teste APIX' }
	}
];

export default apixRoutes;
