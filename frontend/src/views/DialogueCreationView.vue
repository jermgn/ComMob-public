<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import ContextSelector from '@/components/phrase/ContextSelector.vue'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Save, Volume2, X, Trash2, MessagesSquare, Loader2, MapPinOff, ChevronDown } from 'lucide-vue-next'
import { useDialoguesStore } from '@/stores/dialogues'
import { useSpeechSynthesis } from '@/composables/useSpeechSynthesis'
import { useUserProfileStore } from '@/stores/userProfile'
import { fetchContexts, fetchAllContexts, fetchIpLocation } from '@/services/contextService'
import type { ContextCandidate } from '@/types/context'

const dialoguesStore = useDialoguesStore()
const { speak } = useSpeechSynthesis()
const profileStore = useUserProfileStore()

const EXAMPLE_DIALOGUES = [
  { context: 'Transport', phrase: "Bonjour, je voudrais un ticket s'il vous plaît." },
  { context: 'Pharmacie', phrase: "Bonjour, j'ai mal à la tête, est-ce que vous avez un médicament ?" },
  { context: 'Boulangerie', phrase: "Bonjour, je voudrais une baguette et deux croissants s'il vous plaît." },
  { context: 'Restaurant', phrase: "Excusez-moi, est-ce que je pourrais avoir l'addition s'il vous plaît ?" },
]

const examplesOpen = ref(false)
const loading = ref(true)
const locating = ref(false)
const geoError = ref('')
const allContexts = ref<ContextCandidate[]>([])
const selectedContext = ref<ContextCandidate | null>(null)
const newBlockText = ref('')
const editingDialogueId = ref<number | null>(null)
const deletingIds = ref<Set<number>>(new Set())

const customContexts = computed(() =>
  profileStore.profile.customContexts.map(c => ({ id: c.id, label: c.label, score: 0 } as ContextCandidate))
)

