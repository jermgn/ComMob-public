<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ContextSelector from '@/components/phrase/ContextSelector.vue'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'
import { Mic, Square, Volume2, Pause, Loader2, MapPinOff, ThumbsUp, ThumbsDown, HelpCircle, MessageCircleQuestion, RefreshCw, Maximize2 } from 'lucide-vue-next'
import { fetchContexts, fetchAllContexts, generateResponses, expandResponse as apiExpand, modifyResponse as apiModify, fetchIpLocation } from '@/services/contextService'
import { useSpeechSynthesis } from '@/composables/useSpeechSynthesis'
import { useUserProfileStore } from '@/stores/userProfile'
import type { ContextCandidate } from '@/types/context'

const { speak, stop, speaking } = useSpeechSynthesis()
const profileStore = useUserProfileStore()

const loading = ref(true)
const geoError = ref('')
const selectedContext = ref<ContextCandidate | null>(null)
const allContexts = ref<ContextCandidate[]>([])

const customContexts = computed(() =>
  profileStore.profile.customContexts.map(c => ({ id: c.id, label: c.label, score: 0 } as ContextCandidate))
)

const listening = ref(false)
const transcription = ref('')
const responses = ref<string[]>([])
const selectedIndex = ref<number | null>(null)
const editedResponse = ref('')
const generatingResponse = ref(false)
const actionLoading = ref(false)
const micError = ref('')

const RESPONSE_TYPES = [
  { label: 'Positive', icon: ThumbsUp, color: '#2BC05B', bg: '#EDFBF2' },
  { label: 'Négative', icon: ThumbsDown, color: '#F14D41', bg: '#FFF0EF' },
  { label: 'Question', icon: HelpCircle, color: '#2E76F0', bg: '#D4E6FA' },
  { label: 'Précision', icon: MessageCircleQuestion, color: '#9076F9', bg: '#F3F0FF' },
]

let recognition: any = null

async function detectLocation() {
  loading.value = true
  geoError.value = ''
  selectedContext.value = null

  try {
    allContexts.value = await fetchAllContexts()
  } catch {}

  if (!navigator.geolocation) {
    geoError.value = "La géolocalisation n'est pas supportée"
    loading.value = false
    return
  }

  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      try {
        const ctxs = await fetchContexts(pos.coords.latitude, pos.coords.longitude)
        const best = ctxs.find(c => c.id !== 'home' && c.id !== 'street')
        if (best) selectedContext.value = best
      } catch { geoError.value = 'Erreur lors de la détection' }
      loading.value = false
    },
    async (err) => {
      if (err.code === err.POSITION_UNAVAILABLE || err.code === err.TIMEOUT) {
        const ipLoc = await fetchIpLocation()
        if (ipLoc) {
          try {
            const ctxs = await fetchContexts(ipLoc.latitude, ipLoc.longitude)
            allContexts.value = await fetchAllContexts()
            const best = ctxs.find(c => c.id !== 'home' && c.id !== 'street')
            if (best) selectedContext.value = best
            loading.value = false
            return
          } catch { /* fall through */ }
        }
      }
      switch (err.code) {
        case err.PERMISSION_DENIED: geoError.value = "Veuillez autoriser l'accès à votre position"; break
        case err.POSITION_UNAVAILABLE: geoError.value = 'Position non disponible'; break
        case err.TIMEOUT: geoError.value = "Délai d'attente dépassé"; break
        default: geoError.value = 'Erreur de géolocalisation'
      }
      try { allContexts.value = await fetchAllContexts() } catch { /* ignore */ }
      loading.value = false
    },
    { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
  )
}

detectLocation()

function toggleMic() {
  if (listening.value) {
    recognition?.stop()
    listening.value = false
    return
  }
  const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
  if (!SpeechRecognition) {
    micError.value = "La reconnaissance vocale n'est pas supportée par votre navigateur"
    return
  }
  micError.value = ''
  recognition = new SpeechRecognition()
  recognition.lang = 'fr-FR'
  recognition.interimResults = false
  recognition.maxAlternatives = 1
  recognition.onresult = async (event: any) => {
    transcription.value = event.results[0]?.[0]?.transcript ?? ''
    listening.value = false
    if (transcription.value) await generateAIResponse()
  }
  recognition.onerror = (event: any) => {
    listening.value = false
    micError.value = 'Erreur de reconnaissance vocale: ' + event.error
  }
  recognition.onend = () => { listening.value = false }
  recognition.start()
  listening.value = true
}

