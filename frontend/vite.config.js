import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite' // importing latest Vite plugin

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss() // activating Tailwind v4 engine directly in Vite
  ],
  server: {
    port: 3000,
    strictPort: true,
    host: true,
    watch: {
      usePolling: true// this forces Vite to detect changes inside Docker containers
    }
  }
})