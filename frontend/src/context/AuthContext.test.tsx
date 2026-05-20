import { renderHook, act, waitFor } from '@testing-library/react';
import { AuthProvider, useAuth } from './AuthContext';
import api from '../services/api';

jest.mock('../services/api');

const mockApi = api as jest.Mocked<typeof api>;

describe('AuthContext - Connexion et Inscription', () => {
    beforeEach(() => {
        window.localStorage.clear();
        jest.clearAllMocks();
        mockApi.get.mockRejectedValue(new Error('No auth cookie'));
        mockApi.post.mockResolvedValue({ data: { success: true } });
    });

    const wrapper = ({ children }: { children: React.ReactNode }) => (
        <AuthProvider>{children}</AuthProvider>
    );

    it('devrait initialiser avec un utilisateur non connecte si aucun cookie valide', async () => {
        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        expect(result.current.user).toBeNull();
        expect(result.current.isAuthenticated).toBe(false);
        expect(window.localStorage.getItem('token')).toBeNull();
    });

    it('devrait permettre la connexion et mettre a jour le state sans stocker de JWT', async () => {
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

        act(() => {
            result.current.login(mockUser);
        });

        expect(result.current.isAuthenticated).toBe(true);
        expect(result.current.user).toEqual(mockUser);
        expect(window.localStorage.getItem('token')).toBeNull();
    });

    it('devrait permettre la deconnexion et nettoyer le state', async () => {
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

        act(() => {
            result.current.login(mockUser);
        });

        expect(result.current.isAuthenticated).toBe(true);

        await act(async () => {
            await result.current.logout();
        });

        expect(mockApi.post).toHaveBeenCalledWith('/logout');
        expect(result.current.isAuthenticated).toBe(false);
        expect(result.current.user).toBeNull();
        expect(window.localStorage.getItem('token')).toBeNull();
    });

    it('devrait recuperer le profil au demarrage si le cookie de session est valide', async () => {
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
    });

    it('devrait gerer les erreurs de recuperation du profil', async () => {
        mockApi.get.mockRejectedValueOnce(new Error('Invalid auth cookie'));

        const { result } = renderHook(() => useAuth(), { wrapper });
        await waitFor(() => expect(result.current.isLoading).toBe(false));

        expect(result.current.user).toBeNull();
        expect(result.current.isAuthenticated).toBe(false);
        expect(window.localStorage.getItem('token')).toBeNull();
    });
});