async function generateAIResponse() {
  if (!transcription.value) return
  generatingResponse.value = true
  responses.value = []
  selectedIndex.value = null
  editedResponse.value = ''
  try {
    const ctx = selectedContext.value?.label ?? 'général'
    const result = await generateResponses(ctx, transcription.value)
    responses.value = result.responses ?? []
  } catch {
    responses.value = []
  } finally {
    generatingResponse.value = false
  }
}

function selectResponse(index: number) {
  selectedIndex.value = index
  editedResponse.value = responses.value[index] ?? ''
}

async function handleExpand() {
  if (selectedIndex.value === null) return
  actionLoading.value = true
  try {
    const ctx = selectedContext.value?.label ?? 'général'
    const result = await apiExpand(ctx, editedResponse.value)
    editedResponse.value = result.expanded
    responses.value[selectedIndex.value] = result.expanded
  } catch {}
  finally { actionLoading.value = false }
}

async function handleModify() {
  if (selectedIndex.value === null) return
  actionLoading.value = true
  try {
    const ctx = selectedContext.value?.label ?? 'général'
    const result = await apiModify(ctx, transcription.value, editedResponse.value)
    editedResponse.value = result.modified
    responses.value[selectedIndex.value] = result.modified
  } catch {}
  finally { actionLoading.value = false }
}

async function handleSelectContext(ctx: ContextCandidate) {
  selectedContext.value = ctx
}
async function handleAddContext(name: string) {
  const id = 'custom-' + name.toLowerCase().replace(/\s+/g, '_')
  if (customContexts.value.some(c => c.id === id)) return
  const updated = [...profileStore.profile.customContexts, { id, label: name }]
  await profileStore.saveProfile({ customContexts: updated })
  selectedContext.value = { id, label: name, score: 0 }
}
async function handleDeleteContext(id: string) {
  const updated = profileStore.profile.customContexts.filter(c => c.id !== id)
  await profileStore.saveProfile({ customContexts: updated })
  if (selectedContext.value?.id === id) selectedContext.value = null
}
</script>

