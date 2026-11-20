import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export type TextSize = 'small' | 'medium' | 'large' | 'xlarge'

function getStorageKey(): string {
  const authStore = useAuthStore()
  const email = authStore.user?.email
  return email ? `accessibility_${email}` : 'accessibility'
}

export const useAccessibilityStore = defineStore('accessibility', () => {
  const textSize = ref<TextSize>('medium')
  const highContrast = ref(false)
  const audioFeedback = ref(true)
  const vibration = ref(true)
  const keyboardOnly = ref(false)

  function setTextSize(size: TextSize) {
    textSize.value = size
    updateRootFontSize(size)
    saveToLocalStorage()
  }

  function toggleHighContrast() {
    highContrast.value = !highContrast.value
    updateTheme()
    saveToLocalStorage()
  }

  function toggleAudioFeedback() {
    audioFeedback.value = !audioFeedback.value
    saveToLocalStorage()
  }

  function toggleVibration() {
    vibration.value = !vibration.value
    saveToLocalStorage()
  }

  function setKeyboardOnly(value: boolean) {
    keyboardOnly.value = value
    if (value) {
      document.body.classList.add('keyboard-navigation')
    } else {
      document.body.classList.remove('keyboard-navigation')
    }
  }

  function updateRootFontSize(size: TextSize) {
    document.documentElement.setAttribute('data-text-size', size)
  }

  function updateTheme() {
    if (highContrast.value) {
      document.documentElement.classList.add('high-contrast')
    } else {
      document.documentElement.classList.remove('high-contrast')
    }
  }

  function saveToLocalStorage() {
    const data = {
      textSize: textSize.value,
      highContrast: highContrast.value,
      audioFeedback: audioFeedback.value,
      vibration: vibration.value
    }
    localStorage.setItem(getStorageKey(), JSON.stringify(data))
  }

  function loadFromLocalStorage() {
    const saved = localStorage.getItem(getStorageKey())
    if (saved) {
      try {
        const data = JSON.parse(saved)
        textSize.value = data.textSize || 'medium'
        highContrast.value = data.highContrast || false
        audioFeedback.value = data.audioFeedback ?? true
        vibration.value = data.vibration ?? true

        updateRootFontSize(textSize.value)
        updateTheme()
      } catch (error) {
        console.error('Erreur chargement paramètres accessibilité:', error)
      }
    }
  }

  function playAudioFeedback() {
    if (audioFeedback.value) {
      const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)()
      const oscillator = audioContext.createOscillator()
      const gainNode = audioContext.createGain()

      oscillator.connect(gainNode)
      gainNode.connect(audioContext.destination)

      oscillator.frequency.value = 800
      oscillator.type = 'sine'

      gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1)

      oscillator.start(audioContext.currentTime)
      oscillator.stop(audioContext.currentTime + 0.1)
    }
  }

  function triggerVibration(pattern: number | number[] = 50) {
    if (vibration.value && 'vibrate' in navigator) {
      navigator.vibrate(pattern)
    }
  }

  function resetToDefaults() {
    textSize.value = 'medium'
    highContrast.value = false
    audioFeedback.value = true
    vibration.value = true
    keyboardOnly.value = false
    updateRootFontSize('medium')
    updateTheme()
  }

  return {
    textSize,
    highContrast,
    audioFeedback,
    vibration,
    keyboardOnly,
    setTextSize,
    toggleHighContrast,
    toggleAudioFeedback,
    toggleVibration,
    setKeyboardOnly,
    loadFromLocalStorage,
    resetToDefaults,
    playAudioFeedback,
    triggerVibration
  }
})
