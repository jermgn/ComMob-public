import { useAuthStore } from '@/stores/auth'

const API_BASE = 'http://localhost:8080/api'

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  // Lire le token depuis le store Pinia (isolé par onglet/session),
  // pas depuis localStorage (clé globale partagée entre tous les onglets)
  const authStore = useAuthStore()
  const token = authStore.token

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  })

  if (res.status === 401) {
    authStore.logout()
    window.location.href = '/login'
    throw new ApiError(401, 'Session expirée')
  }

  if (!res.ok) {
    const body = await res.json().catch(() => ({ error: 'Erreur inconnue' }))
    throw new ApiError(res.status, body.error || 'Erreur serveur')
  }

  if (res.status === 204 || res.headers.get('content-length') === '0') {
    return undefined as unknown as T
  }

  return res.json()
}
