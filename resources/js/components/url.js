export const BASE_URL = String(
  (window && window.BASE_URL) ||
  (document.querySelector('meta[name="app-base-url"]')?.getAttribute('content')) ||
  import.meta.env.VITE_BASE_URL ||
  ''
).replace(/\/+$/, '')
