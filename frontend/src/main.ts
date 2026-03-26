import './assets/main.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useUserStore } from './stores/userStore'

const pinia = createPinia()
const app = createApp(App).use(pinia);

async function init() {
    const userStore = useUserStore();
    const res = await userStore.fetchUser();

    app.use(router);

    app.mount("#app");
}

init();
