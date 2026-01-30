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
			bankTypes: [
				{ label: 'Normal', value: 'normal' },
				{ label: 'Bcodex', value: 'bcodex' },
				{ label: 'Cora', value: 'cora' },
				{ label: 'Velana', value: 'velana' },
				{ label: 'XGate', value: 'xgate' },
				{ label: 'APIX', value: 'apix' }
			]
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
				this.banco.bank_type = 'normal';
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
			
			// Se for bcodex, manter wallet = 1, se for cora ou normal, wallet = 0
			if (this.banco.bank_type === 'bcodex') {
				this.banco.wallet = 1;
			} else {
				this.banco.wallet = 0;
			}

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
					<label for="bank_type">Tipo de Banco</label>
					<Dropdown 
						:modelValue="banco?.bank_type || 'normal'" 
						v-model="banco.bank_type" 
						:options="bankTypes" 
						optionLabel="label" 
						optionValue="value"
						placeholder="Selecione o tipo de banco"
						class="w-full p-inputtext-sm"
						:class="{ 'p-invalid': errors?.bank_type }"
					/>
					<small v-if="errors?.bank_type" class="text-red-500 pl-2">{{ errors?.bank_type[0] }}</small>
				</div>
			</div>

			<div class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<h5>Wallet? (Bcodex)</h5>
					<InputSwitch :modelValue="banco?.wallet" v-model="banco.wallet" :disabled="banco?.bank_type !== 'bcodex'" />
					<small class="text-gray-500 pl-2">Ativo apenas para bancos Bcodex</small>
				</div>
			</div>

			<!-- Campos Bcodex -->
			<div v-if="banco?.bank_type === 'bcodex' || banco?.wallet" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="document">B.CODEX Documento</label>
					<InputText :modelValue="banco?.document" v-model="banco.document" id="document" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.document }" />
					<small v-if="errors?.document" class="text-red-500 pl-2">{{ errors?.document[0] }}</small>
				</div>
			</div>

			<div v-if="banco?.bank_type === 'bcodex' || banco?.wallet" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="accountId">B.CODEX accountId</label>
					<InputText :modelValue="banco?.accountId" v-model="banco.accountId" id="accountId" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.accountId }" />
					<small v-if="errors?.accountId" class="text-red-500 pl-2">{{ errors?.accountId[0] }}</small>
				</div>
			</div>

			<!-- Campos Cora -->
			<div v-if="banco?.bank_type === 'cora'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="client_id">Cora Client ID</label>
					<InputText :modelValue="banco?.client_id" v-model="banco.client_id" id="client_id" type="text" class="w-full p-inputtext-sm" placeholder="Ex: int-3g1VYFU7tflsufR9ZrsUXp" :class="{ 'p-invalid': errors?.client_id }" />
					<small v-if="errors?.client_id" class="text-red-500 pl-2">{{ errors?.client_id[0] }}</small>
					<small class="text-gray-500 pl-2">Client ID fornecido pela Cora</small>
				</div>
			</div>

			<div v-if="banco?.bank_type === 'cora'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="certificate_path">Caminho do Certificado</label>
					<InputText :modelValue="banco?.certificate_path" v-model="banco.certificate_path" id="certificate_path" type="text" class="w-full p-inputtext-sm" placeholder="Ex: C:\projetos\...\certificate.pem" :class="{ 'p-invalid': errors?.certificate_path }" />
					<small v-if="errors?.certificate_path" class="text-red-500 pl-2">{{ errors?.certificate_path[0] }}</small>
					<small class="text-gray-500 pl-2">Caminho absoluto do arquivo certificate.pem</small>
				</div>
			</div>

			<div v-if="banco?.bank_type === 'cora'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="private_key_path">Caminho da Chave Privada</label>
					<InputText :modelValue="banco?.private_key_path" v-model="banco.private_key_path" id="private_key_path" type="text" class="w-full p-inputtext-sm" placeholder="Ex: C:\projetos\...\private-key.key" :class="{ 'p-invalid': errors?.private_key_path }" />
					<small v-if="errors?.private_key_path" class="text-red-500 pl-2">{{ errors?.private_key_path[0] }}</small>
					<small class="text-gray-500 pl-2">Caminho absoluto do arquivo private-key.key</small>
				</div>
			</div>

			<!-- Campos Velana -->
			<div v-if="banco?.bank_type === 'velana'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="velana_public_key">Velana Public Key (Chave Pública)</label>
					<InputText 
						:modelValue="banco?.velana_public_key" 
						v-model="banco.velana_public_key" 
						id="velana_public_key" 
						type="text"
						class="w-full p-inputtext-sm" 
						placeholder="Digite a chave pública da Velana (pk_live_...)"
						:class="{ 'p-invalid': errors?.velana_public_key }"
					/>
					<small v-if="errors?.velana_public_key" class="text-red-500 pl-2">{{ errors?.velana_public_key[0] }}</small>
					<small class="text-gray-500 pl-2">Chave pública encontrada em Configurações -> Credenciais de API no painel Velana</small>
				</div>
			</div>

			<div v-if="banco?.bank_type === 'velana'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="velana_secret_key">Velana Secret Key (Chave Secreta)</label>
					<InputText 
						:modelValue="banco?.velana_secret_key" 
						v-model="banco.velana_secret_key" 
						id="velana_secret_key" 
						type="password"
						class="w-full p-inputtext-sm" 
						placeholder="Digite a chave secreta da Velana (sk_live_...)"
						:class="{ 'p-invalid': errors?.velana_secret_key }"
					/>
					<small v-if="errors?.velana_secret_key" class="text-red-500 pl-2">{{ errors?.velana_secret_key[0] }}</small>
					<small class="text-gray-500 pl-2">Chave secreta encontrada em Configurações -> Credenciais de API no painel Velana</small>
				</div>
			</div>

			<!-- Campos XGate -->
			<div v-if="banco?.bank_type === 'xgate'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="xgate_email">XGate Email</label>
					<InputText 
						:modelValue="banco?.xgate_email" 
						v-model="banco.xgate_email" 
						id="xgate_email" 
						type="email"
						class="w-full p-inputtext-sm" 
						placeholder="Digite o email da conta XGate"
						:class="{ 'p-invalid': errors?.xgate_email }"
					/>
					<small v-if="errors?.xgate_email" class="text-red-500 pl-2">{{ errors?.xgate_email[0] }}</small>
					<small class="text-gray-500 pl-2">Email utilizado para login na plataforma XGate</small>
				</div>
			</div>

			<div v-if="banco?.bank_type === 'xgate'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="xgate_password">XGate Password (Senha)</label>
					<InputText 
						:modelValue="banco?.xgate_password" 
						v-model="banco.xgate_password" 
						id="xgate_password" 
						type="password"
						class="w-full p-inputtext-sm" 
						placeholder="Digite a senha da conta XGate"
						:class="{ 'p-invalid': errors?.xgate_password }"
					/>
					<small v-if="errors?.xgate_password" class="text-red-500 pl-2">{{ errors?.xgate_password[0] }}</small>
					<small class="text-gray-500 pl-2">Senha utilizada para login na plataforma XGate (será criptografada)</small>
				</div>
			</div>

			<!-- Campos APIX (apixpag.com) - Token via POST /api/auth/token com client_id e client_secret -->
			<div v-if="banco?.bank_type === 'apix'" class="formgrid grid">
				<div class="field col-12 md:col-12 lg:col-12 xl:col-12">
					<label for="apix_base_url">APIX - URL da API</label>
					<InputText 
						:modelValue="banco?.apix_base_url" 
						v-model="banco.apix_base_url" 
						id="apix_base_url" 
						class="w-full p-inputtext-sm" 
						placeholder="https://api.apixpag.com (deixe vazio para usar o padrão)"
						:class="{ 'p-invalid': errors?.apix_base_url }"
					/>
					<small v-if="errors?.apix_base_url" class="text-red-500 pl-2">{{ errors?.apix_base_url[0] }}</small>
					<small class="text-gray-500 pl-2">URL base da API APIX. Documentação: app.apixpag.com/docs/deposits</small>
				</div>
			</div>

			<div v-if="banco?.bank_type === 'apix'" class="formgrid grid">
				<div class="field col-12 md:col-6 lg:col-6 xl:col-6">
					<label for="apix_client_id">APIX - Client ID</label>
					<InputText 
						:modelValue="banco?.apix_client_id" 
						v-model="banco.apix_client_id" 
						id="apix_client_id" 
						class="w-full p-inputtext-sm" 
						placeholder="seu_client_id"
						:class="{ 'p-invalid': errors?.apix_client_id }"
					/>
					<small v-if="errors?.apix_client_id" class="text-red-500 pl-2">{{ errors?.apix_client_id[0] }}</small>
					<small class="text-gray-500 pl-2">Usado em POST /api/auth/token</small>
				</div>
				<div class="field col-12 md:col-6 lg:col-6 xl:col-6">
					<label for="apix_client_secret">APIX - Client Secret</label>
					<InputText 
						:modelValue="banco?.apix_client_secret" 
						v-model="banco.apix_client_secret" 
						id="apix_client_secret" 
						type="password"
						class="w-full p-inputtext-sm" 
						placeholder="seu_client_secret"
						:class="{ 'p-invalid': errors?.apix_client_secret }"
					/>
					<small v-if="errors?.apix_client_secret" class="text-red-500 pl-2">{{ errors?.apix_client_secret[0] }}</small>
					<small class="text-gray-500 pl-2">Será criptografado. Obtenha no painel APIX.</small>
				</div>
			</div>

		</template>
	</Card>

</template>
