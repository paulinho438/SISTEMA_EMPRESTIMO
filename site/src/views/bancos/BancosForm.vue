<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import BancoService from '@/service/BancoService';
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
			bancoService: new BancoService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			banco: ref({}),
			oldCicom: ref(null),
			errors: ref([]),
			loading: ref(false),
		}
	},
	methods: {
		changeLoading() {
			this.loading = !this.loading;
		},
		getBanco() {

			if (this.route.params?.id) {
				this.banco = ref(null);
				this.loading = true;
				this.bancoService.get(this.route.params.id)
				.then((response) => {
					this.banco = response.data?.data;
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
				this.banco.wallet = false;
			}
		
			
		},
		back() {
			this.router.push(`/bancos`);
		},
		changeEnabled(enabled) {
			this.banco.enabled = enabled;
		},
		uploadCertificado(){
			this.banco.certificado = this.$refs.certificado.files[0];

		},
		save() {
			this.changeLoading();
			this.errors = [];

			this.banco.wallet = (this.banco.wallet) ? 1 : 0;

			this.bancoService.saveComCertificado(this.banco)
			.then((response) => {
				if (undefined != response.data.data) {
					this.banco = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.banco?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'bancosList'})
				}, 1200)

			})
			.catch((error) => {
				this.changeLoading();
				this.errors = error?.response?.data?.errors;

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
			// this.banco.cities.push(city);
			this.changeLoading();
		}
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Banco' : 'Criar Banco';
		}
	},
	mounted() {
		this.getBanco();
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
					<InputText :modelValue="banco?.name" v-model="banco.name" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Agência</label>
					<InputText :modelValue="banco?.agencia" v-model="banco.agencia" id="agencia" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Conta</label>
					<InputText :modelValue="banco?.conta" v-model="banco.conta" id="conta" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Saldo</label>
					<InputNumber id="inputnumber" :modelValue="banco?.saldo" v-model="banco.saldo" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }"></InputNumber>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Beneficiário Pix</label>
					<InputText :modelValue="banco?.info_recebedor_pix" v-model="banco.info_recebedor_pix" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Chave Pix</label>
					<InputText :modelValue="banco?.chavepix" v-model="banco.chavepix" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div v-if="banco?.wallet" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">Juros de cobrança</label>
					<InputText :modelValue="banco?.juros" v-model="banco.juros" id="name" type="text" class="w-full p-inputtext-sm" placeholder="Exemplo 1.9" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<h5>Wallet?</h5>
					<InputSwitch :modelValue="banco?.wallet" v-model="banco.wallet" />
				</div>
			</div>

			<div v-if="banco?.wallet" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">B.CODEX Documento</label>
					<InputText :modelValue="banco?.document" v-model="banco.document" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

			<div v-if="banco?.wallet" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="name">B.CODEX accountId</label>
					<InputText :modelValue="banco?.accountId" v-model="banco.accountId" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>

		</template>
	</Card>

</template>
