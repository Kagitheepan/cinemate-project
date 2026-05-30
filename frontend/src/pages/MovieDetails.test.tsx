import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { TextDecoder, TextEncoder } from 'util';
import { useMovies } from '../context/MovieContext';
import { useAuth } from '../context/AuthContext';

jest.mock('../context/MovieContext');
jest.mock('../context/AuthContext');
jest.mock('../services/api', () => ({
    put: jest.fn()
}));

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => {
    const originalModule = jest.requireActual('react-router-dom');
    return {
        ...originalModule,
        useNavigate: () => mockNavigate,
    };
});

jest.mock('../components/AddEventModal', () => {
    return function DummyModal({ isOpen, onAddEvent }: any) {
        if (!isOpen) return null;
        return <button data-testid="submit-event" onClick={() => onAddEvent({ movieId: 'matrix', title: 'Matrix', date: new Date() })}>Valider event</button>;
    };
});

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
        expect(screen.getByText('Disponible en streaming sur')).toBeInTheDocument();
        expect(screen.getByText('Netflix')).toBeInTheDocument();
        expect(screen.getByText('Prime Video')).toBeInTheDocument();
        expect(screen.getByText('8.7')).toBeInTheDocument();
        expect(screen.getByText('1999')).toBeInTheDocument();
        expect(screen.getByText('2h 16m')).toBeInTheDocument();
        expect(screen.getByText(mainMovie.description)).toBeInTheDocument();

        expect(screen.getByRole('button', { name: /Bande Annonce/i })).toBeInTheDocument();
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

    it('applique les bons styles selon la plateforme (lignes 11-23)', async () => {
        const movieWithPlatforms = { ...mainMovie, availableOn: ['Netflix', 'Prime Video', 'Disney+', 'Canal+', 'Apple TV', 'Cinéma'] };
        mockGetMovie.mockReturnValue(movieWithPlatforms);
        mockFetchMovieDetails.mockResolvedValue(movieWithPlatforms);
        renderMovieDetails();

        expect(await screen.findByText('Netflix')).toHaveClass('bg-[#E50914]');
        expect(screen.getByText('Prime Video')).toHaveClass('bg-[#00A8E1]');
        expect(screen.getByText('Disney+')).toHaveClass('bg-[#113CCF]');
        expect(screen.getByText('Canal+')).toHaveClass('bg-black');
        expect(screen.getByText('Apple TV')).toHaveClass('bg-white');
        expect(screen.getByText('Cinéma')).toHaveClass('bg-gradient-to-r', 'from-yellow-500');
    });

    it('affiche une erreur si l\'utilisateur non connecté tente d\'ajouter à la watchlist', async () => {
        window.alert = jest.fn();
        mockUseAuth.mockReturnValue({ user: null, updateUser: mockUpdateUser });
        renderMovieDetails();
        
        const watchlistBtn = await screen.findByRole('button', { name: /Watchlist/i });
        fireEvent.click(watchlistBtn);
        
        expect(window.alert).toHaveBeenCalledWith("Veuillez vous connecter pour gérer votre Watchlist.");
    });

    it('gère les erreurs de l\'API lors de l\'ajout à la watchlist', async () => {
        const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
        const mockPut = jest.requireMock('../services/api').put;
        mockPut.mockRejectedValueOnce(new Error('API Error'));
        
        renderMovieDetails();
        
        const watchlistBtn = await screen.findByRole('button', { name: /Watchlist/i });
        fireEvent.click(watchlistBtn);
        
        await waitFor(() => {
            expect(consoleSpy).toHaveBeenCalledWith("Failed to update watchlist", expect.any(Error));
        });
        consoleSpy.mockRestore();
    });

    it('affiche une erreur si l\'utilisateur non connecté tente de planifier', async () => {
        window.alert = jest.fn();
        mockUseAuth.mockReturnValue({ user: null, updateUser: mockUpdateUser }); 
        renderMovieDetails();
        
        const planifierBtn = await screen.findByRole('button', { name: /Planifier/i });
        fireEvent.click(planifierBtn);
        
        expect(window.alert).toHaveBeenCalledWith("Veuillez vous connecter pour planifier un film.");
    });

    it('ajoute le film à l\'agenda via la modale', async () => {
        window.alert = jest.fn();
        renderMovieDetails();
        
        const planifierBtn = await screen.findByRole('button', { name: /Planifier/i });
        fireEvent.click(planifierBtn);
        
        const submitEvent = await screen.findByTestId('submit-event');
        fireEvent.click(submitEvent);
        
        await waitFor(() => {
            const mockPut = jest.requireMock('../services/api').put;
            expect(mockPut).toHaveBeenCalledWith('/profile', expect.objectContaining({
                agenda: expect.arrayContaining([
                    expect.objectContaining({ movieId: 'matrix' })
                ])
            }));
            expect(window.alert).toHaveBeenCalledWith("Événement ajouté à l'agenda avec succès !");
        });
    });

    it('retourne à la page précédente au clic sur retour', async () => {
        renderMovieDetails();
        const backBtn = await screen.findByRole('button', { name: /Retour/i });
        fireEvent.click(backBtn);
        expect(mockNavigate).toHaveBeenCalledWith(-1);
    });

    it('ouvre et ferme la modale vidéo de la bande annonce', async () => {
        const movieWithTrailer = { ...mainMovie, trailerKey: 'dQw4w9WgXcQ' };
        mockGetMovie.mockReturnValue(movieWithTrailer);
        mockFetchMovieDetails.mockResolvedValue(movieWithTrailer);
        renderMovieDetails();
        
        const trailerBtn = await screen.findByRole('button', { name: /Bande Annonce/i });
        fireEvent.click(trailerBtn);
        
        const iframe = await screen.findByTitle('Trailer');
        expect(iframe).toHaveAttribute('src', 'https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1');
        
        // Clic sur le bouton fermer
        const closeBtn = iframe.parentElement?.previousElementSibling as HTMLElement;
        fireEvent.click(closeBtn);
        
        expect(screen.queryByTitle('Trailer')).not.toBeInTheDocument();
    });

    it('désactive le bouton si la bande annonce est indisponible', async () => {
        const movieWithoutTrailer = { ...mainMovie, trailerKey: undefined };
        mockGetMovie.mockReturnValue(movieWithoutTrailer);
        mockFetchMovieDetails.mockResolvedValue(movieWithoutTrailer);
        renderMovieDetails();
        
        const trailerBtn = await screen.findByRole('button', { name: /Bande Annonce \(Indisponible\)/i });
        expect(trailerBtn).toHaveClass('opacity-50', 'cursor-not-allowed');
        
        fireEvent.click(trailerBtn);
        expect(screen.queryByTitle('Trailer')).not.toBeInTheDocument();
    });

    it('affiche le spinner de chargement (loader) pendant la récupération des données', () => {
        // On simule un chargement en cours sans données existantes
        mockUseMovies.mockReturnValue({
            movies: [],
            isLoading: true,
            error: null,
            getMovie: jest.fn().mockReturnValue(undefined),
            fetchMovieDetails: jest.fn().mockReturnValue(new Promise(() => {})) // Promesse bloquée
        });
        
        const { container } = renderMovieDetails();
        expect(container.querySelector('.animate-spin')).toBeInTheDocument();
    });

    it('affiche "Film non trouvé" et retourne à l\'accueil si le film n\'existe pas', async () => {
        // On rétablit le mock normal mais qui ne trouve rien
        mockUseMovies.mockReturnValue({
            movies: [],
            isLoading: false,
            error: null,
            getMovie: mockGetMovie,
            fetchMovieDetails: mockFetchMovieDetails
        });
        mockGetMovie.mockReturnValue(undefined);
        mockFetchMovieDetails.mockResolvedValue(undefined);
        
        renderMovieDetails();
        
        // On attend la fin du chargement local
        expect(await screen.findByText('Film non trouvé')).toBeInTheDocument();
        
        // Clic sur le bouton de retour à l'accueil
        const homeBtn = screen.getByRole('button', { name: /Retour à l'accueil/i });
        fireEvent.click(homeBtn);
        
        expect(mockNavigate).toHaveBeenCalledWith('/');
    });
});
