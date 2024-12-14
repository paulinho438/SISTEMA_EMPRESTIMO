<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import { useRouter } from 'vue-router';
import AuthService from '@/service/AuthService';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import ClientService from '@/service/ClientService';

import { ToastSeverity } from 'primevue/api';

const { layoutConfig, onMenuToggle, contextPath } = useLayout();

const clientService = new ClientService();

const outsideClickListener = ref(null);
const topbarMenuActive = ref(false);
const router = useRouter();
const authService = new AuthService();
const toast = useToast();
const confirmPopup = useConfirm();

onMounted(() => {
    bindOutsideClickListener();
});

onBeforeUnmount(() => {
    unbindOutsideClickListener();
});

const logoUrl = computed(() => {
    return `${contextPath}layout/images/${layoutConfig.darkTheme.value ? 'logo' : 'logo'}.png`;
});

const onTopBarMenuButton = () => {
    topbarMenuActive.value = !topbarMenuActive.value;
};
const topbarMenuClasses = computed(() => {
    return {
        'layout-topbar-menu-mobile-active': topbarMenuActive.value
    };
});

const bindOutsideClickListener = () => {
    if (!outsideClickListener.value) {
        outsideClickListener.value = (event) => {
            if (isOutsideClicked(event)) {
                topbarMenuActive.value = false;
            }
        };
        document.addEventListener('click', outsideClickListener.value);
    }
};
const unbindOutsideClickListener = () => {
    if (outsideClickListener.value) {
        document.removeEventListener('click', outsideClickListener);
        outsideClickListener.value = null;
    }
};
const isOutsideClicked = (event) => {
    if (!topbarMenuActive.value) return;

    const sidebarEl = document.querySelector('.layout-topbar-menu');
    const topbarEl = document.querySelector('.layout-topbar-menu-button');

    return !(sidebarEl.isSameNode(event.target) || sidebarEl.contains(event.target) || topbarEl.isSameNode(event.target) || topbarEl.contains(event.target));
};

const logout = async () => {
    try {
        const response = authService.logout();
        localStorage.removeItem('app.190.token');
        localStorage.removeItem('changed');
        router.push({ name: 'login' });
    } catch (e) {
        console.log(e.message);
    }
};

const confirm = (event) => {
    confirmPopup.require({
        target: event.target,
        message: 'Tem certeza que deseja cobrar todos os clientes?',
        icon: 'pi pi-exclamation-triangle',
		acceptLabel: 'Sim',
        rejectLabel: 'Não',
        accept: () => {
			cobrarTodosClientes();
        },
        reject: () => {
            toast.add({ severity: 'info', summary: 'Cancelar', detail: 'Rotina não iniciada!', life: 3000 });
        }
    });
};

const cobrarTodosClientes = async (event) => {

	try {
		
		await clientService.cobrarClientes();

		toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Clientes cobrados com sucesso!', life: 3000 });

	} catch (e) {
		toast.add({ severity: 'error', summary: 'Erro', detail: 'Rotina já foi iniciada!', life: 3000 });
	}

};


</script>

<template>
    <div class="layout-topbar">
        <router-link to="/" class="layout-topbar-logo">
            <span>{{ $store?.getters?.isCompany?.company }}</span>
        </router-link>

        <button class="p-link layout-menu-button layout-topbar-button" @click="onMenuToggle()">
            <i class="pi pi-bars"></i>
        </button>

        <button class="p-link layout-topbar-menu-button layout-topbar-button" @click="onTopBarMenuButton()">
            <button @click="logout()" class="p-link layout-topbar-button" style="margin-left: 10px">
                <i class="pi pi-sign-out"></i>
                <span>Logout</span>
            </button>
        </button>
		<ConfirmPopup></ConfirmPopup>

        <div style="display: flex; flex: 1; flex-direction: row; justify-content: end">
            <button ref="popup" @click="confirm($event)" class="p-link hidden-on-small">
                <i class="pi pi-whatsapp" style="margin-right: 10px"></i>
                <span>Cobrar Todos Clientes</span>
            </button>
            <div class="hidden-on-small">
            <button @click="logout()" class="p-link layout-topbar-button hidden-on-small" style="margin-left: 10px">
                <i class="pi pi-sign-out"></i>
                <span>Logout</span>
            </button>
        </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.hidden-on-small {
    display: block;
}

@media (max-width: 991px) {
    .hidden-on-small {
        display: none;
    }
}
</style>
