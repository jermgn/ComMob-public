import { createRouter, createWebHistory } from "vue-router"
import { useAuthStore } from "@/stores/auth"
import PhraseGenerationView from "@/views/PhraseGenerationView.vue"
import ResponseModeView from "@/views/ResponseModeView.vue"
import DialogueCreationView from "@/views/DialogueCreationView.vue"
import SettingsView from "@/views/SettingsView.vue"

const LoginView = () => import("@/views/LoginView.vue")
const RegisterView = () => import("@/views/RegisterView.vue")
const Verify2faView = () => import("@/views/Verify2faView.vue")

const routes = [
  { path: "/login", name: "login", component: LoginView, meta: { requiresAuth: false } },
  { path: "/register", name: "register", component: RegisterView, meta: { requiresAuth: false } },
  { path: "/verify-2fa", name: "verify-2fa", component: Verify2faView, meta: { requiresAuth: false } },
  { path: "/", redirect: "/phrase" },
  { path: "/phrase", name: "phrase", component: PhraseGenerationView, meta: { requiresAuth: true } },
  { path: "/reponse", name: "reponse", component: ResponseModeView, meta: { requiresAuth: true } },
  { path: "/dialogues", name: "dialogues", component: DialogueCreationView, meta: { requiresAuth: true } },
  { path: "/settings", name: "settings", component: SettingsView, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth !== false && !authStore.isAuthenticated) {
    next({ name: "login" })
    return
  }

  if (to.meta.requiresAuth === false && authStore.isAuthenticated) {
    next({ name: "phrase" })
    return
  }

  next()
})

export default router
