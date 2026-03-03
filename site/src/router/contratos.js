import ContratosList from '@/views/contratos/ContratosList.vue';
import ContratosAssinaturasList from '@/views/contratos/ContratosAssinaturasList.vue';
import ContratoAssinaturaReview from '@/views/contratos/ContratoAssinaturaReview.vue';

const contratosRoutes = [
	{
		path: '/contratos',
		children: [
			{
				path: '/contratos',
				name: 'contratosList',
				component: ContratosList
			},
			{
				path: '/contratos/assinaturas',
				name: 'contratosAssinaturasList',
				component: ContratosAssinaturasList
			},
			{
				path: '/contratos/:id/assinatura',
				name: 'contratoAssinaturaReview',
				component: ContratoAssinaturaReview
			}
		]
	}
];

export default contratosRoutes;
