import { createApp } from 'vue'
import ShortLink from '~/vue/ShortLink.vue'

// App main
const main = async () => {
    const shortlink = createApp(ShortLink)
    const app = shortlink.mount('#shortlink-generator')

    return app
}

// Execute async function
main().then(() => {
    console.log()
})
