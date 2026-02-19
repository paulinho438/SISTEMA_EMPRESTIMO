<script>
import { useRoute, useRouter } from 'vue-router';

import ClientService from '@/service/ClientService';
import UtilService from '@/service/UtilService';
import AddressClient from '../address/Address.vue';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import { useToast } from 'primevue/usetoast';

export default {
	name: 'PessoaFisicaForm',
	setup() {
		return {
			route: useRoute(),
			router: useRouter(),
			clientService: new ClientService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	components: {
		AddressClient,
	},
	data() {
		return {
			client: {},
			oldClient: null,
			errors: [],
			loading: false,
			selectedTipoSexo: '',
			sexo: [
				{ name: 'Masculino', value: 'M' },
				{ name: 'Feminino', value: 'F' }
			],
			estadoCivilOptions: [
				{ label: 'Solteiro(a)', value: 'Solteiro(a)' },
				{ label: 'Casado(a)', value: 'Casado(a)' },
				{ label: 'Divorciado(a)', value: 'Divorciado(a)' },
				{ label: 'Viúvo(a)', value: 'Viúvo(a)' },
				{ label: 'União Estável', value: 'União Estável' }
			],
			regimeBensOptions: [
				{ label: 'Comunhão Universal', value: 'Comunhão Universal' },
				{ label: 'Comunhão Parcial', value: 'Comunhão Parcial' },
				{ label: 'Separação Total', value: 'Separação Total' },
				{ label: 'Participação Final', value: 'Participação Final' }
			]
		};
	},
	methods: {
		changeLoading() {
			this.loading = !this.loading;
		},
		getclient() {
			if (this.route.params?.id) {
				this.loading = true;
				this.clientService.get(this.route.params.id)
					.then((response) => {
						this.client = response.data?.data || {};
						if (!this.client.address) this.client.address = [];
						if (this.client?.sexo === 'M') {
							this.selectedTipoSexo = { name: 'Masculino', value: 'M' };
						} else {
							this.selectedTipoSexo = { name: 'Feminino', value: 'F' };
						}
					})
					.catch((error) => {
						this.toast.add({
							severity: ToastSeverity.ERROR,
							detail: UtilService.message(error),
							life: 3000
						});
					})
					.finally(() => {
						this.loading = false;
					});
			} else {
				this.client = {
					tipo_pessoa: 'PF',
					limit: 2000,
					status: 1,
					address: []
				};
				this.selectedTipoSexo = '';
			}
		},
		back() {
			this.router.push(`/clientes/pf`);
		},
		save() {
			this.changeLoading();
			this.errors = [];

			if (this.selectedTipoSexo?.value === undefined) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Selecione o Sexo',
					life: 3000
				});
				this.changeLoading();
				return;
			}

			this.client.tipo_pessoa = 'PF';
			this.client.sexo = this.selectedTipoSexo.value;

			this.clientService.save(this.client)
				.then((response) => {
					if (response.data?.data) this.client = response.data.data;
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Pessoa física cadastrada com sucesso!',
						life: 3000
					});
					setTimeout(() => {
						this.router.push({ name: 'pfList' });
					}, 1200);
				})
				.catch((error) => {
					this.changeLoading();
					this.errors = error?.response?.data?.errors || {};
					if (error?.response?.status !== 422) {
						this.toast.add({
							severity: ToastSeverity.ERROR,
							detail: UtilService.message(error.response?.data),
							life: 3000
						});
					}
				})
				.finally(() => {
					this.changeLoading();
				});
		},
		addCityBeforeSave() {
			this.changeLoading();
		},
		clearCicom() {
			this.loading = true;
		}
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Pessoa Física' : 'Cadastrar Pessoa Física';
		}
	},
	mounted() {
		this.getclient();
	}
};
</script>

