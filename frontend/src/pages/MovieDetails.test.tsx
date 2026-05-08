import { render, screen, waitFor } from '@testing-library/react';
import { TextDecoder, TextEncoder } from 'util';
import { useMovies } from '../context/MovieContext';
import { useAuth } from '../context/AuthContext';

jest.mock('../context/MovieContext');
jest.mock('../context/AuthContext');
jest.mock('../services/api', () => ({
    put: jest.fn()
}));

Object.assign(global, { TextDecoder, TextEncoder });

const { MemoryRouter, Route, Routes } = require('react-router-dom');
const MovieDetails = require('./MovieDetails').default;

const mockUseMovies = useMovies as jest.Mock;
const mockUseAuth = useAuth as jest.Mock;
const mockFetchMovieDetails = jest.fn();
const mockGetMovie = jest.fn();
const mockUpdateUser = jest.fn();

const mainMovie = {
    id: 'matrix',
    title: 'Matrix',
    description: 'Un pirate informatique decouvre que son monde est une simulation.',
    year: 1999,
    rating: 8.7,
    imageUrl: '/matrix.jpg',
    duration: 136,
    genres: ['Action', 'Science-fiction'],
    category: 'Science-fiction',
    availableOn: ['Netflix', 'Prime Video'],
    director: 'Lana Wachowski',
    cast: [
        { name: 'Keanu Reeves', role: 'Neo' },
        { name: 'Carrie-Anne Moss', role: 'Trinity' }
    ]
};

const recommendedMovie = {
    id: 'inception',
    title: 'Inception',
    description: 'Un voleur infiltre les reves.',
    year: 2010,
    rating: 8.8,
    imageUrl: '/inception.jpg',
    duration: 148,
    genres: ['Science-fiction'],
    category: 'Science-fiction'
};

const renderMovieDetails = () => {
    return render(
        <MemoryRouter initialEntries={['/movie/matrix']}>
            <Routes>
                <Route path="/movie/:id" element={<MovieDetails />} />
            </Routes>
        </MemoryRouter>
    );
};

describe('MovieDetails - affichage de la page film', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        window.scrollTo = jest.fn();
        mockGetMovie.mockReturnValue(mainMovie);
        mockFetchMovieDetails.mockResolvedValue(mainMovie);
        mockUseMovies.mockReturnValue({
            movies: [mainMovie, recommendedMovie],
            isLoading: false,
            error: null,
            getMovie: mockGetMovie,
            fetchMovieDetails: mockFetchMovieDetails
        });
        mockUseAuth.mockReturnValue({
            user: {
                username: 'cinephile',
                email: 'cinephile@example.com',
                platforms: [],
                favoriteGenres: [],
                averageTimeAvailable: null,
                watchlist: [],
                agenda: []
            },
            updateUser: mockUpdateUser
        });
    });

    it('affiche les informations principales, le casting et les recommandations du film', async () => {
        renderMovieDetails();

        expect(await screen.findByRole('heading', { name: 'Matrix' })).toBeInTheDocument();
        expect(mockGetMovie).toHaveBeenCalledWith('matrix');
        expect(mockFetchMovieDetails).toHaveBeenCalledWith('matrix');

        expect(screen.getByAltText('Matrix')).toHaveAttribute('src', '/matrix.jpg');
        expect(screen.getByText('Science-fiction')).toBeInTheDocument();
        expect(screen.getByText('Disponible sur :')).toBeInTheDocument();
        expect(screen.getByText('Netflix, Prime Video')).toBeInTheDocument();
        expect(screen.getByText('8.7')).toBeInTheDocument();
        expect(screen.getByText('1999')).toBeInTheDocument();
        expect(screen.getByText('2h 16m')).toBeInTheDocument();
        expect(screen.getByText(mainMovie.description)).toBeInTheDocument();

        expect(screen.getByRole('button', { name: /Regarder/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Watchlist/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Planifier/i })).toBeInTheDocument();

        expect(screen.getByText('Keanu Reeves')).toBeInTheDocument();
        expect(screen.getByText('Neo')).toBeInTheDocument();
        expect(screen.getByText('Carrie-Anne Moss')).toBeInTheDocument();
        expect(screen.getByText('Trinity')).toBeInTheDocument();
        expect(screen.getByText('Lana Wachowski')).toBeInTheDocument();
        expect(screen.getByText('Réalisateur')).toBeInTheDocument();

        expect(screen.getByRole('heading', { name: 'Autres films similaires' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Inception' })).toBeInTheDocument();

        await waitFor(() => {
            expect(window.scrollTo).toHaveBeenCalledWith(0, 0);
        });
    });
});
