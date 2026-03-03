<script>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import EmpresasService from '@/service/EmpresasService';
import UsuarioService from '@/service/UsuarioService';
import PlanosService from '@/service/PlanosService';
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
            planosService: new PlanosService(),
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
            planos: ref([]),
            oldempresas: ref(null),
            errors: ref([]),
            address: ref({
                id: 1,
                name: 'ok',
                geolocalizacao: '17.23213, 12.455345'
            }),
            loading: ref(false),
            selectedAtivo: ref(''),
            selectedPlano: ref(''),
            ativo: ref([
                { name: 'Ativada', value: 1 },
                { name: 'Inativo', value: 0 }
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

                        if (this.empresas?.ativo == 1) {
                            this.selectedAtivo = { name: 'Ativada', value: 1 };
                        } else {
                            this.selectedAtivo = { name: 'Inativo', value: 0 };
                        }

						if (this.empresas?.plano_id) {
							this.selectedPlano = this.planos.find((plano) => plano.id == this.empresas.plano_id);
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
                this.empresas = ref({});
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
        getPlanos() {
            this.planos = ref(null);
            this.loading = true;
            this.planosService
                .getAll()
                .then((response) => {
                    this.planos = response.data;
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
        back() {
            this.router.push(`/empresas`);
        },
        changeEnabled(enabled) {
            this.empresas.enabled = enabled;
        },
        save() {
            this.changeLoading();
            this.errors = [];

            this.empresas.ativo = this.selectedAtivo.value;

			this.empresas.plano_id = this.selectedPlano.id;

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
		this.getPlanos();
        this.getempresas();
        this.getUsuariosDaEmpresa();
    }
};
</script>

<template>
    <Toast />
    <LoadingComponent :loading="loading" />
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
                        <label for="razao_social">Razão Social (para contrato)</label>
                        <InputText id="razao_social" :modelValue="empresas?.razao_social" v-model="empresas.razao_social" type="text" placeholder="Razão social completa" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="cnpj">CNPJ</label>
                        <InputText id="cnpj" :modelValue="empresas?.cnpj" v-model="empresas.cnpj" type="text" placeholder="00.000.000/0001-00" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="firstname2">E-mail</label>
                        <InputText id="firstname2" :modelValue="empresas?.email" v-model="empresas.email" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="lastname2">Plano</label>
                        <Dropdown v-model="selectedPlano" :options="planos" optionLabel="nome" placeholder="Selecione" />
                    </div>
					<div v-if="this.empresas?.id" class="field col-12 md:col-3">
                        <label for="lastname2">Status da Empresa</label>
                        <Dropdown v-model="selectedAtivo" :options="ativo" optionLabel="name" placeholder="Selecione" />
                    </div>
                    <div v-if="this.empresas?.id" class="field col-12 md:col-3">
                        <label for="firstname2">Motivo Inativo</label>
                        <InputText id="firstname2" :modelValue="empresas?.motivo_inativo" v-model="empresas.motivo_inativo" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="firstname2">URL Integração WhatsApp (Geral)</label>
                        <InputText id="firstname2" :modelValue="empresas?.whatsapp" v-model="empresas.whatsapp" type="text" placeholder="Usado para: Renovações, Transferências, etc." />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="whatsapp_cobranca">URL Integração WhatsApp (Cobrança)</label>
                        <InputText id="whatsapp_cobranca" :modelValue="empresas?.whatsapp_cobranca" v-model="empresas.whatsapp_cobranca" type="text" placeholder="Usado exclusivamente para cobranças" />
                    </div>
                    <div class="col-12 mt-3">
                        <h6>Dados para Contrato de Mútuo</h6>
                    </div>
                    <div class="field col-12 md:col-4">
                        <label for="endereco">Endereço</label>
                        <InputText id="endereco" :modelValue="empresas?.endereco" v-model="empresas.endereco" type="text" placeholder="Rua, número, complemento" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="cidade">Cidade</label>
                        <InputText id="cidade" :modelValue="empresas?.cidade" v-model="empresas.cidade" type="text" />
                    </div>
                    <div class="field col-12 md:col-1">
                        <label for="estado">UF</label>
                        <InputText id="estado" :modelValue="empresas?.estado" v-model="empresas.estado" type="text" maxlength="2" placeholder="GO" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="cep">CEP</label>
                        <InputText id="cep" :modelValue="empresas?.cep" v-model="empresas.cep" type="text" placeholder="00000-000" />
                    </div>
                    <div class="col-12 mt-2">
                        <h6 class="text-sm">Representante Mutuante</h6>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="representante_nome">Nome Completo</label>
                        <InputText id="representante_nome" :modelValue="empresas?.representante_nome" v-model="empresas.representante_nome" type="text" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="representante_cpf">CPF</label>
                        <InputText id="representante_cpf" :modelValue="empresas?.representante_cpf" v-model="empresas.representante_cpf" type="text" placeholder="000.000.000-00" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="representante_rg">RG</label>
                        <InputText id="representante_rg" :modelValue="empresas?.representante_rg" v-model="empresas.representante_rg" type="text" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="representante_orgao_emissor">Órgão Emissor</label>
                        <InputText id="representante_orgao_emissor" :modelValue="empresas?.representante_orgao_emissor" v-model="empresas.representante_orgao_emissor" type="text" placeholder="SSP" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="representante_cargo">Cargo</label>
                        <InputText id="representante_cargo" :modelValue="empresas?.representante_cargo" v-model="empresas.representante_cargo" type="text" placeholder="Diretor" />
                    </div>
                    <div class="col-12 mt-2">
                        <h6 class="text-sm">Dados Bancários (para recebimento)</h6>
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="banco_nome">Banco</label>
                        <InputText id="banco_nome" :modelValue="empresas?.banco_nome" v-model="empresas.banco_nome" type="text" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="banco_agencia">Agência</label>
                        <InputText id="banco_agencia" :modelValue="empresas?.banco_agencia" v-model="empresas.banco_agencia" type="text" />
                    </div>
                    <div class="field col-12 md:col-2">
                        <label for="banco_conta">Conta</label>
                        <InputText id="banco_conta" :modelValue="empresas?.banco_conta" v-model="empresas.banco_conta" type="text" />
                    </div>
                    <div class="field col-12 md:col-3">
                        <label for="banco_pix">Chave Pix</label>
                        <InputText id="banco_pix" :modelValue="empresas?.banco_pix" v-model="empresas.banco_pix" type="text" />
                    </div>
                </div>
            </div>

            <Usuarios
                v-if="this.empresas?.id"
                :usuarios="this.usuarios"
                :address="this.empresas?.address"
                :oldCicom="this.oldempresas"
                :loading="loading"
                @updateCicom="clearCicom"
                @addCityBeforeSave="addCityBeforeSave"
                @changeLoading="changeLoading"
            />
        </template>
    </Card>
</template>
