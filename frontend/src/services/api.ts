import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || '/api';

const api = axios.create({
    baseURL: API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
    withCredentials: true, // Nécessaire pour envoyer/recevoir les cookies (JWT + CSRF)
});

/**
 * Lit un cookie par son nom.
 * Utilisé pour récupérer le token CSRF depuis le cookie CSRF-TOKEN.
 */
function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Initialise le cookie CSRF en appelant l'endpoint dédié.
 * Doit être appelé au démarrage de l'application.
 */
export async function initCsrf(): Promise<void> {
    try {
        await api.get('/csrf-cookie');
    } catch (error) {
        console.error('Failed to initialize CSRF cookie:', error);
    }
}

// Intercepteur de requête : ajoute le token CSRF sur les requêtes mutantes
api.interceptors.request.use(
    (config) => {
        // Ajouter le token CSRF sur les requêtes mutantes (POST, PUT, PATCH, DELETE)
        const method = config.method?.toUpperCase();
        if (method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            const csrfToken = getCookie('CSRF-TOKEN');
            if (csrfToken) {
                config.headers['X-CSRF-TOKEN'] = csrfToken;
            }
        }

        return config;
    },
    (error) => Promise.reject(error)
);

// Intercepteur de réponse : propage les erreurs (le JWT HttpOnly est géré par le navigateur)
api.interceptors.response.use(
    (response) => response,
    (error) => Promise.reject(error)
);

export default api;