<template>
  <AppLayout>
    <h1 class="sr-only">Mode réponse</h1>

    <!-- Loading GPS -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[60vh] gap-5">
      <Loader2 :size="60" class="animate-spin" style="color: #2BC05B" />
      <p class="text-xl font-semibold" style="color: #2BC05B">Détection de votre position ...</p>
    </div>

    <!-- Main content -->
    <div v-else class="max-w-[640px] mx-auto flex flex-col gap-6">

      <!-- GPS Error (non-bloquant) -->
      <Alert v-if="geoError" variant="destructive" role="alert" class="mb-0">
        <MapPinOff :size="16" />
        <AlertDescription>{{ geoError }} — Sélectionnez un contexte manuellement.</AlertDescription>
      </Alert>

      <!-- Context -->
      <div class="rounded-xl border bg-white p-4">
        <ContextSelector
          :context="selectedContext"
          :all-contexts="allContexts"
          :custom-contexts="customContexts"
          @select="handleSelectContext"
          @add="handleAddContext"
          @delete="handleDeleteContext"
          @refresh="detectLocation"
        />
      </div>

      <Separator />

      <!-- Microphone section -->
      <div class="flex flex-col items-center gap-4">
        <button
          class="w-20 h-20 rounded-full text-white flex items-center justify-center shadow-lg transition-all duration-200 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-offset-2"
          :class="listening ? 'mic-listening' : 'hover:scale-105 hover:shadow-xl'"
          :style="{
            backgroundColor: listening ? '#F14D41' : '#2BC05B',
            minWidth: '80px',
            minHeight: '80px',
          }"
          :aria-label="listening ? 'Arrêter l\'écoute' : 'Commencer l\'écoute'"
          :aria-pressed="listening"
          @click="toggleMic"
        >
          <Square v-if="listening" :size="28" />
          <Mic v-else :size="32" />
        </button>
        <p class="text-sm font-medium" :style="{ color: listening ? '#F14D41' : '#667085' }">
          {{ listening ? 'Écoute en cours...' : 'Appuyez pour écouter' }}
        </p>
      </div>

      <!-- Mic error -->
      <Alert v-if="micError" variant="destructive" role="alert">
        <AlertDescription>{{ micError }}</AlertDescription>
      </Alert>

      <!-- Transcription -->
      <div v-if="transcription" class="rounded-xl bg-slate-100 border p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Ce que j'ai entendu</p>
        <p class="text-base text-slate-800">{{ transcription }}</p>
      </div>

      <!-- Generating spinner -->
      <div v-if="generatingResponse" class="flex items-center justify-center gap-3 py-4">
        <Loader2 :size="24" class="animate-spin" style="color: #2BC05B" />
        <span class="text-sm text-slate-500">Génération des réponses...</span>
      </div>

      <!-- 4 Response cards -->
      <div v-if="responses.length > 0 && !generatingResponse" class="flex flex-col gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Choisissez une réponse</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <button
            v-for="(resp, i) in responses"
            :key="i"
            class="flex flex-col gap-2 p-4 rounded-xl border-2 text-left cursor-pointer transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1"
            :class="selectedIndex === i
              ? 'shadow-md'
              : 'border-slate-200 bg-white hover:shadow-sm'"
            :style="selectedIndex === i
              ? { borderColor: RESPONSE_TYPES[i]?.color ?? '#2BC05B', backgroundColor: RESPONSE_TYPES[i]?.bg ?? '#EDFBF2' }
              : {}"
            @click="selectResponse(i)"
          >
            <div class="flex items-center gap-2">
              <component
                :is="RESPONSE_TYPES[i]?.icon ?? ThumbsUp"
                :size="16"
                :style="{ color: RESPONSE_TYPES[i]?.color ?? '#667085' }"
              />
              <span
                class="text-xs font-bold uppercase tracking-wide"
                :style="{ color: RESPONSE_TYPES[i]?.color ?? '#667085' }"
              >
                {{ RESPONSE_TYPES[i]?.label ?? 'Réponse' }}
              </span>
            </div>
            <p class="text-sm text-slate-800 leading-relaxed">{{ resp }}</p>
          </button>
        </div>
      </div>

      <!-- Selected response actions -->
      <div v-if="selectedIndex !== null && !generatingResponse" class="flex flex-col gap-3 rounded-xl border bg-white p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Réponse sélectionnée</p>
        <Textarea
          v-model="editedResponse"
          :rows="3"
          class="text-lg leading-relaxed resize-none"
        />

        <div class="flex gap-2 flex-wrap">
          <!-- Lire -->
          <Button
            class="h-12 flex-1 text-base font-semibold text-white"
            style="background-color: #2BC05B; min-height: 48px;"
            :disabled="!editedResponse || actionLoading"
            @click="speak(editedResponse)"
          >
            <Volume2 :size="18" class="mr-2" />
            Lire
          </Button>
          <!-- Développer -->
          <Button
            variant="outline"
            class="h-12"
            :disabled="!editedResponse || actionLoading"
            @click="handleExpand"
          >
            <Loader2 v-if="actionLoading" :size="16" class="mr-2 animate-spin" />
            <Maximize2 v-else :size="16" class="mr-2" />
            Développer
          </Button>
          <!-- Modifier -->
          <Button
            variant="outline"
            class="h-12"
            :disabled="!editedResponse || actionLoading"
            @click="handleModify"
          >
            <RefreshCw :size="16" class="mr-2" />
            Modifier
          </Button>
        </div>

        <!-- Arrêter la lecture -->
        <Button
          v-if="speaking"
          variant="outline"
          class="h-12 w-full"
          @click="stop()"
        >
          <Pause :size="18" class="mr-2" />
          Arrêter
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
