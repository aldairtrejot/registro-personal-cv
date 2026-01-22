import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  server: {
    cors: true,
    host: process.env.VITE_DEV_SERVER_HOST,
  },

  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
      ],
      refresh: true,
    }),

    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],

  resolve: {
    alias: {
      // Vue compiler build
      vue: 'vue/dist/vue.esm-bundler.js',

      // ✅ Alias robustos (con path real del sistema)
      '@': path.resolve(__dirname, 'resources/js'),
      '@axios': path.resolve(__dirname, 'resources/js/components/axios.js'),
      '@assets': path.resolve(__dirname, 'resources/js/asset'),
      '@helpers': path.resolve(__dirname, 'resources/js/helpers'),
      '@components': path.resolve(__dirname, 'resources/js/components'),
    },
  },
})
