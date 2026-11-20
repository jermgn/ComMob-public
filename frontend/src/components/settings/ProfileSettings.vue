<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Separator } from '@/components/ui/separator'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { X, Plus, LogOut } from 'lucide-vue-next'
import { useUserProfileStore } from '@/stores/userProfile'
import { useAuthStore } from '@/stores/auth'

const store = useUserProfileStore()
const authStore = useAuthStore()
const router = useRouter()

const firstName = ref('')
const lastName = ref('')
const age = ref<string>('')
const gender = ref<string>('none')
const aiDescription = ref('')
const savedPhrases = ref<string[]>([])
const newPhrase = ref('')
const savedMessage = ref(false)
let savedMessageTimer: ReturnType<typeof setTimeout> | null = null

function showSavedMessage() {
  savedMessage.value = true
  if (savedMessageTimer) clearTimeout(savedMessageTimer)
  savedMessageTimer = setTimeout(() => { savedMessage.value = false }, 3000)
}

let saveTimeout: ReturnType<typeof setTimeout> | null = null

function debouncedSaveProfile() {
  if (saveTimeout) clearTimeout(saveTimeout)
  saveTimeout = setTimeout(async () => {
    await store.saveProfile({
      firstName: firstName.value || null,
      lastName: lastName.value || null,
      age: age.value ? parseInt(age.value) : null,
      gender: (gender.value === 'none' ? null : gender.value) as 'male' | 'female' | null,
      aiDescription: aiDescription.value || null,
      savedPhrases: savedPhrases.value,
    })
    showSavedMessage()
  }, 800)
}

async function addPhrase() {
  const t = newPhrase.value.trim()
  if (!t || savedPhrases.value.includes(t)) return
  savedPhrases.value.push(t)
  newPhrase.value = ''
  await store.saveProfile({
    firstName: firstName.value || null,
    lastName: lastName.value || null,
    age: age.value ? parseInt(age.value) : null,
    gender: (gender.value === 'none' ? null : gender.value) as 'male' | 'female' | null,
    aiDescription: aiDescription.value || null,
    savedPhrases: savedPhrases.value,
  })
  showSavedMessage()
}

async function removePhrase(phrase: string) {
  const idx = savedPhrases.value.indexOf(phrase)
  if (idx !== -1) savedPhrases.value.splice(idx, 1)
  await store.saveProfile({
    firstName: firstName.value || null,
    lastName: lastName.value || null,
    age: age.value ? parseInt(age.value) : null,
    gender: (gender.value === 'none' ? null : gender.value) as 'male' | 'female' | null,
    aiDescription: aiDescription.value || null,
    savedPhrases: savedPhrases.value,
  })
  showSavedMessage()
}

function syncFromStore() {
  const p = store.profile
  firstName.value = p.firstName ?? ''
  lastName.value = p.lastName ?? ''
  age.value = p.age != null ? String(p.age) : ''
  gender.value = p.gender ?? 'none'
  aiDescription.value = p.aiDescription ?? ''
  savedPhrases.value = [...p.savedPhrases]
}

onMounted(() => { syncFromStore() })
watch(() => store.loading, (val, old) => { if (old && !val) syncFromStore() })
watch([firstName, lastName, age, gender, aiDescription], () => { debouncedSaveProfile() })

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex flex-col gap-0">
    <div v-if="store.loading" class="flex items-center justify-center gap-2 py-8 text-slate-500">
      <div class="w-4 h-4 border-2 border-t-transparent rounded-full animate-spin" style="border-color: #B0B5BD; border-top-color: transparent;" />
      Chargement du profil…
    </div>

    <template v-else>
      <!-- Mon profil -->
      <div class="flex flex-col gap-4 py-1">
        <div>
          <h2 class="text-lg font-semibold" style="color: #252526;">Mon profil</h2>
          <p class="text-sm text-slate-500 mt-1">Personnalisez vos informations pour adapter l'expérience à vos besoins.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <Label for="firstName">Prénom</Label>
            <Input id="firstName" v-model="firstName" placeholder="Marie" class="h-11" />
          </div>
          <div class="flex flex-col gap-1.5">
            <Label for="lastName">Nom</Label>
            <Input id="lastName" v-model="lastName" placeholder="Dupont" class="h-11" />
          </div>
          <div class="flex flex-col gap-1.5">
            <Label for="age">Âge</Label>
            <Input id="age" v-model="age" type="number" min="0" max="120" placeholder="25" class="h-11" />
          </div>
          <div class="flex flex-col gap-1.5">
            <Label for="gender">Sexe</Label>
            <Select v-model="gender">
              <SelectTrigger id="gender" class="h-11">
                <SelectValue placeholder="Non précisé" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Non précisé</SelectItem>
                <SelectItem value="male">Homme</SelectItem>
                <SelectItem value="female">Femme</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="col-span-2 flex flex-col gap-1.5">
            <Label for="aiDesc">Description pour l'IA</Label>
            <Textarea
              id="aiDesc"
              v-model="aiDescription"
              :rows="4"
              placeholder="Décrivez vos préférences, habitudes, contexte de communication..."
              class="resize-none"
            />
          </div>
        </div>
      </div>

      <Separator class="my-4" />

      <!-- Mes phrases -->
      <div class="flex flex-col gap-4">
        <div>
          <h2 class="text-lg font-semibold" style="color: #252526;">Mes phrases</h2>
          <p class="text-sm text-slate-500 mt-1">Ces phrases sont disponibles en accès rapide lors de l'interaction avec le bouton d'aide.</p>
        </div>

        <div class="flex flex-wrap gap-2 min-h-[36px]">
          <button
            v-for="phrase in savedPhrases"
            :key="phrase"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium hover:opacity-80 transition-colors cursor-pointer min-h-[36px] focus-visible:outline-none focus-visible:ring-2"
            style="background: #F5F6F7; border: 1px solid #D1D5DB; color: #252526;"
            :aria-label="`Supprimer la phrase : ${phrase}`"
            @click="removePhrase(phrase)"
          >
            {{ phrase }}
            <X :size="12" />
          </button>
          <p v-if="savedPhrases.length === 0" class="text-sm text-slate-400">Aucune phrase sauvegardée.</p>
        </div>

        <div class="flex gap-2">
          <Input
            v-model="newPhrase"
            placeholder="Ajouter une phrase personnalisée ..."
            class="flex-1 h-11"
            @keydown.enter.prevent="addPhrase"
          />
          <Button
            class="h-11 w-11 p-0 shrink-0"
            :disabled="!newPhrase.trim()"
            aria-label="Ajouter la phrase"
            @click="addPhrase"
          >
            <Plus :size="18" />
          </Button>
        </div>
      </div>

      <Separator class="my-4" />

      <!-- Message confirmation sauvegarde -->
      <Transition name="fade">
        <p
          v-if="savedMessage"
          class="text-sm font-medium text-center py-2 px-4 rounded-lg mb-3"
          style="background-color: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;"
        >
          Les modifications du profil ont été sauvegardées !
        </p>
      </Transition>

      <!-- Déconnexion -->
      <Button
        class="h-12 w-full text-white font-semibold"
        style="background-color: #F14D41;"
        @click="handleLogout"
      >
        <LogOut :size="18" class="mr-2" />
        Se déconnecter
      </Button>
    </template>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
