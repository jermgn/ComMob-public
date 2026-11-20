import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/index.css'

import { useAccessibilityStore } from './stores/accessibility'
import { useUserProfileStore } from './stores/userProfile'
import { useDialoguesStore } from './stores/dialogues'
import { useContextsStore } from './stores/contexts'
import { useAuthStore } from './stores/auth'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

const authStore = useAuthStore()
const accessibilityStore = useAccessibilityStore()
const userProfileStore = useUserProfileStore()
const dialoguesStore = useDialoguesStore()
const contextsStore = useContextsStore()

// Charger auth + accessibilité depuis localStorage
authStore.loadFromLocalStorage()
accessibilityStore.loadFromLocalStorage()
contextsStore.loadFromLocalStorage()

// Si authentifié, charger le profil et les dialogues depuis le backend
if (authStore.isAuthenticated) {
  userProfileStore.fetchProfile()
  dialoguesStore.fetchDialogues()
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Tab') {
    accessibilityStore.setKeyboardOnly(true)
  }
})

document.addEventListener('mousedown', () => {
  accessibilityStore.setKeyboardOnly(false)
})
