<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import PermissionsService from '@/service/PermissionsService';
import UtilService from '@/service/UtilService';

import { ToastSeverity, PrimeIcons } from 'primevue/api';
// import City from '../cities/City.vue';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'cicomForm',
	setup() {
		return {
			route: useRoute(),
			router: useRouter(),
			permissionsService: new PermissionsService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	// components: {
	// 	City, LoadingComponent
	// },
	data() {
		return {
			cicom: ref({}),
			oldCicom: ref(null),
			errors: ref([]),
			loading: ref(false),
			multiselectValueDashboard: ref(null),
			multiselectValueEmpresa: ref(null),
			multiselectValueUsuario: ref(null),
			multiselectValuePermissoes: ref(null),
			multiselectValues : ref([]),
			checkboxValue : ref([])
		}
	},
	methods: {
		setOldCicom() {
			if (this.cicom?.id) {
				this.oldCicom = {
					id: this.cicom.id,
				};
			}
		},
		changeLoading() {
			this.loading = !this.loading;
		},
		getGroup() {

			if (this.route.params?.id) {
				this.cicom = ref(null);
				this.loading = true;
				this.permissionsService.get(this.route.params.id)
				.then((response) => {
					this.cicom = response.data;
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
					this.setOldCicom();
				});
			} 
			
		},
		getItems() {
			this.multiselectValues = ref(null);
			this.loading = true;
			this.permissionsService.getItems()
			.then((response) => {
				this.multiselectValues = response.data;
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
				this.setOldCicom();
			});
			
		},
		getItemsGroup() {
			this.multiselectValue = ref(null);
			this.multiselectValueDashboard = ref(null);
			this.multiselectValueEmpresa = ref(null);
			this.multiselectValueUsuario = ref(null);
			this.multiselectValuePermissoes = ref(null);
			this.checkboxValue = ref([])
			

			if (this.route.params?.id) {
				this.loading = true;
				this.permissionsService.getItemsGroup(this.route.params.id)
				.then((response) => {
					this.checkboxValue = response.data?.data;
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
					this.setOldCicom();
				});
			} 
		},
		back() {
			this.router.push(`/permissoes`);
		},
		changeEnabled(enabled) {
			this.cicom.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			this.cicom.permissions = this.checkboxValue;

			this.permissionsService.save(this.cicom)
			.then((response) => {
				if (undefined != response.data.data) {
					this.cicom = response.data.data;
					
				}

				this.toast.add({
					severity: ToastSeverity.SUCCESS,
					detail: this.cicom?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
					life: 3000
				});

				setTimeout(() => {
					this.router.push({ name: 'permissionsList'})
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
		clearCicom() {
			this.loading = true;
		},
		addCityBeforeSave(city) {
			// this.cicom.cities.push(city);
			this.changeLoading();
		}
	},
	computed: {
		title() {
			return this.route.params?.id ? 'Editar Permissão' : 'Criar Permissão';
		}
	},
	mounted() {
		this.getGroup();
		this.getItems();
		this.getItemsGroup();
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
					<InputText :modelValue="cicom?.name" v-model="cicom.name" id="name" type="text" class="w-full p-inputtext-sm" :class="{ 'p-invalid': errors?.name }" />
					<small v-if="errors?.name" class="text-red-500 pl-2">{{ errors?.name[0] }}</small>
				</div>
			</div>
			<h5>Dashboard</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.dashboard" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Cadastro</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.cadastro" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Permissões</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.permissoes" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Categorias</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.categorias" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Centro de Custo</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.centrodecusto" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Bancos</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.bancos" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>

			<h5>Clientes</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.clientes" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>

			<h5>Fornecedores</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.fornecedores" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>

			<h5>Emprestimos</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.emprestimos" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Contas a Pagar</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.contaspagar" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Contas a Receber</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.contasreceber" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Movimentacao Financeira</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.movimentacaofinanceira" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Empresa</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.empresa" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Usuário</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.usuario" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			<h5>Alteração das Informações da Empresa</h5>
			<div class="grid">
				<div class="col-12 md:col-4" v-for="option of multiselectValues?.alteracaoempresa" :key="option.id">
					<div class="field-checkbox mb-0" >
						<Checkbox id="checkOption1" name="option" :value="option.slug" v-model="checkboxValue"  />
						<label for="checkOption1">{{option.name}}</label>
					</div>
				</div>
			</div>
			
			
		</template>
	</Card>

	<Message v-if="errors?.cities" severity="error" :closable="false">{{ errors.cities[0] }}</Message>
	<!-- <City 
		:cicom="cicom" 
		:oldCicom="oldCicom"
		:loading="loading" 
		@updateCicom="clearCicom" 
		@addCityBeforeSave="addCityBeforeSave" 
		@changeLoading="changeLoading" 
		v-if="cicom?.enabled"
	/> -->
</template>
