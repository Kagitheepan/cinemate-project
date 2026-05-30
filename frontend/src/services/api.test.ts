import api from './api';

describe('api.ts - Intercepteurs Axios', () => {
    beforeEach(() => {
        localStorage.clear();
        jest.clearAllMocks();
    });

    it('ajoute le token d\'authentification dans les headers si stocké', async () => {
        localStorage.setItem('token', 'fake-jwt-token');
        
        // On récupère la fonction passée à api.interceptors.request.use
        const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;
        
        const config = { headers: {} as any };
        const result = await requestInterceptor(config);
        
        expect(result.headers.Authorization).toBe('Bearer fake-jwt-token');
    });

    it('n\'ajoute pas de token si aucun n\'est stocké', async () => {
        // Le storage est vide par défaut (voir beforeEach)
        const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;
        
        const config = { headers: {} as any };
        const result = await requestInterceptor(config);
        
        expect(result.headers.Authorization).toBeUndefined();
    });

    it('gère les tokens expirés (401) en supprimant le token du storage', async () => {
        localStorage.setItem('token', 'expired-token');
        
        // On récupère la fonction d'erreur passée à api.interceptors.response.use
        const responseErrorInterceptor = (api.interceptors.response as any).handlers[0].rejected;
        
        const error = {
            response: {
                status: 401
            }
        };
        
        await expect(responseErrorInterceptor(error)).rejects.toEqual(error);
        expect(localStorage.getItem('token')).toBeNull(); // Le token doit être supprimé
    });
    
    it('ne supprime pas le token pour les autres erreurs (ex: 500)', async () => {
        localStorage.setItem('token', 'valid-token');
        
        const responseErrorInterceptor = (api.interceptors.response as any).handlers[0].rejected;
        
        const error = {
            response: {
                status: 500
            }
        };
        
        await expect(responseErrorInterceptor(error)).rejects.toEqual(error);
        expect(localStorage.getItem('token')).toBe('valid-token'); // Le token doit rester intact
    });

    it('rejette correctement l\'erreur de requête', async () => {
        // On teste le rejet de la requête (handlers[0].rejected)
        const requestErrorInterceptor = (api.interceptors.request as any).handlers[0].rejected;
        const error = new Error('Request Error');
        await expect(requestErrorInterceptor(error)).rejects.toEqual(error);
    });

    it('retourne la réponse correctement sans la modifier', async () => {
        // On teste le succès de la réponse (handlers[0].fulfilled)
        const responseInterceptor = (api.interceptors.response as any).handlers[0].fulfilled;
        const response = { data: 'ok' };
        expect(responseInterceptor(response)).toEqual(response);
    });
});
