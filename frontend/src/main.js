import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css'
import { useAuthCarhrisStore } from './stores/authCarhris.js'
import { useAuthAdminStore } from './stores/authAdmin.js'

const app = createApp(App)

app.use(createPinia())
app.use(router)

const auth = useAuthCarhrisStore()
await auth.fetchMe()

const authAdmin = useAuthAdminStore()
await authAdmin.fetchMe()

app.mount('#app')
