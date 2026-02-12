<script>
import { useLayout } from '@/layout/composables/layout';
import { ref, computed } from 'vue';

import { mapGetters, mapMutations } from 'vuex';
import AppConfig from '@/layout/AppConfig.vue';
import AuthService from '@/service/AuthService.js';
import { useRouter } from 'vue-router';

import { useToast } from 'primevue/usetoast';

const { layoutConfig } = useLayout();

const email = ref('');
const password = ref('');
const checked = ref(false);

const logoUrl = computed(() => {
    return `layout/images/${layoutConfig.darkTheme.value ? 'logo-white' : 'logo-dark'}.svg`;
});
export default {
    name: 'Login',
    components: {
        AppConfig
    },
    setup() {
        return {
            usuario: ref(''),
            password: ref(''),
            contextPath: useLayout(),
            layoutConfig: useLayout(),
            authService: new AuthService(),
            toast: useToast(),
            permissions: ref([]),
            companies: ref({}),
            companiesSearch: ref({}),
            router: useRouter(),
            message: ref([]),
            changed: ref(false),
            appVersion: ref(import.meta.env.VITE_APP_VERSION),
            selectedTipo: ref(''),
            intervalId: null
        };
    },
    data() {
        return {
            error: ref(''),
            loadingLogin: ref(false)
        };
    },
    computed: {
        logoUrl() {
            return `${this.contextPath.contextPath}layout/images/${this.layoutConfig.darkTheme ? 'logo' : 'logo'}.png`;
        },
        ...mapGetters(['isAutenticated', 'usuario'])
    },
    methods: {
        async login() {
            this.loadingLogin = true;
            this.error = '';

            const data = {
                usuario: this.usuario,
                password: this.password
            };

            try {
                const response = await this.authService.login(data);

                localStorage.setItem('app.emp.token', `${response.data.token}`);
                this.setAuthenticated({ isAuthenticated: true });
                console.log('user', response.data.user);
                this.$store.commit('setUsuario', response.data.user);

                if (response.data.user.companies.length == 0) {
                    this.toast.add({
                        severity: 'error',
                        summary: 'Atenção',
                        detail: 'Você não está cadastrado em nenhuma empresa.',
                        life: 3000
                    });
                } else if (response.data.user.companies.length == 1) {
                    this.$store.commit('setCompany', response.data.user.companies[0]);
                    this.$store.commit('setAllPermissions', response.data.user.permissions);
                    this.$store.commit('setAllCompanies', response.data.user.companies);

                    if (response.data.user.permissions.length > 0) {
                        let res = response.data.user.permissions.filter((item) => item.company_id === response.data.user.companies[0]['id']);

                        this.$store.commit('setPermissions', res[0]['permissions']);
                    } else {
                        this.$store.commit('setPermissions', []);
                    }

                    this.router.push({ name: 'dashboard' });
                } else {
                    this.$store.commit('setPermissions', response.data.user.permissions);
                    this.$store.commit('setAllPermissions', response.data.user.permissions);
                    this.$store.commit('setAllCompanies', response.data.user.companies);
                    this.changed = true;
                    this.companies = response.data.user.companies;
                    this.companiesSearch = response.data.user.companies;
                    this.permissions = response.data.user.permissions;
                }

                this.loadingLogin = false;
            } catch (e) {
                if (e?.response?.data?.message) {
                    this.toast.add({
                        severity: 'error',
                        summary: 'Atenção',
                        detail: e?.response?.data?.message,
                        life: 3000
                    });
                }

                this.loadingLogin = false;
            }
        },
        async searchCostcenter(event) {
            try {
				console.log(event.query.toLowerCase());
				console.log(this.companiesSearch);
				console.log(this.companies );
				
				if(this.companiesSearch.length == 0){
					this.companies = this.companiesSearch;
				}else{
					this.companies = this.companiesSearch.filter((company) => company.company.toLowerCase().includes(event.query.toLowerCase()));

				}
				
            } catch (e) {
                console.log(e);
            }
        },
        async changeCompany() {
            if (this.selectedTipo?.id) {
                this.$store.commit('setCompany', this.selectedTipo);

                let res = this.permissions.filter((item) => item.company_id === this.selectedTipo?.id);

                this.$store.commit('setPermissions', res[0]['permissions']);

                this.router.push({ name: 'dashboard' });
            } else {
                this.toast.add({
                    severity: 'error',
                    summary: 'Atenção',
                    detail: 'Selecione uma empresa!',
                    life: 3000
                });
            }
        },
        ...mapMutations(['setAuthenticated', 'setUsuario'])
    }
};
</script>

