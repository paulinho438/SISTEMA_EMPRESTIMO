<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import UsuarioService from '@/service/UsuarioService';
import EmpresaService from '@/service/EmpresaService';
import UtilService from '@/service/UtilService';
import AddressClient from '../address/Address.vue';
import PermissionsService from '@/service/PermissionsService';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
	name: 'cicomForm',
	setup() {
		return {
			route: useRoute(),
			router: useRouter(),
			usuarioService: new UsuarioService(),
			empresaService: new EmpresaService(),
			permissionsService: new PermissionsService(),
			icons: PrimeIcons,
			toast: useToast()
		};
	},
	components: {
		AddressClient,
	},
	data() {
		return {
			usuario: ref({}),
			empresas: ref([
				{ name: 'Australia', code: 'AU' },
				{ name: 'Brazil', code: 'BR' },
				{ name: 'China', code: 'CN' },
				{ name: 'Egypt', code: 'EG' },
				{ name: 'France', code: 'FR' },
				{ name: 'Germany', code: 'DE' },
				{ name: 'India', code: 'IN' },
				{ name: 'Japan', code: 'JP' },
				{ name: 'Spain', code: 'ES' },
				{ name: 'United States', code: 'US' }
			]),
			multiselectValue: ref(null),
			oldClient: ref(null),
			errors: ref([]),
			address: ref({
				id: 1,
				name: 'ok',
				geolocalizacao: '17.23213, 12.455345'
			}),
			loading: ref(false),
			selectedTipoSexo: ref(''),
			sexo: ref([
				{ name: 'Masculino', value: 'M' },
				{ name: 'Feminino', value: 'F' },
			]),
		}
	},
	methods: {
		changeLoading() {
			this.loading = !this.loading;
		},
		getEmpresas() {

			this.empresaService.getAll()
				.then((response) => {
					this.empresas = response.data.data;

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
		getUsuario() {

			if (this.route.params?.id) {
				this.usuario = ref(null);
				this.loading = true;
				this.usuarioService.get(this.route.params.id)
					.then((response) => {
						this.usuario = response.data?.data;
						this.multiselectValue = response.data?.data.empresas;

						if (this.usuario?.sexo == 'M') {
							this.selectedTipoSexo = { name: 'Masculino', value: 'M' };
						} else {
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
			} else {
				this.usuario = ref({});
				this.usuario.address = [];
			}

		},
		back() {
			this.router.push(`/usuarios`);
		},
		changeEnabled(enabled) {
			this.usuario.enabled = enabled;
		},
		save() {
			this.changeLoading();
			this.errors = [];

			if (this.selectedTipoSexo.value == undefined) {
				this.toast.add({
					severity: ToastSeverity.ERROR,
					detail: 'Selecione o Sexo',
					life: 3000
				});

				return false;
			}

			this.usuario.sexo = this.selectedTipoSexo.value;

			this.usuario.empresas = this.multiselectValue;

			this.usuarioService.save(this.usuario)
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
						this.router.push({ name: 'usuarioList' })
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
		this.getEmpresas();
		this.getUsuario();
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
			<Button label="Voltar" class="p-button-outlined p-button-secondary p-button-sm" :icon="icons.ANGLE_LEFT"
				@click.prevent="back" />
			<Button label="Salvar" class="p-button p-button-info p-button-sm ml-3" :icon="icons.SAVE" type="button"
				@click.prevent="save" />
		</div>
	</div>
	<Card>
		<template #content>
			<div class="col-12">
				<div class="p-fluid formgrid grid">
					<div v-if="this.permissionsService.isMaster()" class="field col-12 md:col-12">
						<label for="firstname2">Empresas</label>
						<MultiSelect v-model="multiselectValue" :options="empresas" optionLabel="company"
							placeholder="Selecione as Empresas" :filter="true">
							<template #value="slotProps">
								<div class="inline-flex align-items-center py-1 px-2 bg-primary text-primary border-round mr-2"
									v-for="option of slotProps.value" :key="option.company">

									<div>{{ option.company }}</div>
								</div>
								<template v-if="!slotProps.value || slotProps.value.length === 0">
									<div class="p-1">Selecione as Empresas</div>
								</template>
							</template>
							<template #option="slotProps">
								<div class="flex align-items-center">
									<div>{{ slotProps.option.company }}</div>
								</div>
							</template>
						</MultiSelect>
					</div>
					<div v-if="(!this.route.params?.id)" class="field col-12 md:col-3">
						<label for="firstname2">Login</label>
						<InputText id="firstname2" :modelValue="usuario?.login" v-model="usuario.login"
							type="text" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="firstname2">Nome Completo</label>
						<InputText id="firstname2" :modelValue="usuario?.nome_completo" v-model="usuario.nome_completo"
							type="text" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="firstname2">E-mail</label>
						<InputText id="firstname2" :modelValue="usuario?.email" v-model="usuario.email" type="text" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="lastname2">Sexo</label>
						<Dropdown v-model="selectedTipoSexo" :options="sexo" optionLabel="name" placeholder="Selecione" />
					</div>
					<div class="field col-12 md:col-3">
						<label for="lastname2">Dt. Nascimento</label>
						<InputMask id="inputmask" :modelValue="usuario?.data_nascimento" v-model="usuario.data_nascimento"
							mask="99/99/9999"></InputMask>
					</div>
					<div class="field col-12 md:col-3">
						<label for="state">Telefone</label>
						<InputMask id="inputmask" :modelValue="usuario?.telefone_celular"
							v-model="usuario.telefone_celular" mask="(99) 9999-9999"></InputMask>
					</div>
					
					<div class="field col-12 md:col-3">
						<label for="state">CPF</label>
						<InputMask id="inputmask" :modelValue="usuario?.cpf" v-model="usuario.cpf" mask="999.999.999-99">
						</InputMask>
					</div>
					<div class="field col-12 md:col-3">
						<label for="zip">RG</label>
						<InputMask id="inputmask" :modelValue="usuario?.rg" v-model="usuario.rg" mask="9.999.999">
						</InputMask>
					</div>
					<div class="field col-12 md:col-6">
						<label for="firstname2">Senha</label>
						<InputText id="firstname2" :modelValue="usuario?.password" v-model="usuario.password"
							type="password" />
					</div>
				</div>

			</div>
		</template>
	</Card>
</template>
