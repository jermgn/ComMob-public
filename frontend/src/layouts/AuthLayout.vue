<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4" style="background: #F0F5FF;">
    <div class="w-full max-w-[420px]">
      <Card class="w-full shadow-lg border-0">
        <CardContent class="p-8">
          <slot />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
