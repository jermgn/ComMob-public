<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { LogIn, AlertCircle, Eye, EyeOff } from 'lucide-vue-next'
import { loginRequest } from '@/services/authService'
import { useAuthStore } from '@/stores/auth'
import { useUserProfileStore } from '@/stores/userProfile'
import iconSrc from '@/assets/images/commob_icon.svg'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useUserProfileStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

async function handleSubmit() {
  error.value = ''
  if (!email.value.trim() || !password.value) {
    error.value = 'Veuillez remplir tous les champs'
    return
  }
  loading.value = true
  try {
    const data = await loginRequest(email.value.trim(), password.value)
    if ('requires_2fa' in data && data.requires_2fa) {
      authStore.setPendingEmail(email.value.trim())
      router.push('/verify-2fa')
    } else {
      authStore.setAuth(data.token, data.user)
      await profileStore.fetchProfile()
      router.push('/phrase')
    }
  } catch (e: any) {
    error.value = e.message || 'Identifiants incorrects'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="flex flex-col gap-6">
      <!-- Icon + title -->
      <div class="flex flex-col items-center gap-3">
        <img :src="iconSrc" alt="ComMob" class="h-16 w-16" />
        <h1 class="text-2xl font-bold" style="color: #252526;">ComMob Web v3.0</h1>
        <p class="text-sm text-center leading-relaxed" style="color: #6B7280;">
          Connectez-vous à votre compte.
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <Label for="email" style="color: #252526;">Adresse e-mail</Label>
          <Input
            id="email"
            v-model="email"
            type="email"
            placeholder="vous@exemple.fr"
            autocomplete="email"
            :disabled="loading"
            class="h-12 text-base"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <Label for="password" style="color: #252526;">Mot de passe</Label>
          <div class="relative">
            <Input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              :disabled="loading"
              class="h-12 text-base pr-10"
            />
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
              @click="showPassword = !showPassword"
            >
              <EyeOff v-if="showPassword" :size="18" />
              <Eye v-else :size="18" />
            </button>
          </div>
        </div>

        <Alert v-if="error" variant="destructive" role="alert">
          <AlertCircle :size="16" />
          <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <Button
          type="submit"
          class="h-12 text-base font-semibold w-full text-white"
          style="background-color: #2E76F0;"
          :disabled="loading"
        >
          <LogIn v-if="!loading" :size="18" class="mr-2" />
          {{ loading ? 'Connexion...' : 'Se connecter' }}
        </Button>
      </form>

      <p class="text-sm text-center" style="color: #6B7280;">
        Pas encore de compte ?
        <router-link to="/register" class="font-semibold" style="color: #2E76F0;">
          Créer un compte
        </router-link>
      </p>
    </div>
  </AuthLayout>
</template>
