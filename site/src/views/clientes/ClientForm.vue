<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import ClientService from '@/service/ClientService';
import UtilService from '@/service/UtilService';
import AddressClient from '../address/Address.vue';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'cicomForm',
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
			client: ref({}),
			oldClient: ref(null),
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
		getclient() {

			if (this.route.params?.id) {
				this.client = ref(null);
				this.loading = true;
				this.clientService.get(this.route.params.id)
				.then((response) => {
					this.client = response.data?.data;
					if(this.client?.sexo == 'M'){
						this.selectedTipoSexo = { name: 'Masculino', value: 'M' };
					}else{
						this.selectedTipoSexo = { name: 'Feminino', value: 'F' };
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
			}else{
				this.client = ref({});
				this.client.address = [];
			}
			
		},
		back() {
			this.router.push(`/clientes`);
		},
		changeEnabled(enabled) {
			this.client.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			if( this.selectedTipoSexo.value == undefined){
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Selecione o Sexo',
					life: 3000
				});

				return false;
			}

			this.client.sexo = this.selectedTipoSexo.value;

			this.clientService.save(this.client)
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
					this.router.push({ name: 'clientList'})
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
			this.loading = true;
		},
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Cliente' : 'Criar Cliente';
		}
	},
	mounted() {
		this.getclient();
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
                        <label for="firstname2">Nome Completo</label>
                        <InputText id="firstname2" :modelValue="client?.nome_completo" v-model="client.nome_completo" type="text" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="firstname2">E-mail</label>
                        <InputText id="firstname2" :modelValue="client?.email" v-model="client.email" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="lastname2">Sexo</label>
                        <Dropdown v-model="selectedTipoSexo" :options="sexo" optionLabel="name" placeholder="Selecione" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="lastname2">Dt. Nascimento</label>
                        <InputMask id="inputmask" :modelValue="client?.data_nascimento" v-model="client.data_nascimento" mask="99/99/9999"></InputMask>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="state">Telefone Principal</label>
                        <InputMask id="inputmask" :modelValue="client?.telefone_celular_1" v-model="client.telefone_celular_1" mask="(99) 9999-9999" ></InputMask>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="zip">Telefone Secundário</label>
                        <InputMask id="inputmask" :modelValue="client?.telefone_celular_2" v-model="client.telefone_celular_2" mask="(99) 9999-9999" ></InputMask>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="state">CPF</label>
                        <InputMask id="inputmask" :modelValue="client?.cpf" v-model="client.cpf" mask="999.999.999-99" ></InputMask>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="zip">RG</label>
                        <InputMask id="inputmask" :modelValue="client?.rg" v-model="client.rg" mask="9.999.999" ></InputMask>
                    </div>
					<div class="field col-12 md:col-6">
                        <label for="zip">Limite de Emprestimo</label>
						<InputNumber id="inputnumber" :modelValue="client?.limit" v-model="client.limit" :mode="'currency'" :currency="'BRL'" :locale="'pt-BR'" :precision="2" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.description }"></InputNumber>
                    </div>
					<div v-if="(!this.route.params?.id)" class="field col-12 md:col-6">
                        <label for="firstname2">Senha</label>
                        <InputText id="firstname2" :modelValue="client?.password" v-model="client.password" type="password" />
                    </div>
                </div>
            
        	</div>
			<AddressClient 
				:address="this.client?.address" 
				:oldCicom="this.oldClient"
				:loading="loading" 
				@updateCicom="clearCicom" 
				@addCityBeforeSave="addCityBeforeSave" 
				@changeLoading="changeLoading" 
				v-if="true"
			/>
		</template>
	</Card>

</template>
