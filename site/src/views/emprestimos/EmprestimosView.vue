<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import EmprestimoService from '@/service/EmprestimoService';
import UtilService from '@/service/UtilService';
import EmprestimoParcelas from '../parcelas/Parcelas.vue';
import skeletonEmprestimos from '../skeleton/SkeletonEmprestimos.vue';
import EmprestimoAdd from '../emprestimos/EmprestimosAdd.vue';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'cicomForm',
	setup() {
		return {
			route: useRoute(),
			router: useRouter(),
			emprestimoService: new EmprestimoService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	components: {
		EmprestimoParcelas,
		skeletonEmprestimos,
		EmprestimoAdd,
	},
	data() {
		return {
			client: ref({}),
			oldClient: ref(null),
			errors: ref([]),
			city: ref(null),
			cities: ref([]),
			bancos: ref([]),
			banco: ref(null),
			costcenters: ref([]),
			costcenter: ref(null),
			consultores: ref([]),
			consultor: ref(null),
			parcelas: ref([]),
			address: ref({
				id: 1,
				name: 'ok',
				geolocalizacao: '17.23213, 12.455345'
			}),
			loading: ref(false),
			selectedTipoSexo : ref(''),
			sexo: ref([
					{ name: 'Masculino', value: 'M' },
					{ name: 'Feminino', value: 'F' },
				])
			}
			
	},
	methods: {
		changeLoading() {
			this.loading = !this.loading;
		},
		async searchCliente(event) {
			try {
				let response = await this.emprestimoService.searchClient(event.query);
				this.cities = response.data.data;
			} catch (e) {
				console.log(e);
			}
		},
		async searchBanco(event) {
			try {
				let response = await this.emprestimoService.searchbanco(event.query);
				this.bancos = response.data.data;
			} catch (e) {
				console.log(e);
			}
		},
		async searchCostcenter(event) {
			try {
				let response = await this.emprestimoService.searchCostcenter(event.query);
				this.costcenters = response.data.data;
			} catch (e) {
				console.log(e);
			}
		},
		async searchConsultor(event) {
			try {
				let response = await this.emprestimoService.searchConsultor(event.query);
				this.consultores = response.data;
			} catch (e) {
				console.log(e);
			}
		},
		saveNewParcela(address) {
			this.parcelas.push(address);
		},
		saveInfoDoEmprestimo(emprestimo) {

			this.client.valor = emprestimo.valor
			this.client.lucro = emprestimo.lucro
			this.client.juros = emprestimo.juros
		},
		getemprestimo() {

			if (this.route.params?.id) {
				this.client = ref(null);
				this.loading = true;
				this.emprestimoService.get(this.route.params.id)
				.then((response) => {
					this.client = response.data?.data;
					this.city = response.data?.data.cliente;
					this.banco = response.data?.data.banco;
					this.costcenter = response.data?.data.costcenter;
					this.consultor = response.data?.data.consultor;
					this.parcelas = response.data?.data.parcelas;
				})
				.catch((error) => {
					this.toast.add({
						severity: ToastSeverity.ERROR,
						detail: UtilService.message(e),
						life: 3000
					});
				})
				.finally(() => {
					this.loading = false;
				});
			}else{
				this.client = ref({});
				this.client.address = [];
			}
			
		},
		back() {
			this.router.push(`/emprestimos`);
		},
		changeEnabled(enabled) {
			this.client.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			this.client.cliente = this.city;
			this.client.banco = this.banco;
			this.client.costcenter = this.costcenter;
			this.client.consultor = this.consultor;
			this.client.parcelas = this.parcelas;

			this.toast.add({
				severity: ToastSeverity.SUCCESS,
				detail: 'Gerando Chaves Pix, aguarde!',
				life: 3000
			});

			this.emprestimoService.save(this.client)
			.then((response) => {
				if (undefined != response.data.data) {
					this.client = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'emprestimosList'})
				}, 1200)

			})
			.catch((error) => {
				this.changeLoading();
				this.errors = error?.response?.data?.errors;

				if (error?.response?.status != 422) {
					this.toast.add({
						severity: ToastSeverity.ERROR,
						detail: UtilService.message(error.response.data),
						life: 3000
					});
				}

				this.changeLoading();
			})
			.finally(() => {
				this.changeLoading();
			});
		},
		
		clearclient() {
			this.loading = true;
		},
		addCityBeforeSave(city) {
			// this.client.cities.push(city);
			this.changeLoading();
		},
		clearCicom() {
			this.getemprestimo();
		},
	},
	computed: {
		title() {
			return 'Visualizar Empréstimo';
		}
	},
	mounted() {
		this.getemprestimo();
	}
};
</script>

<template>
	<div class="grid flex flex-wrap mb-3 px-4 pt-2">
		<div class="col-8 px-0 py-0">
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.BUILDING"></i> {{ title }}</h5>
		</div>
		<div class="col-4 px-0 py-0 text-right">
			<Button label="Voltar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.ANGLE_LEFT" @click.prevent="back" />
			<Button v-if="!this.route.params?.id" label="Salvar" class="p-button p-button-info p-button-sm ml-3" :icon="icons.SAVE" type="button" @click.prevent="save" />
		</div>
	</div>
	<skeletonEmprestimos :loading="loading" />
	<div v-if="!loading">
		<Toast />
		<Card>
			<template #content>
				<div class="col-12">
					<div class="p-fluid formgrid grid">
						<div class="field col-12 md:col-12">
							<label for="firstname2">Consultor</label>
							<Chip :label="consultor?.nome_completo" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-12">
							<label for="firstname2">Cliente</label>
							<Chip :label="city?.nome_completo_cpf" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-12">
							<label for="firstname2">Banco</label>
							<Chip :label="banco?.name_agencia_conta" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-12">
							<label for="firstname2">Centro de Custo</label>
							<Chip :label="costcenter?.name" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-3">
							<label for="firstname2">Valor do Emprestimo</label>
							<Chip :label="client?.valor?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-3">
							<label for="firstname2">Lucro Previsto</label>
							<Chip :label="client?.lucro?.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-3">
							<label for="firstname2">Parcelas</label>
							<Chip :label="`${parcelas?.length.toString().padStart(3, '0')}`" class="w-full p-inputtext-sm"></Chip>
						</div>
						<div class="field col-12 md:col-3">
							<label for="firstname2">Juros</label>
							<Chip :label="`% ${client?.juros}`" class="w-full p-inputtext-sm"></Chip>
						</div>
					</div>
				</div>
				<EmprestimoAdd 
					:address="this.client" 
					:oldCicom="this.oldClient"
					:loading="loading" 
					@updateCicom="clearCicom" 
					@addCityBeforeSave="addCityBeforeSave" 
					@changeLoading="changeLoading" 
					@saveParcela="saveNewParcela"
					@saveInfoEmprestimo="saveInfoDoEmprestimo"
					v-if="true"
				/>
				<EmprestimoParcelas 
					:address="this.parcelas" 
					:oldCicom="this.oldClient"
					:loading="loading" 
					:viewCreated="false"
					:aprovacao="false"
					@updateCicom="clearCicom" 
					@addCityBeforeSave="addCityBeforeSave" 
					@changeLoading="changeLoading" 
					v-if="true"
				/>
			</template>
		</Card>
	</div>
</template>