<template>
    <Toast />

    <Dialog header="Selecione a Empresa" modal class="w-8 mx-8" v-model:visible="changed" :closable="false">
        <div class="grid flex align-content-center flex-wrap">
            <div class="col-12 flex align-content-center flex-wrap md:col-2 lg:col-1">
                <label>Empresa:</label>
            </div>
            <div class="col-12 flex align-content-center flex-wrap sm:col-12 md:col-8">
                <AutoComplete
                    :modelValue="selectedTipo"
                    :dropdown="true"
                    v-model="selectedTipo"
                    :suggestions="companies"
                    placeholder="Informe o nome da Empresa"
                    class="w-full"
                    inputClass="w-full p-inputtext-sm"
                    @complete="searchCostcenter($event)"
                    optionLabel="company"
                />
            </div>
            <div class="col-12 flex align-content-center flex-wrap md:col-12 lg:col-3">
                <Button icon="pi pi-check" label="Entrar" class="p-button-sm p-button-sucess w-full" @click.prevent="changeCompany" />
            </div>
        </div>
    </Dialog>

    <div class="login-container">
        <div class="login-background">
            <div class="login-background-gradient"></div>
            <div class="login-background-pattern"></div>
        </div>
        <div class="login-content flex align-items-center justify-content-center min-h-screen min-w-screen">
            <div class="login-card">
                <div class="login-card-inner">
                    <div class="text-center mb-5">
                        <img src="/images/AGELOGO.png" alt="logo" width="50%" class="login-logo" />
                    </div>
                    <div class="text-center mb-5">
                        <h2 class="login-title">Bem-vindo de volta</h2>
                        <span class="login-subtitle">Faça login para continuar</span>
                    </div>

                    <div class="login-form">
                        <div class="mb-4">
                            <label for="usuario" class="login-label">Usuário</label>
                            <InputText 
                                :modelValue="usuario" 
                                v-model="usuario" 
                                id="usuario" 
                                type="text" 
                                class="w-full login-input" 
                                :disabled="loadingLogin"
                                placeholder="Digite seu usuário"
                            />
                        </div>

                        <div class="mb-4">
                            <label for="password1" class="login-label">Senha</label>
                            <Password 
                                id="password1" 
                                v-model="password" 
                                placeholder="Digite sua senha" 
                                :toggleMask="true" 
                                :feedback="false" 
                                class="w-full login-password" 
                                inputClass="w-full login-input" 
                                :inputStyle="{ padding: '1rem' }"
                            ></Password>
                        </div>

                        <Button 
                            label="Entrar" 
                            class="w-full login-button" 
                            @click.prevent="login" 
                            :loading="loadingLogin"
                            icon="pi pi-sign-in"
                            iconPos="right"
                        ></Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <AppConfig simple /> -->
</template>

<style scoped>
.login-container {
    position: relative;
    min-height: 100vh;
    width: 100%;
    overflow: hidden;
}

.login-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
}

.login-background-gradient {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    background-size: 200% 200%;
    animation: gradientShift 15s ease infinite;
}

.login-background-pattern {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
    opacity: 0.6;
}

@keyframes gradientShift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

.login-content {
    position: relative;
    z-index: 1;
    padding: 2rem;
}

.login-card {
    width: 100%;
    max-width: 450px;
    position: relative;
}

.login-card-inner {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border-radius: 24px;
    padding: 3rem 2.5rem;
    box-shadow: 
        0 20px 60px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.3) inset,
        0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.4);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.login-card-inner:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 25px 70px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.4) inset;
}

.login-logo {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    transition: transform 0.3s ease;
}

.login-logo:hover {
    transform: scale(1.05);
}

.login-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.5px;
}

.login-subtitle {
    color: #718096;
    font-size: 0.95rem;
    font-weight: 500;
}

.login-form {
    margin-top: 2rem;
}

.login-label {
    display: block;
    color: #2d3748;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    letter-spacing: 0.2px;
}

.login-input {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
    font-size: 1rem;
    padding: 0.875rem 1rem;
}

.login-input:hover {
    border-color: #cbd5e0;
}

.login-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.login-password :deep(.p-inputtext) {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s ease;
}

.login-password :deep(.p-inputtext:hover) {
    border-color: #cbd5e0;
}

.login-password :deep(.p-inputtext:focus) {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.login-button {
    border-radius: 12px;
    padding: 0.875rem;
    font-size: 1rem;
    font-weight: 600;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    margin-top: 1rem;
}

.login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.login-button:active {
    transform: translateY(0);
}

.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .login-card-inner {
        padding: 2rem 1.5rem;
    }
    
    .login-title {
        font-size: 1.5rem;
    }
}

/* Dark theme support */
:deep(.p-component) {
    color: inherit;
}
</style>
