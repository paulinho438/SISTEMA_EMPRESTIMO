const localizacaousuarioRoutes = [
	{
		path: '/localizacaousuario',
		redirect: '/localizacaousuario',
		children: [
			{
				path: '/localizacaousuario',
				name: 'localizacaousuarioList',
				component: () => import('@/views/localizacaousuario/LocalizacaoUsuario.vue')
			},
		]
	}
];

export default localizacaousuarioRoutes;
