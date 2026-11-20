import { ref, onMounted } from 'vue'

export function useSpeechSynthesis() {
  const speaking = ref(false)
  const voices = ref<SpeechSynthesisVoice[]>([])
  const selectedVoice = ref<SpeechSynthesisVoice | null>(null)
  const utterance = ref<SpeechSynthesisUtterance | null>(null)

  onMounted(() => {
    loadVoices()

    if ('speechSynthesis' in window) {
      window.speechSynthesis.onvoiceschanged = () => {
        loadVoices()
      }
    }
  })

  function loadVoices() {
    voices.value = window.speechSynthesis.getVoices()

    const frenchVoices = voices.value.filter(v => v.lang.startsWith('fr'))

    if (frenchVoices.length > 0) {
      const preferredVoice =
        frenchVoices.find(v =>
          v.name.toLowerCase().includes('google') ||
          v.name.toLowerCase().includes('microsoft') ||
          v.name.toLowerCase().includes('natural')
        ) || frenchVoices[0]

      selectedVoice.value = preferredVoice
    }
  }

  function speak(text: string, options: {
    rate?: number
    pitch?: number
    volume?: number
    voice?: SpeechSynthesisVoice | null
  } = {}) {
    if (!text || speaking.value) return

    window.speechSynthesis.cancel()

    utterance.value = new SpeechSynthesisUtterance(text)
    utterance.value.lang = 'fr-FR'
    utterance.value.rate = options.rate ?? 1
    utterance.value.pitch = options.pitch ?? 1
    utterance.value.volume = options.volume ?? 1

    if (options.voice || selectedVoice.value) {
      utterance.value.voice = options.voice || selectedVoice.value
    }

    utterance.value.onstart = () => {
      speaking.value = true
    }

    utterance.value.onend = () => {
      speaking.value = false
    }

    utterance.value.onerror = (event) => {
      console.error('Erreur synthèse vocale:', event)
      speaking.value = false
    }

    window.speechSynthesis.speak(utterance.value)
  }

  function stop() {
    window.speechSynthesis.cancel()
    speaking.value = false
  }

  function pause() {
    if (speaking.value) {
      window.speechSynthesis.pause()
    }
  }

  function resume() {
    if (speaking.value) {
      window.speechSynthesis.resume()
    }
  }

  function setVoice(voice: SpeechSynthesisVoice) {
    selectedVoice.value = voice
  }

  return {
    speaking,
    voices,
    selectedVoice,
    speak,
    stop,
    pause,
    resume,
    setVoice,
    loadVoices
  }
}
