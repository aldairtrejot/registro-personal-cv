import axios from 'axios'

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Si tu auth es por sesión y estás en el mismo dominio, normalmente NO necesitas esto.
// Si estás en dominios/subdominios diferentes, entonces sí:
// axios.defaults.withCredentials = true

export default axios
