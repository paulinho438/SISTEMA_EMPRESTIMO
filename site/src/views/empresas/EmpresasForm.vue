<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import EmpresasService from '@/service/EmpresasService';
import UsuarioService from '@/service/UsuarioService';
import UtilService from '@/service/UtilService';
import Usuarios from './components/Usuarios.vue';
import { ToastSeverity, PrimeIcons } from 'primevue/api';

import LoadingComponent from '../../components/Loading.vue';
import { useToast } from 'primevue/usetoast';

export default {
    name: 'cicomForm',
    setup() {
        return {
            route: useRoute(),
            router: useRouter(),
            empresasService: new EmpresasService(),
            usuarioService: new UsuarioService(),
            icons: PrimeIcons,
            toast: useToast()
        };
    },
    components: {
        Usuarios
    },
    data() {
        return {
            empresas: ref({}),
            usuarios: ref([]),
            oldempresas: ref(null),
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
                { name: 'Feminino', value: 'F' }
            ])
        };
    },
    methods: {
        changeLoading() {
            this.loading = !this.loading;
        },
        getempresas() {
			this.loading = true;
            if (this.route.params?.id) {
                this.empresas = ref(null);
                this.loading = true;
                this.empresasService
                    .get(this.route.params.id)
                    .then((response) => {
                        this.empresas = response.data;
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
                this.empresas = ref({});
                this.empresas.address = [];
            }
        },
        getUsuariosDaEmpresa() {
            if (this.route.params?.id) {
                this.usuarios = ref(null);
                this.loading = true;
                this.usuarioService
                    .getAllUsuariosCompany(this.route.params.id)
                    .then((response) => {
						console.log(response.data);
                        this.usuarios = response.data.data;
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
                this.usuarios = ref({});
            }
        },
        back() {
            this.router.push(`/empresas`);
        },
        changeEnabled(enabled) {
            this.empresas.enabled = enabled;
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

            this.empresas.sexo = this.selectedTipoSexo.value;

            this.empresasService
                .save(this.empresas)
                .then((response) => {
                    if (undefined != response.data.data) {
                        this.empresas = response.data.data;
                    }

                    this.toast.add({
                        severity: ToastSeverity.SUCCESS,
                        detail: this.empresas?.id ? 'Dados alterados com sucesso!' : 'Dados inseridos com sucesso!',
                        life: 3000
                    });

                    setTimeout(() => {
                        this.router.push({ name: 'empresasList' });
                    }, 1200);
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

        clearempresas() {
            this.loading = true;
        },
        addCityBeforeSave(city) {
            // this.empresas.cities.push(city);
            this.changeLoading();
        },
        clearCicom() {
            this.loading = true;
        }
    },
    computed: {
        title() {
            return this.route.params?.id ? 'Editar Empresa' : 'Criar Empresa';
        }
    },
    mounted() {
        this.getempresas();
		this.getUsuariosDaEmpresa();
    }
};
</script>

<template>
    <Toast />
    <LoadingComponent :loading="true" />
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
                        <label for="firstname2">Nome da Empresa</label>
                        <InputText id="firstname2" :modelValue="empresas?.company" v-model="empresas.company" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="firstname2">E-mail</label>
                        <InputText id="firstname2" :modelValue="empresas?.email" v-model="empresas.email" type="text" />
                    </div>
					<div class="field col-12 md:col-3">
                        <label for="firstname2">URL Integração WhatsApp</label>
                        <InputText id="firstname2" :modelValue="empresas?.whatsapp" v-model="empresas.whatsapp" type="text" />
                    </div>
                </div>
            </div>

            <Usuarios :usuarios="this.usuarios" :address="this.empresas?.address" :oldCicom="this.oldempresas" :loading="loading" @updateCicom="clearCicom" @addCityBeforeSave="addCityBeforeSave" @changeLoading="changeLoading" v-if="true" />
        </template>
    </Card>
</template>
