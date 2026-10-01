import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css'
import { useAuthCarhrisStore } from './stores/authCarhris.js'
import { useAuthAdminStore } from './stores/authAdmin.js'

const app = createApp(App)

app.use(createPinia())

const auth = useAuthCarhrisStore()
await auth.fetchMe()

const authAdmin = useAuthAdminStore()
await authAdmin.fetchMe()

app.use(router)
app.mount('#app')
