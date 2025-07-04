import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar la URL base dinámicamente
window.axios.defaults.baseURL = window.location.origin;

// Interceptor para manejar tokens CSRF
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Si estamos usando ngrok, añadir header para saltar la página de advertencia
if (window.location.hostname.includes('ngrok')) {
    window.axios.defaults.headers.common['ngrok-skip-browser-warning'] = 'true';
}
