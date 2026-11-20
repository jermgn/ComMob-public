import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiFetch } from '@/services/api'

export interface DialogueBlock {
  id: number
  text: string
  order: number
}

export interface Dialogue {
  id: number
  name: string
  contextType: string
  contextLabel?: string
  blocks: DialogueBlock[]
  createdAt: number
  updatedAt: number
}

export const useDialoguesStore = defineStore('dialogues', () => {
  const dialogues = ref<Dialogue[]>([])

  async function fetchDialogues(): Promise<void> {
    try {
      const data = await apiFetch<Dialogue[]>('/dialogues')
      dialogues.value = data
    } catch {
      dialogues.value = []
    }
  }

  async function createDialogue(name: string, contextType: string, contextLabel?: string): Promise<Dialogue | null> {
    try {
      const dialogue = await apiFetch<Dialogue>('/dialogues', {
        method: 'POST',
        body: JSON.stringify({ name, contextType, contextLabel: contextLabel ?? null }),
      })
      dialogues.value.push(dialogue)
      return dialogue
    } catch {
      return null
    }
  }

  async function deleteDialogue(id: number): Promise<void> {
    try {
      await apiFetch(`/dialogues/${id}`, { method: 'DELETE' })
      dialogues.value = dialogues.value.filter(d => d.id !== id)
    } catch {}
  }

  async function addBlock(dialogueId: number, text: string): Promise<DialogueBlock | null> {
    try {
      const block = await apiFetch<DialogueBlock>(`/dialogues/${dialogueId}/blocks`, {
        method: 'POST',
        body: JSON.stringify({ text }),
      })
      const dialogue = dialogues.value.find(d => d.id === dialogueId)
      if (dialogue) {
        dialogue.blocks.push(block)
        dialogue.updatedAt = Date.now()
      }
      return block
    } catch {
      return null
    }
  }

  async function removeBlock(dialogueId: number, blockId: number): Promise<void> {
    try {
      await apiFetch(`/dialogues/${dialogueId}/blocks/${blockId}`, { method: 'DELETE' })
      const dialogue = dialogues.value.find(d => d.id === dialogueId)
      if (dialogue) {
        dialogue.blocks = dialogue.blocks.filter(b => b.id !== blockId)
        dialogue.updatedAt = Date.now()
      }
    } catch {}
  }

  function resetToDefaults() {
    dialogues.value = []
  }

  return {
    dialogues,
    fetchDialogues,
    createDialogue,
    deleteDialogue,
    addBlock,
    removeBlock,
    resetToDefaults,
  }
})
