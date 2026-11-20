export interface CustomContext {
  id: string
  label: string
}

export interface UserProfile {
  firstName: string | null
  lastName: string | null
  age: number | null
  gender: 'male' | 'female' | null
  aiDescription: string | null
  savedPhrases: string[]
  customContexts: CustomContext[]
}
