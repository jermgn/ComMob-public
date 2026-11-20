<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ProfileSettings from '@/components/settings/ProfileSettings.vue'
import { Card, CardContent } from '@/components/ui/card'
</script>

<template>
  <AppLayout>
    <div class="max-w-[640px] mx-auto">
      <h1 class="text-2xl font-bold mb-6" style="color: #252526;">Réglages</h1>
      <Card>
        <CardContent class="p-6">
          <ProfileSettings />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
