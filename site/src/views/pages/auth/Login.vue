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
			router: useRouter(),
            message: ref([]),
            changed: ref(false),
			appVersion: ref(import.meta.env.VITE_APP_VERSION),
            selectedTipo: ref(''),
			intervalId: null,
			
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
		...mapGetters(['isAutenticated'])
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

                if(response.data.user.companies.length == 0){
                    this.toast.add({ 
                        severity: 'error', 
                        summary: 'Atenção', 
                        detail: 'Você não está cadastrado em nenhuma empresa.', 
                        life: 3000 
                        });
                }else if(response.data.user.companies.length == 1){
                    this.$store.commit("setCompany", response.data.user.companies[0]);

					if(response.data.user.permissions.length > 0){
						let res = response.data.user.permissions.filter(item => item.company_id === response.data.user.companies[0]['id'])

						this.$store.commit("setPermissions", res[0]['permissions']);
					}else{
						this.$store.commit("setPermissions", []);
					}

					

				    this.router.push({ name: 'dashboard' });
                }else{
					this.$store.commit("setPermissions", response.data.user.permissions);
                    this.changed = true ;
                    this.companies = response.data.user.companies;
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
        async changeCompany() {

            if(this.selectedTipo?.id){
                this.$store.commit("setCompany", this.selectedTipo);


				let res = this.permissions.filter(item => item.company_id === this.selectedTipo?.id)

				this.$store.commit("setPermissions", res[0]['permissions']);

				this.router.push({ name: 'dashboard' });

            }else{
                this.toast.add({ 
                        severity: 'error', 
                        summary: 'Atenção', 
                        detail: 'Selecione uma empresa!', 
                        life: 3000 
                        });
            }
		},
		...mapMutations(['setAuthenticated']),
        
	},
    
};
</script>

<template>
    <SvgBackground />
    <Toast />

    <Dialog header="Selecione a Empresa" modal class="w-8 mx-8" v-model:visible="changed" :closable="false">
		<div class="grid flex align-content-center flex-wrap">
			<div class="col-12 flex align-content-center flex-wrap md:col-2 lg:col-1 ">
				<label>Empresa:</label>
			</div>
			<div class="col-12 flex align-content-center flex-wrap sm:col-12 md:col-8">
				<Dropdown v-model="selectedTipo" class="w-full" :options="companies" optionLabel="company" placeholder="Selecione a empresa" />
			</div>
			<div class="col-12 flex align-content-center flex-wrap md:col-12 lg:col-3">
				<Button icon="pi pi-check" label="Entrar" class="p-button-sm p-button-sucess w-full" @click.prevent="changeCompany" />
			</div>
		</div>
	</Dialog>
    
    <div class="z-1 surface-ground flex align-items-center justify-content-center min-h-screen min-w-screen overflow-hidden">
        <div class="z-1 flex flex-column align-items-center justify-content-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class=" z-1 w-full surface-card py-8 px-5 sm:px-8" style="border-radius: 53px">
                    <div class="text-center mb-5">
                        <div class="text-900 text-3xl font-medium mb-3">SISTEMA EMPRESTIMOS!</div>
                        <span class="text-600 font-medium">Faça login para continuar</span>
                    </div>

                    <div class="z-1">
                        <label for="usuario" class="block text-900 text-xl font-medium mb-2">Usuario</label>
						<InputText :modelValue="usuario" v-model="usuario" id="usuario" type="text" class="w-full md:w-30rem mb-5" :disabled="loadingLogin" />

                        <label for="password1" class="block text-900 font-medium text-xl mb-2">SENHA</label>
                        <Password id="password1" v-model="password" placeholder="Senha" :toggleMask="true" :feedback="false" class="w-full mb-3" inputClass="w-full" :inputStyle="{ padding: '1rem' }"></Password>

                        
                        <Button label="Entrar" class="w-full p-3 text-xl" @click.prevent="login" :loading="loadingLogin"></Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- <AppConfig simple /> -->

</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
