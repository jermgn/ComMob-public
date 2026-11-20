export interface AuthUser {
  email: string
  roles: string[]
}

export interface LoginPendingResponse {
  requires_2fa: true
}

export interface LoginSuccessResponse {
  token: string
  user: AuthUser
}

export type LoginResponse = LoginPendingResponse | LoginSuccessResponse

export interface RegisterResponse {
  token: string
  user: AuthUser
}

export interface VerifyLoginResponse {
  token: string
  user: AuthUser
}
