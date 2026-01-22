import axios from 'axios'

// ✅ Base URL real del sistema (sirve si estás en subcarpeta /registro-personal-cv/public)
const BASE =
  String(
    (window && window.BASE_URL) ||
    (document.querySelector('meta[name="app-base-url"]')?.getAttribute('content')) ||
    import.meta.env.VITE_BASE_URL ||
    ''
  ).replace(/\/+$/, '')

// Create an axios instance with custom configuration
const url = axios.create({
  // Base URL para todas las peticiones
  baseURL: BASE,
  // Request timeout
  timeout: 300000,
  // Default headers
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  // Enviar cookies/autenticación
  withCredentials: true,
})

// Interceptor para incluir el token CSRF en cada petición
url.interceptors.request.use(config => {
  const token = document.querySelector('meta[name="csrf-token"]')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token.content
  }
  return config
})

/*
// Si quieres manejar expiración de sesión, descomenta esto
url.interceptors.response.use(
  response => response,
  error => {
    if (error.response && error.response.status === 401) {
      localStorage.clear();
      sessionStorage.clear();
      window.location.href = `${BASE}/login`;
      return Promise.reject('Sesión expirada');
    }
    return Promise.reject(error);
  }
);
*/

export default url
