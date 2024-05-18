<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import { useRouter } from 'vue-router';
import AuthService from '@/service/AuthService';
import { ToastSeverity } from 'primevue/api';

const { layoutConfig, onMenuToggle, contextPath } = useLayout();

const outsideClickListener = ref(null);
const topbarMenuActive = ref(false);
const router = useRouter();
const authService = new AuthService();

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
</script>

<template>
	<div class="layout-topbar">
		<router-link to="/" class="layout-topbar-logo">
            <span>{{$store?.getters?.isCompany?.company}}</span>
        </router-link>

		<button class="p-link layout-menu-button layout-topbar-button" @click="onMenuToggle()">
			<i class="pi pi-bars"></i>
		</button>

		<button class="p-link layout-topbar-menu-button layout-topbar-button" @click="onTopBarMenuButton()">
			<i class="pi pi-ellipsis-v"></i>
		</button>

		<div class="layout-topbar-menu" :class="topbarMenuClasses">
			<button @click="logout()" class="p-link layout-topbar-button">
				<i class="pi pi-sign-out"></i>
				<span>Logout</span>
			</button>
		</div>
	</div>
</template>

<style lang="scss" scoped></style>
