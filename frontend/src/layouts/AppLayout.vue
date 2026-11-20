<script setup lang="ts">
import AppTopBar from '@/components/navigation/AppTopBar.vue'
import HelpButton from '@/components/help/HelpButton.vue'
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <AppTopBar />
    <main
      id="main-content"
      tabindex="-1"
      class="mt-14 max-w-[1200px] mx-auto p-6 focus-visible:outline-none"
    >
      <slot />
    </main>
    <HelpButton />
  </div>
</template>
