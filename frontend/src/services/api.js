const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
const TOKEN_KEY = 'ai_task_manager_token'

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY)
}

export async function api(path, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {}),
    ...options.headers,
  }

  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
    body: options.body ? JSON.stringify(options.body) : undefined,
  })

  if (response.status === 204) {
    return null
  }

  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
    const error = new Error(payload.message || 'Request failed')
    error.status = response.status
    error.errors = payload.errors || {}
    throw error
  }

  return payload
}

export function queryString(filters) {
  const params = new URLSearchParams()

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.append(key, value)
    }
  })

  const query = params.toString()

  return query ? `?${query}` : ''
}
