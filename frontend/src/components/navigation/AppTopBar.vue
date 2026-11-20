<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { MessageSquare, Mic, MessagesSquare, Settings, LogOut } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

function handleLogout() {
  authStore.logout()
  router.push('/login')
}

const navItems = [
  { path: '/phrase',    label: 'Expression',  icon: MessageSquare,  color: '#FAB861' },
  { path: '/reponse',   label: 'Réponse',     icon: Mic,            color: '#2BC05B' },
  { path: '/dialogues', label: 'Conversation',      icon: MessagesSquare, color: '#9076F9' },
  { path: '/settings',  label: 'Réglages',          icon: Settings,       color: '#B0B5BD' },
]

function isActive(path: string) {
  return route.path === path || route.path.startsWith(path + '/')
}
function activeColor(item: { color: string; path: string }) {
  return isActive(item.path) ? item.color : '#9CA3AF'
}
</script>

<template>
  <header class="fixed top-0 left-0 right-0 h-14 z-50 bg-white border-b border-slate-100 flex items-stretch shadow-sm">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-[9999] focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:font-semibold">
      Aller au contenu
    </a>

    <nav class="flex flex-1" role="navigation" aria-label="Navigation principale">
      <button
        v-for="item in navItems"
        :key="item.path"
        class="flex-1 flex flex-row items-center justify-center gap-1.5 relative cursor-pointer border-0 bg-transparent transition-all duration-150 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset"
        :style="{ '--focus-ring-color': item.color }"
        :aria-current="isActive(item.path) ? 'page' : undefined"
        :aria-label="item.label"
        @click="router.push(item.path)"
      >
        <component
          :is="item.icon"
          :size="20"
          :style="{ color: activeColor(item) }"
        />
        <span
          class="text-xs font-semibold truncate max-w-[80px]"
          :style="{ color: activeColor(item) }"
        >
          {{ item.label }}
        </span>
        <!-- Active indicator bar -->
        <span
          v-if="isActive(item.path)"
          class="absolute bottom-0 left-0 right-0 h-[3px] rounded-t"
          :style="{ backgroundColor: item.color }"
        />
      </button>
    </nav>

    <button
      class="flex items-center justify-center px-3 cursor-pointer border-0 bg-transparent transition-colors duration-150 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-red-400"
      aria-label="Se déconnecter"
      @click="handleLogout"
    >
      <LogOut :size="20" style="color: #F14D41" />
    </button>
  </header>
</template>
