import { render, screen, waitFor, act, fireEvent } from '@testing-library/react';
import React from 'react';
import MovieContext, { MovieProvider, useMovies } from './MovieContext';
import api from '../services/api';

jest.mock('../services/api');
const mockedApi = api as jest.Mocked<typeof api>;

// Composant de test pour consommer le contexte
const TestComponent = () => {
    const { movies, isLoading, fetchMovieDetails } = useMovies();
    return (
        <div>
            <div data-testid="loading">{isLoading ? 'true' : 'false'}</div>
            <div data-testid="movie-count">{movies.length}</div>
            <div data-testid="movie2-duration">{movies.find(m => m.id === '2')?.duration || 'none'}</div>
            <button onClick={() => fetchMovieDetails('1')}>Fetch 1</button>
            <button onClick={() => fetchMovieDetails('2')}>Fetch 2</button>
        </div>
    );
};

describe('MovieContext - Gestion du Cache', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        sessionStorage.clear();
    });

    it('L\'affichage des films à partir des infos présents en cache', async () => {
        const cachedMovies = [{ id: '1', title: 'Cached Movie' }];
        // Cache valide (timestamp récent)
        sessionStorage.setItem('cinemate_movies_cache', JSON.stringify({
            movies: cachedMovies,
            timestamp: Date.now()
        }));

        render(
            <MovieProvider>
                <TestComponent />
            </MovieProvider>
        );

        // Vérifie qu'aucun appel API n'a été fait
        expect(mockedApi.get).not.toHaveBeenCalled();
        expect(screen.getByTestId('movie-count').textContent).toBe('1');
        expect(screen.getByTestId('loading').textContent).toBe('false');
    });

    it('Est ce que les caches du films ont été refresh (si expiré)', async () => {
        const cachedMovies = [{ id: '1', title: 'Old Cached Movie' }];
        // Cache expiré (vieux de 10 minutes, le TTL est de 5 min)
        sessionStorage.setItem('cinemate_movies_cache', JSON.stringify({
            movies: cachedMovies,
            timestamp: Date.now() - (10 * 60 * 1000) 
        }));

        mockedApi.get.mockResolvedValueOnce({ data: [{ id: '2', title: 'New Movie' }] });

        render(
            <MovieProvider>
                <TestComponent />
            </MovieProvider>
        );

        // L'API doit être appelée pour rafraîchir les données
        await waitFor(() => {
            expect(mockedApi.get).toHaveBeenCalledWith('/movies', expect.any(Object));
        });
        
        await waitFor(() => {
            expect(screen.getByTestId('movie-count').textContent).toBe('1');
        });
        
        // Le cache sessionStorage doit avoir été mis à jour
        const newCache = JSON.parse(sessionStorage.getItem('cinemate_movies_cache') || '{}');
        expect(newCache.movies[0].id).toBe('2');
    });

    it('Vérifier si le film a des caches (fetchMovieDetails retourne immédiatement)', async () => {
        // Le film 1 a déjà la propriété `cast`, ce qui indique que les détails complets sont en cache
        const cachedMovies = [{ id: '1', title: 'Cached Movie', cast: [{ name: 'Actor' }] }];
        sessionStorage.setItem('cinemate_movies_cache', JSON.stringify({
            movies: cachedMovies,
            timestamp: Date.now() 
        }));

        render(
            <MovieProvider>
                <TestComponent />
            </MovieProvider>
        );

        const fetch1Btn = screen.getByText('Fetch 1');
        
        await act(async () => {
            fireEvent.click(fetch1Btn);
        });

        // Comme le film a déjà les détails (`cast` existe), l'API ne doit PAS être appelée
        expect(mockedApi.get).not.toHaveBeenCalledWith('/movies/1');
    });

    it('Appelle l\'API si le film n\'a pas les détails en cache et met à jour l\'état', async () => {
        // Le film 2 n'a PAS la propriété `cast`
        const cachedMovies = [{ id: '2', title: 'Incomplete Movie' }];
        sessionStorage.setItem('cinemate_movies_cache', JSON.stringify({
            movies: cachedMovies,
            timestamp: Date.now() 
        }));

        mockedApi.get.mockResolvedValueOnce({ data: { id: '2', title: 'Incomplete Movie', duration: 120, cast: [{ name: 'Actor 1' }] } });

        render(
            <MovieProvider>
                <TestComponent />
            </MovieProvider>
        );

        const fetch2Btn = screen.getByText('Fetch 2');
        
        await act(async () => {
            fireEvent.click(fetch2Btn);
        });

        // L'API DOIT être appelée
        expect(mockedApi.get).toHaveBeenCalledWith('/movies/2');
        
        // On vérifie que les infos du film ont été "refresh" dans le state (duration est passé à 120)
        await waitFor(() => {
            expect(screen.getByTestId('movie2-duration').textContent).toBe('120');
        });
    });

    it('Gère l\'erreur API lors du refresh des infos d\'un film et retourne le cache existant', async () => {
        const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        const cachedMovies = [{ id: '2', title: 'Incomplete Movie' }];
        sessionStorage.setItem('cinemate_movies_cache', JSON.stringify({
            movies: cachedMovies,
            timestamp: Date.now() 
        }));

        mockedApi.get.mockRejectedValueOnce(new Error('API Down'));

        render(
            <MovieProvider>
                <TestComponent />
            </MovieProvider>
        );

        await act(async () => {
            fireEvent.click(screen.getByText('Fetch 2'));
        });

        expect(consoleSpy).toHaveBeenCalledWith('Failed to fetch details for movie 2', expect.any(Error));
        consoleSpy.mockRestore();
    });

    it('throw une erreur si useMovies est utilisé hors du Provider', () => {
        const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        expect(() => render(<TestComponent />)).toThrow('useMovies must be used within a MovieProvider');
        consoleSpy.mockRestore();
    });
});
