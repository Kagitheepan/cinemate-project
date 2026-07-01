import api from './api';

describe('api.ts - Intercepteurs Axios', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('ajoute le token CSRF sur les requêtes mutantes (POST)', async () => {
        // Simuler un cookie CSRF
        Object.defineProperty(document, 'cookie', {
            writable: true,
            value: 'CSRF-TOKEN=test-csrf-token-123',
        });

        const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;

        const config = { headers: {} as any, method: 'post' };
        const result = await requestInterceptor(config);

        expect(result.headers['X-CSRF-TOKEN']).toBe('test-csrf-token-123');
    });

    it('n\'ajoute pas le token CSRF sur les requêtes GET', async () => {
        Object.defineProperty(document, 'cookie', {
            writable: true,
            value: 'CSRF-TOKEN=test-csrf-token-123',
        });

        const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;

        const config = { headers: {} as any, method: 'get' };
        const result = await requestInterceptor(config);

        expect(result.headers['X-CSRF-TOKEN']).toBeUndefined();
    });

    it('rejette correctement l\'erreur de requête', async () => {
        const requestErrorInterceptor = (api.interceptors.request as any).handlers[0].rejected;
        const error = new Error('Request Error');
        await expect(requestErrorInterceptor(error)).rejects.toEqual(error);
    });

    it('retourne la réponse correctement sans la modifier', async () => {
        const responseInterceptor = (api.interceptors.response as any).handlers[0].fulfilled;
        const response = { data: 'ok' };
        expect(responseInterceptor(response)).toEqual(response);
    });

    it('propage les erreurs de réponse (401, 500, etc.)', async () => {
        const responseErrorInterceptor = (api.interceptors.response as any).handlers[0].rejected;

        const error = { response: { status: 401 } };
        await expect(responseErrorInterceptor(error)).rejects.toEqual(error);

        const error500 = { response: { status: 500 } };
        await expect(responseErrorInterceptor(error500)).rejects.toEqual(error500);
    });
});
