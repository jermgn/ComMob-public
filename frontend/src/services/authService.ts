import type { LoginResponse, RegisterResponse, VerifyLoginResponse } from '@/types/auth'

const API_BASE = 'http://localhost:8080/api'

export async function loginRequest(email: string, password: string): Promise<LoginResponse> {
  const res = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  })

  const data = await res.json()
  if (!res.ok) throw new Error(data.error || 'Identifiants incorrects')
  return data
}

export async function verifyLoginRequest(email: string, code: string): Promise<VerifyLoginResponse> {
  const res = await fetch(`${API_BASE}/auth/verify-login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code }),
  })

  const data = await res.json()
  if (!res.ok) throw new Error(data.error || 'Code invalide ou expiré')
  return data
}

export async function registerRequest(email: string, password: string): Promise<RegisterResponse> {
  const res = await fetch(`${API_BASE}/auth/register`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  })

  const data = await res.json()
  if (!res.ok) throw new Error(data.error || "Erreur lors de l'inscription")
  return data
}
