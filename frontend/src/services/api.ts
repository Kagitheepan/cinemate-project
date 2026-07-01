import axios from 'axios';

// @ts-ignore
const API_URL = process.env.VITE_API_URL || '/api';

const api = axios.create({
    baseURL: API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
    withCredentials: true, // Nécessaire pour envoyer/recevoir les cookies (JWT + CSRF)
});

let storedCsrfToken: string | null = null;

/**
 * Lit un cookie par son nom. (Garde pour rétrocompatibilité locale)
 */
function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Initialise le cookie CSRF en appelant l'endpoint dédié.
 */
export async function initCsrf(): Promise<void> {
    try {
        const response = await api.get('/csrf-cookie');
        if (response.data && response.data.token) {
            storedCsrfToken = response.data.token;
        }
    } catch (error) {
        console.error('Failed to initialize CSRF cookie:', error);
    }
}

// Intercepteur de requête : ajoute le token CSRF sur les requêtes mutantes
api.interceptors.request.use(
    (config) => {
        const method = config.method?.toUpperCase();
        if (method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            // On privilégie le token reçu dans la requête (cross-domain), sinon le cookie (local)
            const csrfToken = storedCsrfToken || getCookie('CSRF-TOKEN');
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
