import axios from 'axios';

// Create an axios instance with custom configuration
const url = axios.create({
    // Base URL para todas las peticiones
    baseURL: import.meta.env.VITE_BASE_URL || '',
    // Request timeout
    timeout: 300000,
    // Default headers
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    },
    // Enviar cookies/autenticación
    withCredentials: true,
});

// Interceptor para incluir el token CSRF en cada petición
url.interceptors.request.use(config => {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
});

/*
// Si quieres manejar expiración de sesión, descomenta esto
url.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.clear();
            sessionStorage.clear();
            // Ojo con la ruta:
            window.location.href = '/registro-personal-cv/public/login';
            return Promise.reject('Sesión expirada');
        }
        return Promise.reject(error);
    }
);
*/

export default url;
