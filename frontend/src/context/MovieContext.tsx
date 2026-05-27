import { createContext, useContext, useState, useEffect, useMemo, useCallback, type ReactNode } from 'react';
import api from '../services/api';
import { allMovies as mockMovies } from '../data/movies';

export interface Movie {
    id: string;
    title: string;
    description: string;
    year: number;
    rating: number;
    imageUrl: string;
    duration: number | null;
    genres?: string[];
    backdropUrl?: string; // Optional
    releaseDate?: string; // Optional
    // Additional fields for compatibility
    category?: string;
    availableOn?: string[]; // Platforms
    director?: string;
    cast?: { name: string; role: string; imageUrl?: string }[];
    castNames?: string[]; // Used for fast searching
    trailerKey?: string;
}

interface MovieContextType {
    movies: Movie[];
    isLoading: boolean;
    error: string | null;
    getMovie: (id: string) => Movie | undefined;
    fetchMovieDetails: (id: string) => Promise<Movie | undefined>;
}

const MovieContext = createContext<MovieContextType | undefined>(undefined);

// --- Cache helpers ---
const CACHE_KEY = 'cinemate_movies_cache';
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

interface CachedData {
    movies: Movie[];
    timestamp: number;
}

function getCachedMovies(): Movie[] | null {
    try {
        const raw = sessionStorage.getItem(CACHE_KEY);
        if (!raw) return null;
        const cached: CachedData = JSON.parse(raw);
        // Return cached data even if stale (we'll revalidate in background)
        if (cached.movies && cached.movies.length > 0) {
            return cached.movies;
        }
    } catch {
        // Corrupt cache, ignore
    }
    return null;
}

function isCacheFresh(): boolean {
    try {
        const raw = sessionStorage.getItem(CACHE_KEY);
        if (!raw) return false;
        const cached: CachedData = JSON.parse(raw);
        return (Date.now() - cached.timestamp) < CACHE_TTL;
    } catch {
        return false;
    }
}

function setCachedMovies(movies: Movie[]): void {
    try {
        const data: CachedData = { movies, timestamp: Date.now() };
        sessionStorage.setItem(CACHE_KEY, JSON.stringify(data));
    } catch {
        // Storage full or unavailable, silently fail
    }
}

function mapApiMovie(m: any): Movie {
    return {
        ...m,
        year: m.year ? parseInt(m.year) : (m.releaseDate ? new Date(m.releaseDate).getFullYear() : 0),
        duration: m.duration,
    };
}

function convertMockMovies(): Movie[] {
    return mockMovies.map(m => ({
        ...m,
        year: parseInt(m.year),
        imageUrl: m.imageUrl || '',
    }));
}

export const MovieProvider = ({ children }: { children: ReactNode }) => {
    // Try to initialize from cache for instant display
    const cached = getCachedMovies();
    const [movies, setMovies] = useState<Movie[]>(cached || []);
    const [isLoading, setIsLoading] = useState(!cached); // No spinner if we have cache
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const controller = new AbortController();

        // If cache is still fresh, skip the API call entirely
        if (cached && isCacheFresh()) {
            setIsLoading(false);
            return;
        }
        
        const fetchMovies = async () => {
            try {
                const response = await api.get('/movies', { signal: controller.signal });
                if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    const apiMovies = response.data.map(mapApiMovie);
                    setMovies(apiMovies);
                    setCachedMovies(apiMovies);
                } else {
                    // Fallback to mock if API returns empty
                    console.log("API returned empty, using mock data");
                    const converted = convertMockMovies();
                    setMovies(converted);
                }
            } catch (err: any) {
                if (err.name === 'AbortError' || err.code === 'ERR_CANCELED') {
                    return; // Ignore intentional aborts
                }
                console.error("Failed to fetch movies from API, using mock data", err);
                // Only fall back to mock if we have no cache
                if (!cached) {
                    const converted = convertMockMovies();
                    setMovies(converted);
                }
                setError("Mode déconnecté / Mock Data");
            } finally {
                setIsLoading(false);
            }
        };

        fetchMovies();
        
        return () => {
            controller.abort();
        };
    }, []);

    const getMovie = useCallback((id: string) => {
        return movies.find(m => m.id === id);
    }, [movies]);

    const fetchMovieDetails = useCallback(async (id: string) => {
        const existingMovie = getMovie(id);
        
        // If we already have full details (cast exists), just return it
        if (existingMovie && existingMovie.cast) {
            return existingMovie;
        }

        try {
            const response = await api.get(`/movies/${id}`);
            const fullMovie = {
                ...existingMovie,      // Keep existing fields (castNames, director from list)
                ...response.data,      // Override with detailed data
                year: response.data.year ? parseInt(response.data.year) : (response.data.releaseDate ? new Date(response.data.releaseDate).getFullYear() : 0),
                duration: response.data.duration // Use real duration from API
            };

            // Update movies list with full details (merged, not replaced)
            setMovies(prev => prev.map(m => m.id === id ? fullMovie : m));
            return fullMovie;
        } catch (err) {
            console.error(`Failed to fetch details for movie ${id}`, err);
            return existingMovie;
        }
    }, [getMovie]);

    const contextValue = useMemo(() => ({
        movies,
        isLoading,
        error,
        getMovie,
        fetchMovieDetails
    }), [movies, isLoading, error, getMovie, fetchMovieDetails]);

    return (
        <MovieContext.Provider value={contextValue}>
            {children}
        </MovieContext.Provider>
    );
};

export const useMovies = () => {
    const context = useContext(MovieContext);
    if (context === undefined) {
        throw new Error('useMovies must be used within a MovieProvider');
    }
    return context;
};

export default MovieContext;
