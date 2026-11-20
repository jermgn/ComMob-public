<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ShieldCheck, AlertCircle, ArrowLeft } from 'lucide-vue-next'
import { verifyLoginRequest } from '@/services/authService'
import { useAuthStore } from '@/stores/auth'
import { useUserProfileStore } from '@/stores/userProfile'
import iconSrc from '@/assets/images/commob_icon.svg'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useUserProfileStore()

const digits = ref<string[]>(['', '', '', '', '', ''])
const error = ref('')
const loading = ref(false)
const inputs = ref<HTMLInputElement[]>([])

const code = computed(() => digits.value.join(''))
const isComplete = computed(() => code.value.length === 6 && digits.value.every(d => d !== ''))

onMounted(() => {
  if (!authStore.pendingEmail) {
    router.replace('/login')
    return
  }
  inputs.value[0]?.focus()
})

function onInput(index: number, event: Event) {
  const val = (event.target as HTMLInputElement).value
  const digit = val.replace(/\D/g, '').slice(-1)
  digits.value[index] = digit
  if (digit && index < 5) {
    inputs.value[index + 1]?.focus()
  }
}

function onKeydown(index: number, event: KeyboardEvent) {
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
    digits.value[index - 1] = ''
    inputs.value[index - 1]?.focus()
  }
}

function onPaste(event: ClipboardEvent) {
  const text = event.clipboardData?.getData('text') ?? ''
  const onlyDigits = text.replace(/\D/g, '').slice(0, 6)
  if (onlyDigits.length === 6) {
    onlyDigits.split('').forEach((d, i) => { digits.value[i] = d })
    inputs.value[5]?.focus()
    event.preventDefault()
  }
}

async function handleSubmit() {
  if (!isComplete.value || loading.value) return
  error.value = ''
  loading.value = true
  try {
    const data = await verifyLoginRequest(authStore.pendingEmail!, code.value)
    authStore.setAuth(data.token, data.user)
    authStore.setPendingEmail(null)
    await profileStore.fetchProfile()
    router.push('/phrase')
  } catch (e: any) {
    error.value = e.message || 'Code invalide ou expiré'
    digits.value = ['', '', '', '', '', '']
    inputs.value[0]?.focus()
  } finally {
    loading.value = false
  }
}

function goBack() {
  authStore.setPendingEmail(null)
  router.push('/login')
}
</script>

<template>
  <AuthLayout>
    <div class="flex flex-col gap-6">
      <!-- Icon + title -->
      <div class="flex flex-col items-center gap-3">
        <img :src="iconSrc" alt="ComMob" class="h-16 w-16" />
        <h1 class="text-2xl font-bold" style="color: #252526;">Vérification en deux étapes</h1>
        <p class="text-sm text-center leading-relaxed" style="color: #6B7280;">
          Un code à 6 chiffres a été envoyé à<br />
          <span class="font-semibold" style="color: #252526;">{{ authStore.pendingEmail }}</span>
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="flex flex-col gap-5">
        <!-- 6 digit inputs -->
        <div class="flex justify-center gap-2" @paste.prevent="onPaste">
          <Input
            v-for="i in 6"
            :key="i"
            :ref="(el) => { if (el) inputs[i - 1] = (el as any).$el ?? el }"
            v-model="digits[i - 1]"
            type="text"
            inputmode="numeric"
            maxlength="1"
            class="h-14 w-12 text-center text-xl font-bold p-0"
            :disabled="loading"
            autocomplete="one-time-code"
            @input="onInput(i - 1, $event)"
            @keydown="onKeydown(i - 1, $event)"
          />
        </div>

        <Alert v-if="error" variant="destructive" role="alert">
          <AlertCircle :size="16" />
          <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <Button
          type="submit"
          class="h-12 text-base font-semibold w-full text-white"
          style="background-color: #2E76F0;"
          :disabled="!isComplete || loading"
        >
          <ShieldCheck v-if="!loading" :size="18" class="mr-2" />
          {{ loading ? 'Vérification...' : 'Confirmer le code' }}
        </Button>
      </form>

      <button
        class="flex items-center justify-center gap-1.5 text-sm mx-auto"
        style="color: #6B7280;"
        @click="goBack"
      >
        <ArrowLeft :size="14" />
        Retour à la connexion
      </button>
    </div>
  </AuthLayout>
</template>
