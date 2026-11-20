<script setup lang="ts">
import { ref, computed } from 'vue'
import { HelpCircle, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useUserProfileStore } from '@/stores/userProfile'
import { useSpeechSynthesis } from '@/composables/useSpeechSynthesis'

const profileStore = useUserProfileStore()
const { speak } = useSpeechSynthesis()

const DEFAULT_HELP_PHRASE = "J'ai besoin d'aide s'il vous plaît"
const phrases = computed(() => profileStore.profile.savedPhrases)
const open = ref(false)

function speakPhrase(phrase: string) {
  speak(phrase)
  open.value = false
}
</script>

<template>
  <div class="fixed bottom-6 right-6 z-[9999]">
    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          class="w-[60px] h-[60px] rounded-full shadow-xl text-white hover:scale-105 transition-transform focus-visible:ring-2 focus-visible:ring-offset-2"
          style="background-color: #F14D41; min-width: 60px; min-height: 60px;"
          aria-label="Phrases d'aide"
        >
          <HelpCircle style="width: 36px; height: 36px; min-width: 36px; min-height: 36px;" />
        </Button>
      </PopoverTrigger>
      <PopoverContent
        class="w-72 p-0 shadow-xl"
        side="top"
        align="end"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <span class="font-semibold text-base">Phrases d'aide</span>
          <Button variant="ghost" size="icon" class="h-7 w-7" @click="open = false">
            <X :size="16" />
          </Button>
        </div>
        <div class="p-2">
          <button
            class="w-full text-left px-3 py-2.5 text-sm rounded-md cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 min-h-[44px] flex items-center font-semibold"
            style="background: #FEE2E2; color: #DC2626;"
            @click="speakPhrase(DEFAULT_HELP_PHRASE)"
          >
            {{ DEFAULT_HELP_PHRASE }}
          </button>
          <template v-if="phrases.length > 0">
            <div class="border-t my-1"></div>
            <button
              v-for="phrase in phrases"
              :key="phrase"
              class="w-full text-left px-3 py-2.5 text-sm rounded-md hover:bg-slate-100 cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 min-h-[44px] flex items-center"
              @click="speakPhrase(phrase)"
            >
              {{ phrase }}
            </button>
          </template>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>
