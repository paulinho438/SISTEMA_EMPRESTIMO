<script setup>
import { ref, onMounted } from 'vue';
import { useLayout } from '@/layout/composables/layout';
import FullScreenLoading from "@/components/FullScreenLoading.vue";

// Importa funções e configurações do layout
const { changeThemeSettings, setScale, layoutConfig } = useLayout();

// Define a variável reativa para controle do loading
const loading = ref(false);

// Função para alterar o tema
const onChangeTheme = (theme, mode) => {
    const elementId = 'theme-css';
    const linkElement = document.getElementById(elementId);
    const cloneLinkElement = linkElement.cloneNode(true);
    const newThemeUrl = linkElement.getAttribute('href').replace(layoutConfig.theme.value, theme);
    cloneLinkElement.setAttribute('id', elementId + '-clone');
    cloneLinkElement.setAttribute('href', newThemeUrl);
    cloneLinkElement.addEventListener('load', () => {
        linkElement.remove();
        cloneLinkElement.setAttribute('id', elementId);
        changeThemeSettings(theme, mode === 'dark');
    });
    linkElement.parentNode.insertBefore(cloneLinkElement, linkElement.nextSibling);
};

// Controla o carregamento e tema ao montar o componente
onMounted(() => {
    loading.value = true; // Ativa o loading
    setTimeout(() => {
        document.documentElement.style.fontSize = 12 + 'px';
        onChangeTheme('bootstrap4-light-blue', 'light');
        loading.value = false; // Desativa o loading após a operação
    }, 2000); // Simula um carregamento com timeout
});
</script>

<template>
    <FullScreenLoading :isLoading="loading" />
    <router-view />
</template>

<style scoped></style>
