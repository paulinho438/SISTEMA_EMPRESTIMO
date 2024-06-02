<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import FeriadoService from '@/service/FeriadoService';
import UtilService from '@/service/UtilService';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'cicomForm',
	setup() {
		return {
			route: useRoute(),
			router: useRouter(),
			feriadoService: new FeriadoService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			feriado: ref({}),
			oldFeriado: ref(null),
			errors: ref([]),
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
		getFeriado() {

			if (this.route.params?.id) {
				this.feriado = ref(null);
				this.loading = true;
				this.feriadoService.get(this.route.params.id)
				.then((response) => {
					this.feriado = response.data?.data;
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
				this.feriado = ref({});
			}
			
		},
		back() {
			this.router.push(`/feriados`);
		},
		changeEnabled(enabled) {
			this.feriado.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			if( !this.feriado.description ){
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Insira uma descrição',
					life: 3000
				});

				return false;
			}

			if( !this.feriado.data_feriado ){
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Informe uma data',
					life: 3000
				});

				return false;
			}

			this.feriadoService.save(this.feriado)
			.then((response) => {
				if (undefined != response.data?.data) {
					this.feriado = response.data?.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.Feriado?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'feriadosList'})
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
		
		clearFeriado() {
			this.loading = true;
		},
		addCityBeforeSave(city) {
			// this.Feriado.cities.push(city);
			this.changeLoading();
		},
		clearCicom() {
			this.loading = true;
		},
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Feriado' : 'Criar Feriado';
		}
	},
	mounted() {
		this.getFeriado();
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
                    <div class="field col-12 md:col-3">
                        <label for="firstname2">Descrição</label>
                        <InputText id="firstname2" :modelValue="feriado?.description" v-model="feriado.description" type="text" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="lastname2">Data do Feriado</label>
                        <InputMask id="inputmask" :modelValue="feriado?.data_feriado" v-model="feriado.data_feriado" mask="99/99/9999"></InputMask>
                    </div>
                </div>
            
        	</div>
		</template>
	</Card>

</template>
