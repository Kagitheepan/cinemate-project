import { renderHook, act, waitFor } from '@testing-library/react';
import { AuthProvider, useAuth } from './AuthContext';
import api from '../services/api';
// Mock the API module
jest.mock('../services/api');

const mockApi = api as jest.Mocked<typeof api>;

const waitForPendingAuthUpdates = async () => {
    await act(async () => {
        await Promise.resolve();
    });
};

describe('AuthContext - Connexion et Inscription', () => {
    beforeEach(() => {
        window.localStorage.clear();
        jest.clearAllMocks();
        mockApi.get.mockResolvedValue({ data: {} });
    });

    const wrapper = ({ children }: { children: React.ReactNode }) => (
        <AuthProvider>{children}</AuthProvider>
    );

    it('devrait initialiser avec un utilisateur non connecté si aucun token', async () => {
        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        expect(result.current.user).toBeNull();
        expect(result.current.isAuthenticated).toBe(false);
        expect(result.current.token).toBeNull();
    });

    it('devrait permettre la connexion et mettre à jour le state', async () => {
        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        const mockUser = {
            username: 'testuser',
            email: 'test@example.com',
            platforms: [],
            favoriteGenres: [],
            averageTimeAvailable: 120,
            watchlist: [],
            agenda: []
        };

        mockApi.get.mockResolvedValueOnce({ data: mockUser });

        act(() => {
            result.current.login('fake-token-123', mockUser);
        });

        await waitFor(() => expect(mockApi.get).toHaveBeenCalledWith('/profile'));
        await waitFor(() => expect(result.current.user).toEqual(mockUser));

        expect(result.current.isAuthenticated).toBe(true);
        expect(result.current.user).toEqual(mockUser);
        expect(result.current.token).toBe('fake-token-123');
        expect(window.localStorage.getItem('token')).toBe('fake-token-123');
    });

    it('devrait permettre la déconnexion et nettoyer le state', async () => {
        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        const mockUser = {
            username: 'testuser',
            email: 'test@example.com',
            platforms: [],
            favoriteGenres: [],
            averageTimeAvailable: 120,
            watchlist: [],
            agenda: []
        };

        mockApi.get.mockResolvedValueOnce({ data: mockUser });

        // Connecter l'utilisateur
        act(() => {
            result.current.login('fake-token-123', mockUser);
        });

        await waitFor(() => expect(result.current.user).toEqual(mockUser));
        expect(result.current.isAuthenticated).toBe(true);

        // Déconnecter
        act(() => {
            result.current.logout();
        });

        await waitForPendingAuthUpdates();

        expect(result.current.isAuthenticated).toBe(false);
        expect(result.current.user).toBeNull();
        expect(result.current.token).toBeNull();
        expect(window.localStorage.getItem('token')).toBeNull();
    });

    it('devrait récupérer le profil si un token est présent au démarrage', async () => {
        window.localStorage.setItem('token', 'existing-token');

        const mockUser = {
            username: 'existinguser',
            email: 'existing@example.com',
            platforms: [],
            favoriteGenres: [],
            averageTimeAvailable: null,
            watchlist: [],
            agenda: []
        };

        mockApi.get.mockResolvedValueOnce({ data: mockUser });

        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        expect(mockApi.get).toHaveBeenCalledWith('/profile');
        expect(result.current.user).toEqual(mockUser);
        expect(result.current.isAuthenticated).toBe(true);
        expect(result.current.isLoading).toBe(false);
    });

    it('devrait gérer les erreurs de récupération du profil', async () => {
        window.localStorage.setItem('token', 'invalid-token');
        // On mocke console.error pour cacher l'erreur attendue dans la sortie des tests
        jest.spyOn(console, 'error').mockImplementation(() => {});
        mockApi.get.mockRejectedValueOnce(new Error('Invalid token'));

        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        expect(result.current.user).toBeNull();
        expect(result.current.isAuthenticated).toBe(false);
        expect(window.localStorage.getItem('token')).toBeNull();

        await waitForPendingAuthUpdates();
        
        // Nettoyage
        (console.error as jest.Mock).mockRestore();
    });
});
