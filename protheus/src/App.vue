<script setup>
import {  onMounted } from 'vue';
import { useLayout } from '@/layout/composables/layout';

const { changeThemeSettings, setScale, layoutConfig } = useLayout();

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





onMounted(() => {
    
    document.documentElement.style.fontSize = 12 + 'px';

    onChangeTheme('bootstrap4-light-blue', 'light');
    
});
</script>



<template>
    <router-view />
</template>

<style scoped></style>


