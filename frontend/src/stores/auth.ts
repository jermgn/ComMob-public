import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { AuthUser } from '@/types/auth'

function parseJwtExp(token: string): number | null {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]))
    return payload.exp ?? null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<AuthUser | null>(null)
  const pendingEmail = ref<string | null>(null)
  let expirationTimer: ReturnType<typeof setTimeout> | null = null

  const isAuthenticated = computed(() => token.value !== null)

  function setPendingEmail(email: string | null) {
    pendingEmail.value = email
  }

  function startExpirationTimer() {
    stopExpirationTimer()
    if (!token.value) return

    const exp = parseJwtExp(token.value)
    if (!exp) return

    const msLeft = exp * 1000 - Date.now()
    if (msLeft <= 0) {
      logout()
      return
    }

    expirationTimer = setTimeout(() => {
      logout()
      window.location.href = '/login'
    }, msLeft)
  }

  function stopExpirationTimer() {
    if (expirationTimer) {
      clearTimeout(expirationTimer)
      expirationTimer = null
    }
  }

  function setAuth(newToken: string, newUser: AuthUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('auth_token', newToken)
    localStorage.setItem('auth_user', JSON.stringify(newUser))
    startExpirationTimer()
  }

  function logout() {
    stopExpirationTimer()
    // Clear all per-user localStorage data before wiping the user ref
    const email = user.value?.email
    if (email) {
      localStorage.removeItem(`userProfile_${email}`)
      localStorage.removeItem(`accessibility_${email}`)
      localStorage.removeItem(`customContexts_${email}`)
      localStorage.removeItem(`favoriteContextIds_${email}`)
    }
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  function loadFromLocalStorage() {
    const savedToken = localStorage.getItem('auth_token')
    const savedUser = localStorage.getItem('auth_user')
    if (savedToken && savedUser) {
      try {
        const exp = parseJwtExp(savedToken)
        if (!exp || exp * 1000 <= Date.now()) {
          logout()
          return
        }

        token.value = savedToken
        user.value = JSON.parse(savedUser)
        startExpirationTimer()
      } catch {
        logout()
      }
    }
  }

  return {
    token,
    user,
    pendingEmail,
    isAuthenticated,
    setAuth,
    setPendingEmail,
    logout,
    loadFromLocalStorage,
  }
})
