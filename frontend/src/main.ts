import {createApp} from 'vue'
import App from './App.vue'
import router from "./router"
import './style.css'
import echo from './echo'

echo.channel('test')
    .listen('.test-event', (event: unknown) => {
        console.log('[Reverb] event received:', event)
    })

createApp(App)
    .use(router)
    .mount('#app')