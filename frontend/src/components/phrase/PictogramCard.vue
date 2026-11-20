<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ImageIcon, CheckCircle2 } from 'lucide-vue-next'
import { Skeleton } from '@/components/ui/skeleton'
import type { Pictogram } from '@/types/context'

interface Props {
  pictogram: Pictogram
  selected?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{ select: [p: Pictogram] }>()

const imgLoaded = ref(false)
const imgError = ref(false)
const imgSrc = computed(() => `https://api.arasaac.org/v1/pictograms/${props.pictogram.pictogramId}`)

watch(() => props.pictogram.pictogramId, () => {
  imgLoaded.value = false
  imgError.value = false
})
</script>

<template>
  <button
    class="relative flex flex-col items-center justify-between p-2 rounded-xl border-2 bg-white cursor-pointer transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 group"
    :class="selected
      ? 'border-blue-500 bg-blue-50 shadow-md'
      : 'border-slate-200 hover:border-slate-400 hover:shadow-sm'"
    :style="{ minWidth: '80px', minHeight: '90px' }"
    :aria-pressed="selected"
    :aria-label="pictogram.word"
    @click="emit('select', pictogram)"
  >
    <!-- Selected check badge -->
    <CheckCircle2
      v-if="selected"
      :size="18"
      class="absolute top-1.5 right-1.5 text-blue-500 z-10"
    />

    <!-- Skeleton -->
    <Skeleton v-if="!imgLoaded && !imgError" class="absolute inset-2 rounded-lg" />

    <!-- Error fallback -->
    <div v-if="imgError" class="flex items-center justify-center w-full h-full">
      <ImageIcon :size="28" class="text-slate-300" />
    </div>

    <!-- Image -->
    <img
      v-show="imgLoaded && !imgError"
      :src="imgSrc"
      :alt="pictogram.word"
      class="w-full object-contain flex-1 min-h-0"
      @load="imgLoaded = true"
      @error="imgError = true; imgLoaded = true"
    />

    <!-- Label -->
    <span class="text-[11px] font-semibold text-slate-700 text-center leading-tight line-clamp-2 mt-1 w-full shrink-0">
      {{ pictogram.word }}
    </span>
  </button>
</template>