async function detectLocation(isRefresh = false) {
  if (isRefresh) {
    locating.value = true
  } else {
    loading.value = true
    await dialoguesStore.fetchDialogues()
  }
  geoError.value = ''
  selectedContext.value = null

  try {
    allContexts.value = await fetchAllContexts()
  } catch {}

  if (!navigator.geolocation) {
    geoError.value = "La géolocalisation n'est pas supportée"
    loading.value = false
    locating.value = false
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
      locating.value = false
    },
    async (err) => {
      if (err.code === err.POSITION_UNAVAILABLE || err.code === err.TIMEOUT) {
        const ipLoc = await fetchIpLocation()
        if (ipLoc) {
          try {
            const ctxs = await fetchContexts(ipLoc.latitude, ipLoc.longitude)
            const best = ctxs.find(c => c.id !== 'home' && c.id !== 'street')
            if (best) selectedContext.value = best
            loading.value = false
            locating.value = false
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
      loading.value = false
      locating.value = false
    },
    { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
  )
}

detectLocation()

// Reset the active dialogue when context changes so new phrases start a fresh conversation
watch(selectedContext, () => {
  editingDialogueId.value = null
})

const dialogueCount = computed(() => dialoguesStore.dialogues.length)

async function saveBlock() {
  const text = newBlockText.value.trim()
  if (!text) return

  // Validate the stored dialogue still exists (fixes stale-ID bug after deletion)
  const existingDialogue = editingDialogueId.value !== null
    ? dialoguesStore.dialogues.find(d => d.id === editingDialogueId.value)
    : null

  if (!existingDialogue) {
    editingDialogueId.value = null
    const d = await dialoguesStore.createDialogue(
      selectedContext.value?.label ?? 'Général',
      selectedContext.value?.id ?? 'general',
      selectedContext.value?.label ?? undefined
    )
    if (d) {
      await dialoguesStore.addBlock(d.id, text)
      editingDialogueId.value = d.id
    }
  } else {
    await dialoguesStore.addBlock(editingDialogueId.value!, text)
  }
  newBlockText.value = ''
}

async function deleteDialogue(id: number) {
  if (deletingIds.value.has(id)) return
  deletingIds.value = new Set(deletingIds.value).add(id)
  await dialoguesStore.deleteDialogue(id)
  if (editingDialogueId.value === id) {
    editingDialogueId.value = null
  }
  const next = new Set(deletingIds.value)
  next.delete(id)
  deletingIds.value = next
}

function loadBlockToEditor(text: string) {
  newBlockText.value = text
}

function handleSelectContext(ctx: ContextCandidate) {
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
  // Delete all conversations belonging to this context
  const toDelete = dialoguesStore.dialogues.filter(d => d.contextType === id)
  for (const d of toDelete) {
    if (editingDialogueId.value === d.id) editingDialogueId.value = null
    await dialoguesStore.deleteDialogue(d.id)
  }
}

function speakSelection() {
  const el = document.getElementById('dialogue-block-textarea') as HTMLTextAreaElement | null
  if (el) {
    const selected = el.value.substring(el.selectionStart, el.selectionEnd).trim()
    if (selected) { speak(selected); return }
  }
  if (newBlockText.value.trim()) speak(newBlockText.value.trim())
}
</script>

<template>
  <AppLayout>
    <h1 class="sr-only">Création de conversations</h1>

    <!-- Loading GPS -->
    <div v-if="loading" class="flex flex-col items-center justify-center min-h-[60vh] gap-5">
      <Loader2 :size="60" class="animate-spin" style="color: #9076F9" />
      <p class="text-xl font-semibold" style="color: #9076F9">Détection de votre position ...</p>
    </div>

    <template v-else>
      <!-- GPS Error (non-blocking: user can still create with no context) -->
      <Alert v-if="geoError" variant="destructive" role="alert" class="mb-4">
        <MapPinOff :size="16" />
        <AlertDescription>{{ geoError }}</AlertDescription>
      </Alert>

      <div class="flex flex-col gap-6 max-w-[800px] mx-auto">
        <!-- Editor section -->
        <div class="rounded-xl border bg-white p-4 flex flex-col gap-4">
          <h2 class="text-lg font-semibold text-slate-800">Nouvelle conversation</h2>

          <ContextSelector
            :context="selectedContext"
            :all-contexts="allContexts"
            :custom-contexts="customContexts"
            :locating="locating"
            @select="handleSelectContext"
            @add="handleAddContext"
            @delete="handleDeleteContext"
            @refresh="detectLocation(true)"
          />

          <Textarea
            id="dialogue-block-textarea"
            v-model="newBlockText"
            placeholder="Saisissez une phrase de discussion ..."
            rows="4"
            class="text-base leading-relaxed resize-none"
            @keydown.ctrl.enter.prevent="saveBlock"
          />

          <div class="flex gap-2 flex-wrap">
            <Button
              class="h-11 font-semibold"
              style="background-color: #9076F9; color: #fff;"
              :disabled="!newBlockText.trim()"
              @click="saveBlock"
            >
              <Save :size="16" class="mr-2" />
              Sauvegarder
            </Button>
            <Button
              variant="outline"
              class="h-11"
              :disabled="!newBlockText.trim()"
              @click="speak(newBlockText)"
            >
              <Volume2 :size="16" class="mr-2" />
              Lire
            </Button>
            <Button
              variant="outline"
              class="h-11"
              :disabled="!newBlockText.trim()"
              @click="speakSelection"
            >
              <Volume2 :size="16" class="mr-2" />
              Lire la sélection
            </Button>
            <Button
              variant="ghost"
              class="h-11"
              :disabled="!newBlockText.trim()"
              @click="newBlockText = ''"
            >
              <X :size="16" class="mr-2" />
              Effacer
            </Button>
          </div>
        </div>

        <Separator />

        <!-- Discussions list -->
        <div class="flex flex-col gap-4">
          <div class="flex items-center gap-2">
            <h2 class="text-lg font-semibold text-slate-800">Mes conversations enregistrées</h2>
            <Badge variant="secondary" class="text-xs">{{ dialogueCount }}</Badge>
          </div>

          <div v-if="dialoguesStore.dialogues.length === 0" class="text-center py-8 text-slate-400 flex flex-col items-center gap-3">
            <MessagesSquare :size="40" class="opacity-30" />
            <p class="text-sm">Aucune conversation sauvegardée</p>
          </div>

          <div v-for="dialogue in dialoguesStore.dialogues" :key="dialogue.id" class="rounded-xl border bg-white overflow-hidden">
            <!-- Dialogue header -->
            <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
              <div class="flex items-center gap-2">
                <span class="font-semibold text-slate-800 text-sm truncate max-w-[200px]">{{ dialogue.name }}</span>
                <Badge v-if="dialogue.contextLabel" class="text-xs" style="background-color: #F3F0FF; color: #9076F9; border: none;">
                  {{ dialogue.contextLabel }}
                </Badge>
              </div>
              <Button
                variant="ghost"
                size="sm"
                class="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                :disabled="deletingIds.has(dialogue.id)"
                :aria-label="`Supprimer la conversation ${dialogue.name}`"
                @click="deleteDialogue(dialogue.id)"
              >
                <Trash2 :size="14" />
              </Button>
            </div>

            <!-- Blocks -->
            <div class="p-3 flex flex-col gap-2">
              <button
                v-for="block in dialogue.blocks"
                :key="block.id"
                class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-100 w-full text-left hover:bg-purple-50 hover:border-purple-200 transition-colors group cursor-pointer"
                @click="loadBlockToEditor(block.text)"
              >
                <p class="flex-1 text-sm text-slate-700">{{ block.text }}</p>
                <div class="flex gap-1 shrink-0">
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity"
                    :aria-label="`Lire le bloc`"
                    @click.stop="speak(block.text)"
                  >
                    <Volume2 :size="14" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-red-400 hover:text-red-600 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-opacity"
                    :aria-label="`Supprimer le bloc`"
                    @click.stop="dialoguesStore.removeBlock(dialogue.id, block.id)"
                  >
                    <X :size="12" />
                  </Button>
                </div>
              </button>
            </div>
          </div>

          <!-- Exemples prêts à l'emploi (rétractable, fermé par défaut) -->
          <div class="rounded-xl border bg-white overflow-hidden">
            <button
              class="w-full flex items-center justify-between px-4 py-3 bg-purple-50 cursor-pointer border-0 hover:bg-purple-100 transition-colors"
              @click="examplesOpen = !examplesOpen"
            >
              <span class="font-semibold text-sm" style="color: #9076F9">Phrases d'exemple</span>
              <ChevronDown
                :size="18"
                class="transition-transform duration-200"
                :style="{ color: '#9076F9', transform: examplesOpen ? 'rotate(180deg)' : 'rotate(0deg)' }"
              />
            </button>
            <div v-if="examplesOpen" class="p-3 flex flex-col gap-2 border-t">
              <button
                v-for="(ex, i) in EXAMPLE_DIALOGUES"
                :key="i"
                class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 border border-slate-100 w-full text-left hover:bg-purple-50 hover:border-purple-200 transition-colors cursor-pointer group"
                @click="newBlockText = ex.phrase"
              >
                <Badge class="text-xs shrink-0" style="background-color: #F3F0FF; color: #9076F9; border: none;">
                  {{ ex.context }}
                </Badge>
                <p class="flex-1 text-sm text-slate-700">{{ ex.phrase }}</p>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-8 w-8 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                  aria-label="Lire la phrase"
                  @click.stop="speak(ex.phrase)"
                >
                  <Volume2 :size="16" />
                </Button>
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>
