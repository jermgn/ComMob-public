<script setup lang="ts">
import { ref, computed } from 'vue'
import { MapPin, Pencil, Plus, X, RefreshCw, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { ContextCandidate } from '@/types/context'

interface Props {
  context: ContextCandidate | null
  allContexts: ContextCandidate[]
  customContexts: ContextCandidate[]
  locating?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  select: [ctx: ContextCandidate]
  add: [name: string]
  delete: [id: string]
  refresh: []
}>()

const showSelector = ref(false)
const search = ref('')
const newContextName = ref('')

const filteredContexts = computed(() => {
  const all = [...props.allContexts, ...props.customContexts]
  if (!search.value) return all
  return all.filter(c => c.label.toLowerCase().includes(search.value.toLowerCase()))
})

function selectContext(ctx: ContextCandidate) {
  emit('select', ctx)
  showSelector.value = false
  search.value = ''
}

function addCustom() {
  const name = (newContextName.value || search.value).trim()
  if (!name) return
  emit('add', name)
  newContextName.value = ''
  search.value = ''
}

function isActive(ctx: ContextCandidate) {
  return props.context?.id === ctx.id
}
</script>

<template>
  <div class="flex flex-col gap-2">
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2 text-slate-600 flex-1 min-w-0">
        <MapPin :size="16" class="shrink-0" />
        <span class="text-sm font-medium truncate">
          {{ context?.label ?? 'Aucun contexte détecté' }}
        </span>
      </div>
      <div class="flex items-center gap-1 shrink-0">
        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :disabled="locating"
          aria-label="Rafraîchir la localisation"
          @click="emit('refresh')"
        >
          <Loader2 v-if="locating" :size="14" class="animate-spin" />
          <RefreshCw v-else :size="14" />
        </Button>
        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :aria-label="showSelector ? 'Fermer' : 'Modifier le contexte'"
          @click="showSelector = !showSelector"
        >
          <Pencil :size="14" />
        </Button>
      </div>
    </div>

    <div v-if="showSelector" class="flex flex-col gap-2 border rounded-lg p-3 bg-white">
      <div class="flex gap-2">
        <Input
          v-model="search"
          placeholder="Rechercher ou ajouter un nouveau contexte ..."
          class="flex-1 h-9 text-sm"
          @keydown.enter.prevent="addCustom"
        />
        <Button
          size="icon"
          class="h-9 w-9 shrink-0"
          :disabled="!search.trim()"
          aria-label="Ajouter le contexte"
          @click="addCustom"
        >
          <Plus :size="16" />
        </Button>
      </div>

      <div class="flex flex-wrap gap-1.5 max-h-[120px] overflow-y-auto">
        <button
          v-for="ctx in filteredContexts"
          :key="ctx.id"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 min-h-[32px]"
          :class="isActive(ctx)
            ? 'bg-blue-50 border-blue-400 text-blue-700'
            : 'bg-white border-slate-200 text-slate-700 hover:border-slate-400'"
          @click="selectContext(ctx)"
        >
          {{ ctx.label }}
          <button
            v-if="ctx.id && ctx.id.startsWith('custom-')"
            class="ml-1 hover:text-red-500 focus:outline-none"
            :aria-label="`Supprimer ${ctx.label}`"
            @click.stop="emit('delete', ctx.id)"
          >
            <X :size="10" />
          </button>
        </button>
        <p v-if="filteredContexts.length === 0" class="text-xs text-slate-400 py-2 w-full text-center">
          Aucun contexte trouvé
        </p>
      </div>
    </div>
  </div>
</template>
