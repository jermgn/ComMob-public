<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ContextSelector from '@/components/phrase/ContextSelector.vue'
import PictogramGrid from '@/components/phrase/PictogramGrid.vue'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Volume2, RotateCcw, X, Loader2, MapPinOff } from 'lucide-vue-next'
import { fetchContexts, fetchAllContexts, fetchContext, generatePhrase, fetchIpLocation } from '@/services/contextService'
import { useSpeechSynthesis } from '@/composables/useSpeechSynthesis'
import { useUserProfileStore } from '@/stores/userProfile'
import type { ContextCandidate, Pictogram } from '@/types/context'

const { speak, stop, speaking } = useSpeechSynthesis()
const profileStore = useUserProfileStore()

const latitude = ref<number | null>(null)
const longitude = ref<number | null>(null)
const loading = ref(true)
const geoError = ref('')

const contexts = ref<ContextCandidate[]>([])
const selectedContext = ref<ContextCandidate | null>(null)
const contextPictograms = ref<Pictogram[]>([])
const allContexts = ref<ContextCandidate[]>([])
const pictogramPage = ref(0)

const selectedPictograms = ref<Pictogram[]>([])
const generatedPhrase = ref('')
const generatingPhrase = ref(false)

const customContexts = computed(() =>
  profileStore.profile.customContexts.map(c => ({ id: c.id, label: c.label, score: 0 } as ContextCandidate))
)

const selectedIds = computed(() => selectedPictograms.value.map(p => p.pictogramId))

onMounted(() => { detectLocation() })

