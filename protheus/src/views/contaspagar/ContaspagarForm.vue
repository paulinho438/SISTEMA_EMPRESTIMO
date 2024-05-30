<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import contaspagarService from '@/service/ContaspagarService';
import UtilService from '@/service/UtilService';
import EmprestimoService from '@/service/EmprestimoService';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'cicomForm',
	setup() {
		return {
			route: useRoute(),
			router: useRouter(),
			contaspagarService: new contaspagarService(),
			emprestimoService: new EmprestimoService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			contaspagar: ref({}),
			oldcontaspagar: ref(null),
			errors: ref([]),
			bancos: ref([]),
			banco: ref(null),
			fornecedores: ref([]),
			fornecedor: ref(null),
			costcenters: ref([]),
			costcenter: ref(null),
			address: ref({
				id: 1,
				name: 'ok',
				geolocalizacao: '17.23213, 12.455345'
			}),
			loading: ref(false),
			selectedTipoDocumento : ref(''),
			tipoDocumento: ref([
					{ name: 'Boleto', value: 'Boleto' },
					{ name: 'Carnê', value: 'Carnê' },
					{ name: 'Cheque', value: 'Cheque' },
					{ name: 'Promissória', value: 'Promissória' },
					{ name: 'Retirada', value: 'Retirada' },
					{ name: 'Outros', value: 'Outros' },
				])
			}
	},
	methods: {
		changeLoading() {
			this.loading = !this.loading;
		},
		getcontaspagar() {

			if (this.route.params?.id) {
				this.contaspagar = ref(null);
				this.loading = true;
				this.contaspagarService.get(this.route.params.id)
				.then((response) => {
					this.contaspagar = response.data?.data;
					this.selectedTipoDocumento = { name: this.contaspagar?.tipodoc, value: this.contaspagar?.tipodoc };
					this.costcenter = response.data?.data.costcenter;
					this.banco = response.data?.data.banco;
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
				this.contaspagar = ref({});
				this.contaspagar.address = [];
			}
			
		},
		back() {
			this.router.push(`/contaspagar`);
		},
		changeEnabled(enabled) {
			this.contaspagar.enabled = enabled;
		},
		async searchFornecedor(event) {
			try {
				let response = await this.emprestimoService.searchFornecedor(event.query);
				this.fornecedores = response.data.data;
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
		save() {
			this.changeLoading();
			this.errors = [];

			if( this.selectedTipoDocumento.value == undefined){
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Selecione o Tipo de Documento',
					life: 3000
				});

				return false;
			}

			this.contaspagar.tipodoc = this.selectedTipoDocumento.value;
			this.contaspagar.costcenter = this.costcenter;
			this.contaspagar.banco = this.banco;
			this.contaspagar.fornecedor = this.fornecedor;

			this.contaspagarService.save(this.contaspagar)
			.then((response) => {
				if (undefined != response.data.data) {
					this.contaspagar = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.contaspagar?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'contaspagarList'})
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
		
		clearcontaspagar() {
			this.loading = true;
		},
		addCityBeforeSave(city) {
			// this.contaspagar.cities.push(city);
			this.changeLoading();
		},
		clearCicom() {
			this.loading = true;
		},
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Título' : 'Criar Título';
		}
	},
	mounted() {
		this.getcontaspagar();
	}
};
</script>

<template>
	<Toast />
	<!-- <LoadingComponent :loading="loading" /> -->
	<div class="grid flex flex-wrap mb-3 px-4 pt-2">
		<div class="col-8 px-0 py-0">
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.BUILDING"></i> {{ title }}</h5>
		</div>
		<div class="col-4 px-0 py-0 text-right">
			<Button label="Voltar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.ANGLE_LEFT" @click.prevent="back" />
			<Button label="Salvar" class="p-button p-button-info p-button-sm ml-3" :icon="icons.SAVE" type="button" @click.prevent="save" />
		</div>
	</div>
	<Card>
		<template #content>
			<div class="col-12">
                <div class="p-fluid formgrid grid">
					<div class="field col-12 md:col-12">
						<label for="firstname2">Fornecedor</label>
						<AutoComplete :modelValue="fornecedor" v-model="fornecedor" :dropdown="true" :suggestions="fornecedores" placeholder="Informe o nome do fornecedor" class="w-full" inputClass="w-full p-inputtext-sm" @complete="searchFornecedor($event)" optionLabel="nome_completo" />
					</div>
					<div class="field col-12 md:col-12">
						<label for="firstname2">Banco</label>
						<AutoComplete :modelValue="banco" v-model="banco" :dropdown="true" :suggestions="bancos" placeholder="Informe o nome do banco" class="w-full" inputClass="w-full p-inputtext-sm" @complete="searchBanco($event)" optionLabel="name_agencia_conta" />
					</div>
					<div class="field col-12 md:col-12">
						<label for="firstname2">Centro de Custo</label>
						<AutoComplete :modelValue="costcenter" :dropdown="true" v-model="costcenter" :suggestions="costcenters" placeholder="Informe o centro de custo" class="w-full" inputClass="w-full p-inputtext-sm" @complete="searchCostcenter($event)" optionLabel="name" />
					</div>
                    <div class="field col-12 md:col-6">
                        <label for="firstname2">Descricão</label>
                        <InputText id="firstname2" :modelValue="contaspagar?.descricao" v-model="contaspagar.descricao" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="lastname2">Tipo Documento</label>
                        <Dropdown v-model="selectedTipoDocumento" :options="tipoDocumento" optionLabel="name" placeholder="Selecione" />
                    </div>
					
					<div class="field col-12 md:col-3">
                        <label for="zip">Valor</label>
						<InputNumber id="inputnumber" :modelValue="contaspagar?.valor" v-model="contaspagar.valor" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }"></InputNumber>
                    </div>

					<div v-if="selectedTipoDocumento.value == 'Boleto'" class="field col-12 md:col-12">
                        <label for="firstname2">Código de Barras</label>
                        <InputText id="firstname2" :modelValue="contaspagar?.cod_barras" v-model="contaspagar.cod_barras" type="text" />
                    </div>
					
                </div>
            
        	</div>
		</template>
	</Card>

</template>
