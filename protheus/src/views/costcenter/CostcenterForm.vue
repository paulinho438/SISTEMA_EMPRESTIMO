<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import CostcenterService from '@/service/CostcenterService';
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
			costcenterService: new CostcenterService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			costcenter: ref({}),
			oldCicom: ref(null),
			errors: ref([]),
			loading: ref(false),
		}
	},
	methods: {
		changeLoading() {
			this.loading = !this.loading;
		},
		getCostcenter() {

			if (this.route.params?.id) {
				this.costcenter = ref(null);
				this.loading = true;
				this.costcenterService.get(this.route.params.id)
				.then((response) => {
					this.costcenter = response.data?.data;
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
			} 
			
		},
		back() {
			this.router.push(`/centro_de_custo`);
		},
		changeEnabled(enabled) {
			this.costcenter.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			this.costcenterService.save(this.costcenter)
			.then((response) => {
				if (undefined != response.data.data) {
					this.costcenter = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.costcenter?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'costcenterList'})
				}, 1200)

			})
			.catch((error) => {
				this.changeLoading();
				this.errors = error?.response?.data?.errors;

				// if (error?.response?.status != 422) {
				// 	this.toast.add({
				// 		severity: ToastSeverity.ERROR,
				// 		detail: UtilService.message(error.response.data),
				// 		life: 3000
				// 	});
				// }

				this.changeLoading();
			})
			.finally(() => {
				this.changeLoading();
			});
		},
		clearCategory() {
			this.loading = true;
		},
		addCityBeforeSave(city) {
			// this.costcenter.cities.push(city);
			this.changeLoading();
		}
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Centro de Custo' : 'Criar Centro de Custo';
		}
	},
	mounted() {
		this.getCostcenter();
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
			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Nome</label>
					<InputText :modelValue="costcenter?.name" v-model="costcenter.name" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.name }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>
			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="description">Descrição</label>
					<InputText :modelValue="costcenter?.description" v-model="costcenter.description" id="description" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.description" class="text-red-500 pl-2">{{ errors?.description[0] }}</small>
				</div>
			</div>
			
		</template>
	</Card>

</template>
