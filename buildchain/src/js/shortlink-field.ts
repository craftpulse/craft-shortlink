import { createApp } from 'vue'
import Shortlink from '~/vue/Shortlink.vue'

// App main
const main = async () => {
    const shortlink = createApp(Shortlink)
    const app = shortlink.mount('#shortlink-generator')

    return app
}

// Execute async function
main().then(() => {
    console.log()
})
