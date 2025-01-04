<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import FornecedorService from '@/service/FornecedorService';
import ExternoService from '@/service/ExternoService';
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
			fornecedorService: new FornecedorService(),
			externoService: new ExternoService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	data() {
		return {
			fornecedor: ref({}),
			oldClient: ref(null),
			errors: ref([]),
			address: ref({
				id: 1,
				name: 'ok',
				geolocalizacao: '17.23213, 12.455345'
			}),
			loading: ref(false),
			selectedTipoSexo : ref(''),
			cep: null,
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
		getFornecedor() {

			if (this.route.params?.id) {
				this.fornecedor = ref(null);
				this.loading = true;
				this.fornecedorService.get(this.route.params.id)
				.then((response) => {
					this.fornecedor = response.data?.data;
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
				this.fornecedor = ref({});
			}
			
		},
		back() {
			this.router.push(`/fornecedores`);
		},
		changeEnabled(enabled) {
			this.fornecedor.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			this.fornecedorService.save(this.fornecedor)
			.then((response) => {
				if (undefined != response.data.data) {
					this.fornecedor = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.fornecedor?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'fornecedorList'})
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
			// this.fornecedor.cities.push(city);
			this.changeLoading();
		},
		clearCicom() {
			this.loading = true;
		},
		formatCpfCnpj() {
			let value = this.fornecedor?.cpfcnpj.replace(/\D/g, ''); // Remove não dígitos

			if (value.length <= 11) {
				// CPF
				this.fornecedor.cpfcnpj = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
			} else {
				// CNPJ
				this.fornecedor.cpfcnpj = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
			}
		}
		
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Fornecedor' : 'Criar Fornecedor';
		}
	},
	watch: {
		cep(cepVemDoFormulario) {
			
			if (cepVemDoFormulario == "") {
				return;
			}

			if (cepVemDoFormulario.substr(8) != "_") {
				this.fornecedor.cep = cepVemDoFormulario;

				this.externoService.getEndereco(cepVemDoFormulario).then((data) => {
					this.fornecedor.address = data.logradouro;
					this.fornecedor.neighborhood = data.bairro;
					this.fornecedor.city = data.localidade;
					this.fornecedor.number = this.fornecedor?.number;
					this.fornecedor.complement = this.fornecedor?.complement;
				});

				const numeroInput = this.$refs.numeroInput;
				if (numeroInput && numeroInput.$el && numeroInput.$el.focus) {
					numeroInput.$el.focus();
				}

			}
		}
		
	},
	mounted() {
		this.getFornecedor();
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
                        <label for="state">CPF / CNPJ</label>
                        <InputText id="inputmask" :modelValue="fornecedor?.cpfcnpj" v-model="fornecedor.cpfcnpj" @input="formatCpfCnpj" ></InputText>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="firstname2">Nome Completo</label>
                        <InputText id="firstname2" :modelValue="fornecedor?.nome_completo" v-model="fornecedor.nome_completo" type="text" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="firstname2">PIX</label>
                        <InputText id="firstname2" :modelValue="fornecedor?.pix_fornecedor" v-model="fornecedor.pix_fornecedor" type="text" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="state">Telefone Principal</label>
                        <InputMask id="inputmask" :modelValue="fornecedor?.telefone_celular_1" v-model="fornecedor.telefone_celular_1" mask="(99) 9999-9999" ></InputMask>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="zip">Telefone Secundário</label>
                        <InputMask id="inputmask" :modelValue="fornecedor?.telefone_celular_2" v-model="fornecedor.telefone_celular_2" mask="(99) 9999-9999" ></InputMask>
                    </div>
					<div class="field col-12 md:col-12">
                        <label for="state">CEP</label>
                        <InputMask id="inputmask" mask="99999-999" :modelValue="fornecedor?.cep" v-model.trim="cep" ></InputMask>
                    </div>
                    <div class="field col-12 md:col-10">
                        <label for="firstname2">Endereço</label>
                        <InputText id="firstname2" :modelValue="fornecedor?.address" v-model="fornecedor.address" type="text" />
                    </div>
					<div class="field col-12 md:col-2">
                        <label for="state">Número</label>
						<InputText ref="numeroInput" :modelValue="fornecedor?.number" v-model="fornecedor.number" type="text" />
                    </div>
					<div class="field col-12 md:col-6">
                        <label for="firstname2">Bairro</label>
                        <InputText id="firstname2" :modelValue="fornecedor?.neighborhood" v-model="fornecedor.neighborhood" type="text" />
                    </div>
					<div class="field col-12 md:col-6">
                        <label for="firstname2">Cidade</label>
                        <InputText id="firstname2" :modelValue="fornecedor?.city" v-model="fornecedor.city" type="text" />
                    </div>
					<div class="field col-12 md:col-12">
                        <label for="firstname2">Complemento</label>
                        <InputText id="firstname2" :modelValue="fornecedor?.complement" v-model="fornecedor.complement" type="text" />
                    </div>
					
                </div>
            
        	</div>
		</template>
	</Card>

</template>
