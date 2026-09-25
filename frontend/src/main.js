import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

// Uvoz tvog prilagođenog CSS-a
import './assets/style.css'

const app = createApp(App)

app.use(router)
app.mount('#app')