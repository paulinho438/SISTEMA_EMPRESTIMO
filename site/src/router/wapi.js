const wapiRoutes = [
	{
		path: '/wapi',
		redirect: '/wapi/fila'
	},
	{
		path: '/wapi/fila',
		name: 'wapiFila',
		component: () => import('@/views/wapi/FilaWapi.vue'),
		meta: { title: 'Fila WAPI', permission: 'view_mastergeral' }
	}
];

export default wapiRoutes;
