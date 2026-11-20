import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiFetch } from '@/services/api'
import type { UserProfile } from '@/types/profile'

const DEFAULT_PROFILE: UserProfile = {
  firstName: null,
  lastName: null,
  age: null,
  gender: null,
  aiDescription: null,
  savedPhrases: ["J'ai besoin d'aide !"],
  customContexts: [],
}

export type { UserProfile }

export const useUserProfileStore = defineStore('userProfile', () => {
  const profile = ref<UserProfile>({ ...DEFAULT_PROFILE })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)
  const lastSaved = ref<Date | null>(null)

  async function fetchProfile() {
    loading.value = true
    error.value = null
    try {
      const data = await apiFetch<UserProfile>('/user/profile')
      profile.value = { ...DEFAULT_PROFILE, ...data }
    } catch (e: any) {
      error.value = e.message || 'Erreur de chargement du profil'
    } finally {
      loading.value = false
    }
  }

  async function saveProfile(updates: Partial<UserProfile>) {
    saving.value = true
    error.value = null
    try {
      const data = await apiFetch<UserProfile>('/user/profile', {
        method: 'PUT',
        body: JSON.stringify(updates),
      })
      profile.value = { ...DEFAULT_PROFILE, ...data }
      lastSaved.value = new Date()
    } catch (e: any) {
      error.value = e.message || 'Erreur de sauvegarde'
      throw e
    } finally {
      saving.value = false
    }
  }

  function updateProfile(updates: Partial<UserProfile>) {
    profile.value = { ...profile.value, ...updates }
  }

  function resetToDefaults() {
    profile.value = { ...DEFAULT_PROFILE }
    error.value = null
    lastSaved.value = null
  }

  return {
    profile,
    loading,
    saving,
    error,
    lastSaved,
    fetchProfile,
    saveProfile,
    updateProfile,
    resetToDefaults,
  }
})
