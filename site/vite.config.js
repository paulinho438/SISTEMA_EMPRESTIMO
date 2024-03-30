import { fileURLToPath, URL } from 'node:url';
const CopyWebpackPlugin = require('copy-webpack-plugin');


import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// https://vitejs.dev/config/
export default defineConfig(() => {
    return {
        plugins: [
            vue(),
            new CopyWebpackPlugin({
                patterns: [
                  { from: '.htaccess', to: '' }, // Copiar o .htaccess para a raiz do diretório de saída (dist)
                ],
            }),
        ],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url))
            }
        }
    };
});

