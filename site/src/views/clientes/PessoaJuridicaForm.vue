<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import ClientService from '@/service/ClientService';
import UtilService from '@/service/UtilService';
import AddressClient from '../address/Address.vue';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import { useToast } from 'primevue/usetoast';

export default {
	name: 'PessoaJuridicaForm',
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
						if (!this.client.address) {
							this.client.address = [];
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
					tipo_pessoa: 'PJ',
					limit: 2000,
					status: 1,
					address: []
				};
			}
		},
		back() {
			this.router.push(`/clientes/pj`);
		},
		save(redirectToList = true) {
			this.changeLoading();
			this.errors = [];

			this.client.tipo_pessoa = 'PJ';

			this.clientService.save(this.client)
				.then((response) => {
					if (undefined != response.data?.data) {
						this.client = response.data.data;
					}
					this.toast.add({
						severity: ToastSeverity.SUCCESS,
						detail: this.client?.id ? 'Dados alterados com sucesso!' : 'Empresa cadastrada com sucesso!',
						life: 3000
					});
					if (redirectToList) {
						setTimeout(() => {
							this.router.push({ name: 'pjList' });
						}, 1200);
					} else {
						setTimeout(() => {
							this.router.push({ name: 'pjAdd' });
						}, 1200);
					}
				})
				.catch((error) => {
					this.changeLoading();
					this.errors = error?.response?.data?.errors;
					if (error?.response?.status != 422) {
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
		saveAndAddAnother() {
			this.save(false);
		},
		analiseCredito() {
			// Placeholder - Fazer Análise de Crédito
			this.toast.add({
				severity: ToastSeverity.INFO,
				detail: 'Funcionalidade de Análise de Crédito em desenvolvimento.',
				life: 3000
			});
		},
		addCityBeforeSave() {
			this.changeLoading();
		},
		clearCicom() {
			this.loading = true;
		},
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Pessoa Jurídica' : 'Cadastrar Pessoa Jurídica';
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
			<h5 class="px-0 py-0 align-self-center m-2"><i :class="icons.BUILDING"></i> {{ title }}</h5>
		</div>
		<div class="col-4 px-0 py-0 text-right">
			<Button label="Cancelar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.BAN" @click.prevent="back" />
			<Button label="Salvar e cadastrar outro" class="p-button-outlined p-button-info p-button-sm ml-2" :icon="icons.PLUS" type="button" @click.prevent="saveAndAddAnother" v-if="!route.params?.id" />
			<Button label="Cadastrar" class="p-button p-button-info p-button-sm ml-2" :icon="icons.SAVE" type="button" @click.prevent="save" v-if="!route.params?.id" />
			<Button label="Salvar" class="p-button p-button-info p-button-sm ml-2" :icon="icons.SAVE" type="button" @click.prevent="save" v-else />
			<Button label="Fazer Análise de Crédito" class="p-button p-button-success p-button-sm ml-2" :icon="icons.CHECK" type="button" @click.prevent="analiseCredito" v-if="client?.id" />
		</div>
	</div>
	<Card>
		<template #content>
			<div class="col-12">
				<h6 class="mb-3">Dados da Pessoa</h6>
				<div class="p-fluid formgrid grid">
					<div class="field col-12 md:col-4">
						<label for="razao_social">Razão Social *</label>
						<InputText id="razao_social" v-model="client.razao_social" type="text" :class="{ 'p-invalid': errors?.razao_social }" />
						<small v-if="errors?.razao_social" class="p-error">{{ errors.razao_social[0] }}</small>
					</div>
					<div class="field col-12 md:col-4">
						<label for="nome_fantasia">Nome Fantasia</label>
						<InputText id="nome_fantasia" v-model="client.nome_fantasia" type="text" />
					</div>
					<div class="field col-12 md:col-4">
						<label for="cnpj">CNPJ *</label>
						<InputMask id="cnpj" v-model="client.cnpj" mask="99.999.999/9999-99" :class="{ 'p-invalid': errors?.cnpj }" />
						<small v-if="errors?.cnpj" class="p-error">{{ errors.cnpj[0] }}</small>
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
					<div class="field col-12 md:col-6">
						<label for="pix">Chave PIX</label>
						<InputText id="pix" v-model="client.pix_cliente" type="text" placeholder="Opcional" />
					</div>
					<div class="field col-12 md:col-6">
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
