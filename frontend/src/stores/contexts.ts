import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Pictogram } from '@/types/context'
import { useAuthStore } from './auth'

export interface CustomContext {
  id: string
  name: string
  icon: string
  pictograms: Pictogram[]
  phrases: string[]
  isFavorite: boolean
  createdAt: number
}

function getStorageKey(key: string): string {
  const authStore = useAuthStore()
  const email = authStore.user?.email
  return email ? `${key}_${email}` : key
}

export const useContextsStore = defineStore('contexts', () => {
  const customContexts = ref<CustomContext[]>([])
  const favoriteContextIds = ref<string[]>([])

  function createCustomContext(name: string, icon: string = '📍'): CustomContext {
    const context: CustomContext = {
      id: generateId(),
      name,
      icon,
      pictograms: [],
      phrases: [],
      isFavorite: false,
      createdAt: Date.now()
    }

    customContexts.value.push(context)
    saveToLocalStorage()
    return context
  }

  function updateCustomContext(id: string, updates: Partial<CustomContext>) {
    const index = customContexts.value.findIndex(c => c.id === id)
    if (index !== -1) {
      customContexts.value[index] = {
        ...customContexts.value[index],
        ...updates
      }
      saveToLocalStorage()
    }
  }

  function deleteCustomContext(id: string) {
    customContexts.value = customContexts.value.filter(c => c.id !== id)
    favoriteContextIds.value = favoriteContextIds.value.filter(fid => fid !== id)
    saveToLocalStorage()
  }

  function addPictogramToContext(contextId: string, pictogram: Pictogram) {
    const context = customContexts.value.find(c => c.id === contextId)
    if (context) {
      const exists = context.pictograms.some(p => p.pictogramId === pictogram.pictogramId)
      if (!exists) {
        context.pictograms.push(pictogram)
        saveToLocalStorage()
      }
    }
  }

  function removePictogramFromContext(contextId: string, pictogramId: number) {
    const context = customContexts.value.find(c => c.id === contextId)
    if (context) {
      context.pictograms = context.pictograms.filter(p => p.pictogramId !== pictogramId)
      saveToLocalStorage()
    }
  }

  function addPhraseToContext(contextId: string, phrase: string) {
    const context = customContexts.value.find(c => c.id === contextId)
    if (context && !context.phrases.includes(phrase)) {
      context.phrases.push(phrase)
      saveToLocalStorage()
    }
  }

  function removePhraseFromContext(contextId: string, phrase: string) {
    const context = customContexts.value.find(c => c.id === contextId)
    if (context) {
      context.phrases = context.phrases.filter(p => p !== phrase)
      saveToLocalStorage()
    }
  }

  function toggleFavorite(contextId: string) {
    const index = favoriteContextIds.value.indexOf(contextId)
    if (index === -1) {
      favoriteContextIds.value.push(contextId)
    } else {
      favoriteContextIds.value.splice(index, 1)
    }
    saveToLocalStorage()
  }

  function isFavorite(contextId: string): boolean {
    return favoriteContextIds.value.includes(contextId)
  }

  function saveToLocalStorage() {
    localStorage.setItem(getStorageKey('customContexts'), JSON.stringify(customContexts.value))
    localStorage.setItem(getStorageKey('favoriteContextIds'), JSON.stringify(favoriteContextIds.value))
  }

  function loadFromLocalStorage() {
    const savedContexts = localStorage.getItem(getStorageKey('customContexts'))
    const savedFavorites = localStorage.getItem(getStorageKey('favoriteContextIds'))

    if (savedContexts) {
      try {
        customContexts.value = JSON.parse(savedContexts)
      } catch (error) {
        console.error('Erreur chargement contextes personnalisés:', error)
        customContexts.value = []
      }
    }

    if (savedFavorites) {
      try {
        favoriteContextIds.value = JSON.parse(savedFavorites)
      } catch (error) {
        console.error('Erreur chargement favoris:', error)
        favoriteContextIds.value = []
      }
    }
  }

  function generateId(): string {
    return `ctx-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
  }

  function resetToDefaults() {
    customContexts.value = []
    favoriteContextIds.value = []
  }

  return {
    customContexts,
    favoriteContextIds,
    createCustomContext,
    updateCustomContext,
    deleteCustomContext,
    addPictogramToContext,
    removePictogramFromContext,
    addPhraseToContext,
    removePhraseFromContext,
    toggleFavorite,
    isFavorite,
    loadFromLocalStorage,
    resetToDefaults
  }
})
