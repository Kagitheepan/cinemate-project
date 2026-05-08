import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TextDecoder, TextEncoder } from 'util';
import { useMovies } from '../context/MovieContext';

jest.mock('../context/MovieContext');

Object.assign(global, { TextDecoder, TextEncoder });

const { MemoryRouter } = require('react-router-dom');
const Home = require('./Home').default;

const mockUseMovies = useMovies as jest.Mock;
const mockFetchMovieDetails = jest.fn();

const movies = [
    {
        id: 'matrix',
        title: 'Matrix',
        description: 'Un pirate informatique decouvre la matrice.',
        year: 1999,
        rating: 8.7,
        imageUrl: '/matrix.jpg',
        duration: 136,
        genres: ['Action', 'Science-fiction'],
        category: 'Film'
    },
    {
        id: 'amelie',
        title: 'Amelie Poulain',
        description: 'Une comedie romantique parisienne.',
        year: 2001,
        rating: 8.3,
        imageUrl: '/amelie.jpg',
        duration: 122,
        genres: ['Comedie', 'Romance'],
        category: 'Film'
    },
    {
        id: 'court',
        title: 'Court metrage du soir',
        description: 'Un film court pour une petite pause.',
        year: 2024,
        rating: 7.1,
        imageUrl: '/court.jpg',
        duration: 82,
        genres: ['Action'],
        category: 'Film'
    }
];

const renderHome = () => {
    return render(
        <MemoryRouter>
            <Home />
        </MemoryRouter>
    );
};

describe('Home - recherche et filtres', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        window.sessionStorage.clear();
        window.sessionStorage.setItem('hasSeenTimeModal', 'true');
        mockUseMovies.mockReturnValue({
            movies,
            isLoading: false,
            error: null,
            getMovie: jest.fn(),
            fetchMovieDetails: mockFetchMovieDetails
        });
    });

    it('filtre les films avec la recherche par titre', async () => {
        const user = userEvent.setup();
        renderHome();

        await user.type(screen.getByPlaceholderText('Rechercher un film...'), 'matrix');

        expect(screen.getByRole('heading', { name: '1 résultat' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Matrix' })).toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Amelie Poulain' })).not.toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Court metrage du soir' })).not.toBeInTheDocument();
    });

    it('filtre les films par genre', async () => {
        const user = userEvent.setup();
        renderHome();

        const [genreSelect] = screen.getAllByRole('combobox');
        await user.selectOptions(genreSelect, 'Action');

        expect(screen.getByRole('heading', { name: '2 résultats' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Matrix' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Court metrage du soir' })).toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Amelie Poulain' })).not.toBeInTheDocument();
    });

    it('filtre les films selon le temps disponible choisi dans les filtres', async () => {
        const user = userEvent.setup();
        renderHome();

        const [, durationSelect] = screen.getAllByRole('combobox');
        await user.selectOptions(durationSelect, '90');

        expect(screen.getByRole('heading', { name: '1 résultat' })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Court metrage du soir' })).toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Matrix' })).not.toBeInTheDocument();
        expect(screen.queryByRole('heading', { name: 'Amelie Poulain' })).not.toBeInTheDocument();
    });

    it('applique le temps disponible valide dans la modale de demarrage', async () => {
        jest.useFakeTimers();
        const user = userEvent.setup({ advanceTimers: jest.advanceTimersByTime });
        window.sessionStorage.removeItem('hasSeenTimeModal');

        renderHome();
        act(() => {
            jest.advanceTimersByTime(1000);
        });

        const modal = await screen.findByRole('heading', { name: 'Combien de temps avez-vous ?' });
        const dialog = modal.closest('div')?.parentElement as HTMLElement;
        const inputs = within(dialog).getAllByRole('spinbutton');

        await user.clear(inputs[0]);
        await user.type(inputs[0], '1');
        await user.clear(inputs[1]);
        await user.type(inputs[1], '30');
        await user.click(screen.getByRole('button', { name: 'Valider' }));

        await waitFor(() => {
            expect(screen.getByRole('heading', { name: '1 résultat' })).toBeInTheDocument();
        });
        expect(screen.getByRole('heading', { name: 'Court metrage du soir' })).toBeInTheDocument();
        expect(window.sessionStorage.getItem('cinemate_home_maxDuration')).toBe('90');

        jest.useRealTimers();
    });
});
