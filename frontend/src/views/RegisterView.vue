<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { UserPlus, AlertCircle, Eye, EyeOff } from 'lucide-vue-next'
import { registerRequest } from '@/services/authService'
import { useAuthStore } from '@/stores/auth'
import { useUserProfileStore } from '@/stores/userProfile'
import iconSrc from '@/assets/images/commob_icon.svg'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useUserProfileStore()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirm = ref(false)
const error = ref('')
const loading = ref(false)

async function handleSubmit() {
  error.value = ''
  if (!email.value.trim() || !password.value || !confirmPassword.value) {
    error.value = 'Veuillez remplir tous les champs'
    return
  }
  if (password.value.length < 8) {
    error.value = 'Le mot de passe doit contenir au moins 8 caractères'
    return
  }
  if (password.value !== confirmPassword.value) {
    error.value = 'Les mots de passe ne correspondent pas'
    return
  }
  loading.value = true
  try {
    const data = await registerRequest(email.value.trim(), password.value)
    authStore.setAuth(data.token, data.user)
    await profileStore.fetchProfile()
    router.push('/phrase')
  } catch (e: any) {
    error.value = e.message || "Erreur lors de l'inscription"
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
        <h1 class="text-2xl font-bold" style="color: #252526;">Créer un compte</h1>
        <p class="text-sm text-center leading-relaxed" style="color: #6B7280;">
          Rejoignez ComMob et commencez à communiquer.
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
              placeholder="Minimum 8 caractères"
              autocomplete="new-password"
              :disabled="loading"
              class="h-12 text-base pr-10"
            />
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              :aria-label="showPassword ? 'Masquer' : 'Afficher'"
              @click="showPassword = !showPassword"
            >
              <EyeOff v-if="showPassword" :size="18" />
              <Eye v-else :size="18" />
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <Label for="confirmPassword" style="color: #252526;">Confirmer le mot de passe</Label>
          <div class="relative">
            <Input
              id="confirmPassword"
              v-model="confirmPassword"
              :type="showConfirm ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="new-password"
              :disabled="loading"
              class="h-12 text-base pr-10"
            />
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
              :aria-label="showConfirm ? 'Masquer' : 'Afficher'"
              @click="showConfirm = !showConfirm"
            >
              <EyeOff v-if="showConfirm" :size="18" />
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
          <UserPlus v-if="!loading" :size="18" class="mr-2" />
          {{ loading ? 'Création du compte...' : 'Créer mon compte' }}
        </Button>
      </form>

      <p class="text-sm text-center" style="color: #6B7280;">
        Déjà un compte ?
        <router-link to="/login" class="font-semibold" style="color: #2E76F0;">
          Se connecter
        </router-link>
      </p>
    </div>
  </AuthLayout>
</template>
