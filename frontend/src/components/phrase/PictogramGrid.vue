<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import PictogramCard from './PictogramCard.vue'
import type { Pictogram } from '@/types/context'

interface Props {
  pictograms: Pictogram[]
  selectedIds: number[]
  loading?: boolean
  page: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  select: [p: Pictogram]
  'update:page': [p: number]
}>()

const PAGE_SIZE = 9
const totalPages = computed(() => Math.max(1, Math.ceil(props.pictograms.length / PAGE_SIZE)))

const pagePictograms = computed(() => {
  const start = props.page * PAGE_SIZE
  return props.pictograms.slice(start, start + PAGE_SIZE)
})

const slots = computed(() => {
  const items = pagePictograms.value
  const result: (Pictogram | null)[] = [...items]
  while (result.length < PAGE_SIZE) result.push(null)
  return result
})
</script>

<template>
  <div class="flex flex-col gap-3">
    <!-- Loading skeleton grid -->
    <div v-if="loading" class="grid grid-cols-3 gap-2">
      <Skeleton
        v-for="i in 9"
        :key="i"
        class="rounded-xl aspect-square"
        style="min-height: 80px"
      />
    </div>

    <!-- Pictogram grid -->
    <div v-else class="grid grid-cols-3 gap-2">
      <template v-for="(item, idx) in slots" :key="idx">
        <PictogramCard
          v-if="item"
          :pictogram="item"
          :selected="selectedIds.includes(item.pictogramId)"
          @select="emit('select', $event)"
        />
        <div
          v-else
          class="rounded-xl border-2 border-dashed border-slate-200"
          style="min-height: 90px"
        />
      </template>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex items-center justify-between">
      <Button
        variant="outline"
        size="sm"
        :disabled="page === 0"
        @click="emit('update:page', page - 1)"
      >
        <ChevronLeft :size="16" />
        Précédent
      </Button>
      <span class="text-xs text-slate-500">{{ page + 1 }} / {{ totalPages }}</span>
      <Button
        variant="outline"
        size="sm"
        :disabled="page >= totalPages - 1"
        @click="emit('update:page', page + 1)"
      >
        Suivant
        <ChevronRight :size="16" />
      </Button>
    </div>
  </div>
</template>