<template>
	<Toast />
	<div class="grid flex flex-wrap mb-3 px-4 pt-2">
		<div class="col-8 px-0 py-0">
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.USER"></i> {{ title }}</h5>
		</div>
		<div class="col-4 px-0 py-0 text-right">
			<Button label="Cancelar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.BAN" @click.prevent="back" />
			<Button label="Salvar" class="p-button p-button-info p-button-sm ml-2" :icon="icons.SAVE" type="button" @click.prevent="save" />
		</div>
	</div>
	<Card>
		<template #content>
			<div class="col-12">
				<h6 class="mb-3">Dados da Pessoa</h6>
				<div class="p-fluid formgrid grid">
					<div class="field col-12 md:col-4">
						<label for="nome_completo">Nome Completo *</label>
						<InputText id="nome_completo" v-model="client.nome_completo" type="text" :class="{ 'p-invalid': errors?.nome_completo }" />
						<small v-if="errors?.nome_completo" class="p-error">{{ errors.nome_completo[0] }}</small>
					</div>
					<div class="field col-12 md:col-2">
						<label for="cpf">CPF *</label>
						<InputMask id="cpf" v-model="client.cpf" mask="999.999.999-99" :class="{ 'p-invalid': errors?.cpf }" />
						<small v-if="errors?.cpf" class="p-error">{{ errors.cpf[0] }}</small>
					</div>
					<div class="field col-12 md:col-2">
						<label for="rg">RG</label>
						<InputMask id="rg" v-model="client.rg" mask="9.999.999" />
					</div>
					<div class="field col-12 md:col-2">
						<label for="orgao_emissor">Órgão Emissor</label>
						<InputText id="orgao_emissor" v-model="client.orgao_emissor_rg" type="text" />
					</div>
					<div class="field col-12 md:col-2">
						<label for="sexo">Sexo *</label>
						<Dropdown v-model="selectedTipoSexo" :options="sexo" optionLabel="name" placeholder="Selecione" :class="{ 'p-invalid': errors?.sexo }" />
						<small v-if="errors?.sexo" class="p-error">{{ errors.sexo[0] }}</small>
					</div>
					<div class="field col-12 md:col-2">
						<label for="data_nascimento">Dt. Nascimento *</label>
						<InputMask id="data_nascimento" v-model="client.data_nascimento" mask="99/99/9999" :class="{ 'p-invalid': errors?.data_nascimento }" />
						<small v-if="errors?.data_nascimento" class="p-error">{{ errors.data_nascimento[0] }}</small>
					</div>
					<div class="field col-12 md:col-2">
						<label for="estado_civil">Estado Civil</label>
						<Dropdown v-model="client.estado_civil" :options="estadoCivilOptions" optionLabel="label" optionValue="value" placeholder="Selecione" />
					</div>
					<div class="field col-12 md:col-2">
						<label for="regime_bens">Regime de Partilha</label>
						<Dropdown v-model="client.regime_bens" :options="regimeBensOptions" optionLabel="label" optionValue="value" placeholder="Selecione" />
					</div>
					<div class="field col-12 md:col-2">
						<label for="renda_mensal">Renda Mensal</label>
						<InputNumber id="renda_mensal" v-model="client.renda_mensal" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="telefone">Telefone *</label>
						<InputMask id="telefone" v-model="client.telefone_celular_1" mask="(99) 99999-9999" :class="{ 'p-invalid': errors?.telefone_celular_1 }" />
						<small v-if="errors?.telefone_celular_1" class="p-error">{{ errors.telefone_celular_1[0] }}</small>
					</div>
					<div class="field col-12 md:col-3">
						<label for="telefone2">Telefone Secundário</label>
						<InputMask id="telefone2" v-model="client.telefone_celular_2" mask="(99) 99999-9999" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="email">E-mail *</label>
						<InputText id="email" v-model="client.email" type="email" :class="{ 'p-invalid': errors?.email }" />
						<small v-if="errors?.email" class="p-error">{{ errors.email[0] }}</small>
					</div>
					<div class="field col-12 md:col-3">
						<label for="limit">Limite de Crédito</label>
						<InputNumber id="limit" v-model="client.limit" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="pix">Chave PIX *</label>
						<InputText id="pix" v-model="client.pix_cliente" type="text" :class="{ 'p-invalid': errors?.pix_cliente }" />
						<small v-if="errors?.pix_cliente" class="p-error">{{ errors.pix_cliente[0] }}</small>
					</div>
					<div class="field col-12 md:col-3">
						<label for="observation">Observação</label>
						<InputText id="observation" v-model="client.observation" type="text" />
					</div>
				</div>
			</div>

			<AddressClient
				:address="client?.address || []"
				:oldCicom="oldClient"
				:loading="loading"
				@updateCicom="clearCicom"
				@addCityBeforeSave="addCityBeforeSave"
				@changeLoading="changeLoading"
				v-if="client"
			/>

			<div class="col-12 mt-4">
				<h6 class="mb-3">Anexos</h6>
				<div class="p-fluid formgrid grid">
					<div class="field col-12">
						<FileUpload name="anexos[]" :multiple="true" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" :maxFileSize="10000000" chooseLabel="Selecionar arquivos" />
						<small class="text-color-secondary">Formatos: PDF, DOC, DOCX, PNG, JPEG. Máx. 10MB</small>
					</div>
				</div>
			</div>
		</template>
	</Card>
</template>
