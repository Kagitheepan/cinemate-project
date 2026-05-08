import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import Watchlist from './Watchlist';
import { useAuth } from '../context/AuthContext';
import { useMovies } from '../context/MovieContext';
import api from '../services/api';

// Mock contexts and services
jest.mock('../context/AuthContext');
jest.mock('../context/MovieContext');
jest.mock('../services/api');

const mockApi = api as jest.Mocked<typeof api>;
const mockUseAuth = useAuth as jest.Mock;
const mockUseMovies = useMovies as jest.Mock;

// Mock child components that might interfere with simple unit testing
jest.mock('../components/MovieCard', () => {
    return function DummyMovieCard({ title, id }: { title: string; id: string }) {
        return <div data-testid={`movie-card-${id}`}>{title}</div>;
    };
});

describe('Watchlist - Ajout et Gestion', () => {
    const mockUpdateUser = jest.fn();

    const baseUser = {
        username: 'test',
        watchlist: { toWatch: ['1'], watched: ['2'] }
    };

    const mockMovies = [
        { id: '1', title: 'Inception', description: 'Dream', year: 2010, rating: 8.8, imageUrl: '' },
        { id: '2', title: 'Interstellar', description: 'Space', year: 2014, rating: 8.6, imageUrl: '' },
        { id: '3', title: 'The Dark Knight', description: 'Batman', year: 2008, rating: 9.0, imageUrl: '' }
    ];

    beforeEach(() => {
        jest.clearAllMocks();

        mockUseAuth.mockReturnValue({
            user: baseUser,
            updateUser: mockUpdateUser
        });

        mockUseMovies.mockReturnValue({
            movies: mockMovies,
            getMovie: (id: string) => mockMovies.find(m => m.id === id)
        });

        mockApi.put.mockResolvedValue({ data: { success: true } });
    });

    it('devrait afficher les films dans les bonnes catégories au chargement', async () => {
        render(<Watchlist />);

        // Inception devrait être dans "à voir" et Interstellar dans "vus"
        expect(await screen.findByTestId('movie-card-1')).toBeInTheDocument();
        expect(await screen.findByTestId('movie-card-2')).toBeInTheDocument();
        expect(screen.getByText('Inception')).toBeInTheDocument();
        expect(screen.getByText('Interstellar')).toBeInTheDocument();
    });

    it('devrait permettre d\'ajouter un film à la liste "à voir"', async () => {
        render(<Watchlist />);

        // Ouvrir la modale pour "Films à voir"
        const addButtons = screen.getAllByText('Ajouter un film');
        fireEvent.click(addButtons[0]); // Le premier est pour "à voir"

        // Rechercher et ajouter "The Dark Knight"
        const searchInput = screen.getByPlaceholderText('Rechercher un film...');
        fireEvent.change(searchInput, { target: { value: 'Dark' } });

        const addButton = await screen.findByText('Ajouter');
        fireEvent.click(addButton);

        // Vérifier que api.put a été appelé avec les nouvelles données
        await waitFor(() => {
            expect(mockApi.put).toHaveBeenCalledWith('/profile', {
                watchlist: {
                    toWatch: ['1', '3'], // Inception (1) + The Dark Knight (3)
                    watched: ['2']       // Interstellar (2)
                }
            });
        });

        // Vérifier la mise à jour locale
        expect(mockUpdateUser).toHaveBeenCalledWith({
            watchlist: {
                toWatch: ['1', '3'],
                watched: ['2']
            }
        });
    });

    it('devrait permettre de déplacer un film de "à voir" vers "vus"', async () => {
        render(<Watchlist />);

        // Cliquer sur le bouton "Passer aux vus" pour le film "Inception"
        const moveToWatchedBtn = await screen.findByText('Passer aux vus');
        fireEvent.click(moveToWatchedBtn);

        await waitFor(() => {
            expect(mockApi.put).toHaveBeenCalledWith('/profile', {
                watchlist: {
                    toWatch: [], // Inception est parti
                    watched: ['2', '1'] // Interstellar + Inception
                }
            });
        });
    });

    it('devrait permettre de retirer un film de la watchlist', async () => {
        render(<Watchlist />);

        // Le bouton "X" (supprimer) pour chaque film
        // On va cibler le premier bouton supprimer correspondant à Inception
        const deleteButtons = document.querySelectorAll('button .lucide-x');
        // deleteButtons[0] est dans le header "Films à voir" ? Non lucide-x est utilisé comme icône
        // Pour être plus précis, on peut chercher par le container du film
        // Le text Inception est dans le movie-card, le bouton est juste à côté
        const passToWatched = await screen.findByText('Passer aux vus');
        // le bouton X est le next sibling du parent ou similaire
        const removeButton = passToWatched.nextElementSibling as HTMLElement;
        
        fireEvent.click(removeButton);

        await waitFor(() => {
            expect(mockApi.put).toHaveBeenCalledWith('/profile', {
                watchlist: {
                    toWatch: [], // Inception supprimé
                    watched: ['2']
                }
            });
        });
    });
});