async function detectLocation() {
  loading.value = true
  geoError.value = ''
  selectedContext.value = null
  contextPictograms.value = []
  if (!navigator.geolocation) {
    geoError.value = "La géolocalisation n'est pas supportée"
    loading.value = false
    return
  }
  navigator.geolocation.getCurrentPosition(
    async (pos) => {
      latitude.value = pos.coords.latitude
      longitude.value = pos.coords.longitude
      try {
        contexts.value = await fetchContexts(latitude.value, longitude.value)
        allContexts.value = await fetchAllContexts()
        const best = contexts.value.find(c => c.id !== 'home' && c.id !== 'street')
        if (best) {
          selectedContext.value = best
          await loadContextPictograms(best.id)
        }
      } catch { geoError.value = 'Erreur lors de la détection' }
      loading.value = false
    },
    async (err) => {
      if (err.code === err.POSITION_UNAVAILABLE || err.code === err.TIMEOUT) {
        const ipLoc = await fetchIpLocation()
        if (ipLoc) {
          try {
            contexts.value = await fetchContexts(ipLoc.latitude, ipLoc.longitude)
            allContexts.value = await fetchAllContexts()
            const best = contexts.value.find(c => c.id !== 'home' && c.id !== 'street')
            if (best) { selectedContext.value = best; await loadContextPictograms(best.id) }
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

async function loadContextPictograms(contextId: string) {
  try {
    const result = await fetchContext(contextId)
    contextPictograms.value = result.keywords
    pictogramPage.value = 0
  } catch (e) { console.error(e) }
}

async function handleSelectPictogram(pictogram: Pictogram) {
  const index = selectedPictograms.value.findIndex(p => p.pictogramId === pictogram.pictogramId)
  if (index === -1) selectedPictograms.value.push(pictogram)
  else selectedPictograms.value.splice(index, 1)
  await updateGeneratedPhrase()
}

async function updateGeneratedPhrase() {
  if (selectedPictograms.value.length === 0) { generatedPhrase.value = ''; return }
  generatingPhrase.value = true
  try {
    const words = selectedPictograms.value.map(p => p.word)
    const result = await generatePhrase(words)
    generatedPhrase.value = result.phrase
  } catch { generatedPhrase.value = selectedPictograms.value.map(p => p.word).join(' ') }
  finally { generatingPhrase.value = false }
}

function handleResetPhrase() {
  selectedPictograms.value = []
  generatedPhrase.value = ''
  if (speaking.value) stop()
}

function removePictogram(pictogram: Pictogram) {
  const idx = selectedPictograms.value.findIndex(p => p.pictogramId === pictogram.pictogramId)
  if (idx !== -1) { selectedPictograms.value.splice(idx, 1); updateGeneratedPhrase() }
}

async function handleSelectContext(ctx: ContextCandidate) {
  selectedContext.value = ctx
  selectedPictograms.value = []
  generatedPhrase.value = ''
  await loadContextPictograms(ctx.id)
}

async function handleAddContext(name: string) {
  const id = 'custom-' + name.toLowerCase().replace(/\s+/g, '_')
  if (allContexts.value.some(c => c.id === id) || customContexts.value.some(c => c.id === id)) return
  const newCtx: ContextCandidate = { id, label: name, score: 0 }
  const updated = [...profileStore.profile.customContexts, { id, label: name }]
  await profileStore.saveProfile({ customContexts: updated })
  await handleSelectContext(newCtx)
}

async function handleDeleteContext(id: string) {
  const updated = profileStore.profile.customContexts.filter(c => c.id !== id)
  await profileStore.saveProfile({ customContexts: updated })
  if (selectedContext.value?.id === id) selectedContext.value = null
}
</script>

<template>
  <AppLayout>
    <h1 class="sr-only">Génération de phrase</h1>

    <!-- Loading GPS -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[60vh] gap-5">
      <Loader2 :size="60" class="animate-spin" style="color: #FAB861" />
      <p class="text-xl font-semibold" style="color: #FAB861">Détection de votre position ...</p>
    </div>

    <!-- Main content -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

      <!-- GPS Error (non-bloquant) -->
      <Alert v-if="geoError" variant="destructive" role="alert" class="lg:col-span-2 mb-0">
        <MapPinOff :size="16" />
        <AlertDescription>{{ geoError }} — Sélectionnez un contexte manuellement.</AlertDescription>
      </Alert>
      <!-- Left: Context + Pictograms -->
      <section class="flex flex-col gap-4" aria-label="Sélection de pictogrammes">
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
        <div class="rounded-xl border bg-white p-4 flex flex-col gap-3">
          <p class="text-xs font-semibold uppercase tracking-wide" style="color: #FAB861">Pictogrammes</p>
          <PictogramGrid
            :pictograms="contextPictograms"
            :selected-ids="selectedIds"
            :loading="false"
            :page="pictogramPage"
            @select="handleSelectPictogram"
            @update:page="pictogramPage = $event"
          />
        </div>
      </section>

      <!-- Right: Phrase -->
      <section class="flex flex-col gap-4" aria-label="Phrase générée">
        <div class="rounded-xl border bg-white p-4 flex flex-col gap-4">
          <p class="text-xs font-semibold uppercase tracking-wide" style="color: #FAB861">
            Phrase générée
          </p>

          <div class="relative">
            <Textarea
              v-model="generatedPhrase"
              placeholder="Sélectionnez des pictogrammes ..."
              class="text-xl leading-relaxed resize-none min-h-[120px]"
              rows="4"
            />
            <Loader2
              v-if="generatingPhrase"
              :size="20"
              class="animate-spin absolute top-3 right-3 text-slate-400"
            />
          </div>

          <!-- Selected pictogram chips -->
          <div v-if="selectedPictograms.length > 0" class="flex flex-wrap gap-2">
            <button
              v-for="p in selectedPictograms"
              :key="p.pictogramId"
              class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium hover:opacity-80 cursor-pointer transition-colors min-h-[36px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
              style="background: #FFF8EC; border: 1px solid rgba(250, 184, 97, 0.4); color: #252526;"
              :aria-label="`Retirer ${p.word}`"
              @click="removePictogram(p)"
            >
              {{ p.word }}
              <X :size="12" />
            </button>
          </div>

          <!-- Action buttons -->
          <div class="flex gap-3">
            <Button
              class="flex-1 h-14 text-base font-semibold"
              style="background-color: #FAB861; color: #252526; min-height: 56px;"
              :disabled="!generatedPhrase || generatingPhrase"
              aria-label="Lire la phrase"
              @click="speak(generatedPhrase)"
            >
              <Volume2 :size="20" class="mr-2" />
              Lire
            </Button>
            <Button
              variant="outline"
              class="h-14 w-14 p-0 shrink-0"
              :disabled="!generatedPhrase && selectedPictograms.length === 0"
              aria-label="Réinitialiser"
              @click="handleResetPhrase"
            >
              <RotateCcw :size="18" />
            </Button>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
