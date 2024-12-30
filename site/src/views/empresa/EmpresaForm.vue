<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import contaspagarService from '@/service/ContaspagarService';
import UtilService from '@/service/UtilService';
import EmpresaService from '@/service/EmpresasService';
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
			empresaService: new EmpresaService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			contaspagar: ref({}),
			intervalId: null,
			empresa: ref({}),
			zap: ref({}),
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
		getinfoempresa() {

			this.empresaService.get()
				.then((response) => {
					this.empresa = response.data;
					if(this.empresa?.whatsapp != null){
						this.getInfoZap();
						this.intervalId = setInterval(this.getInfoZap, 10000);
					}
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
			
		},
		getInfoZap(){
			this.empresaService.zap(this.empresa.whatsapp)
				.then((response) => {
					this.zap = response;
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
		},
		back() {
			this.router.go(-1);
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
		desconectarZap(){
			this.empresaService.desconectarZap(this.empresa.whatsapp)
				.then((response) => {
					window.location.reload();
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
		},
		save() {
			this.changeLoading();
			this.errors = [];

			// if( this.selectedTipoDocumento.value == undefined){
			// 	this.toast.add({
			// 		severity: ToastSeverity.ERROR,
			// 		detail: 'Selecione o Tipo de Documento',
			// 		life: 3000
			// 	});

			// 	return false;
			// }


			this.empresaService.save(this.empresa)
			.then((response) => {
				if (undefined != response.data.data) {
					this.contaspagar = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.contaspagar?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				this.getinfoempresa();

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
			return 'Informações da Empresa';
		}
	},
	mounted() {
		this.getinfoempresa();
	},
	beforeDestroy() {
		clearInterval(this.intervalId); // Limpa o intervalo quando o componente é destruído
	},
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
                    <div class="field col-12 md:col-4">
                        <label for="firstname2">Nome da Empresa</label>
						<Chip :label="`${empresa?.company}`" class="w-full p-inputtext-sm"></Chip>
                    </div>
					<div class="field col-12 md:col-4">
                        <label for="firstname2">Juros</label>
                        <InputText id="firstname2" :modelValue="empresa?.juros" v-model="empresa.juros" type="text" />
                    </div>
					<!-- <div class="field col-12 md:col-4">
                        <label for="firstname2">Caixa</label>
						<InputNumber id="inputnumber" :modelValue="empresa?.caixa" v-model="empresa.caixa" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" ></InputNumber>
                    </div> -->
					<div class="field col-12 md:col-3">
                        <label for="state">Número para Contato</label>
                        <InputMask id="inputmask" :modelValue="empresa?.numero_contato" v-model="empresa.numero_contato" mask="(99) 9999-9999" ></InputMask>
                    </div>
                </div>
				<div v-if="empresa?.whatsapp != null" class="card">
					
					<div v-if="empresa?.whatsapp != null" class="field col-12 md:col-12">
						<h5>Integração whatsapp</h5>
						<Button v-if="zap?.loggedIn" label="Conectado" class="p-button-rounded p-button-success mr-2 mb-2" />
						<Button v-if="zap?.loggedIn" @click.prevent="desconectarZap" label="Clique para desconectar" class="p-button-rounded p-button-danger mr-2 mb-2" />
						<Button v-if="!zap?.loggedIn" label="Aguardando Conexão" class="p-button-rounded p-button-danger mr-2 mb-2" />
                    </div>
					<div v-if="empresa?.whatsapp != null && !zap?.loggedIn" class="field col-12 md:col-12">
						<Image class="mb-5" :src="`${zap?.url}?t=${Date.now()}`" v-if="zap?.url" alt="Image" preview />
                    </div>
				</div>
            
        	</div>
		</template>
	</Card>

</template>
