import { createContext, useContext, useState, useEffect, type ReactNode } from 'react';
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
}

interface MovieContextType {
    movies: Movie[];
    isLoading: boolean;
    error: string | null;
    getMovie: (id: string) => Movie | undefined;
    fetchMovieDetails: (id: string) => Promise<Movie | undefined>;
}

const MovieContext = createContext<MovieContextType | undefined>(undefined);

export const MovieProvider = ({ children }: { children: ReactNode }) => {
    const [movies, setMovies] = useState<Movie[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        
        const fetchMovies = async () => {
            try {
                const response = await api.get('/movies', { signal: controller.signal });
                if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    const apiMovies = response.data.map((m: any) => ({
                        ...m,
                        year: m.year ? parseInt(m.year) : (m.releaseDate ? new Date(m.releaseDate).getFullYear() : 0),
                        duration: m.duration // Use real duration from API
                    }));
                    setMovies(apiMovies);
                } else {
                    // Fallback to mock if API returns empty
                    console.log("API returned empty, using mock data");
                    const convertedMock = mockMovies.map(m => ({
                        ...m,
                        year: parseInt(m.year),
                        imageUrl: m.imageUrl || '' // Ensure string
                    }));
                    setMovies(convertedMock); 
                }
            } catch (err: any) {
                if (err.name === 'AbortError' || err.code === 'ERR_CANCELED') {
                    return; // Ignore intentional aborts
                }
                console.error("Failed to fetch movies from API, using mock data", err);
                const convertedMock = mockMovies.map(m => ({
                    ...m,
                    year: parseInt(m.year),
                    imageUrl: m.imageUrl || '' // Ensure string
                }));
                setMovies(convertedMock);
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

    const getMovie = (id: string) => {
        return movies.find(m => m.id === id);
    };

    const fetchMovieDetails = async (id: string) => {
        const existingMovie = getMovie(id);
        
        // If we already have full details (description exists), just return it
        if (existingMovie && existingMovie.description) {
            return existingMovie;
        }

        try {
            const response = await api.get(`/movies/${id}`);
            const fullMovie = {
                ...response.data,
                year: response.data.year ? parseInt(response.data.year) : (response.data.releaseDate ? new Date(response.data.releaseDate).getFullYear() : 0),
                duration: response.data.duration // Use real duration from API
            };

            // Update movies list with full details
            setMovies(prev => prev.map(m => m.id === id ? fullMovie : m));
            return fullMovie;
        } catch (err) {
            console.error(`Failed to fetch details for movie ${id}`, err);
            return existingMovie;
        }
    };

    return (
        <MovieContext.Provider value={{ movies, isLoading, error, getMovie, fetchMovieDetails }}>
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
