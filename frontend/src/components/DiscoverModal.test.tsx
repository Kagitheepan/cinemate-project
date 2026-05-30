import { render, screen, fireEvent, act } from '@testing-library/react';
import { TextDecoder, TextEncoder } from 'util';

Object.assign(global, { TextDecoder, TextEncoder });

import DiscoverModal from './DiscoverModal';
import { useMovies } from '../context/MovieContext';

jest.mock('../context/MovieContext');

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
    ...jest.requireActual('react-router-dom'),
    useNavigate: () => mockNavigate,
}));

describe('DiscoverModal', () => {
    const mockOnClose = jest.fn();
    
    const mockMovies = [
        { id: '1', title: 'Action 1', duration: 100, genres: ['Action'] },
        { id: '2', title: 'Action 2', duration: 150, genres: ['Action', 'Sci-Fi'] },
        { id: '1-2', title: 'Action 1 Clone', duration: 100, genres: ['Action'] }, // Finissant par -2
        { id: '3', title: 'Comedy 1', duration: 90, genres: ['Comedy'] },
    ];

    beforeEach(() => {
        jest.clearAllMocks();
        jest.useFakeTimers();
        (useMovies as jest.Mock).mockReturnValue({ movies: mockMovies });
    });

    afterEach(() => {
        jest.useRealTimers();
        jest.restoreAllMocks();
    });

    it('ferme la modale et réinitialise les états', () => {
        render(<DiscoverModal isOpen={true} onClose={mockOnClose} />);
        
        // Simuler quelques changements d'état
        const genreSelect = screen.getByLabelText('Filtrer par genre');
        fireEvent.change(genreSelect, { target: { value: 'Action' } });
        
        // Fermer la modale
        const closeBtn = screen.getByRole('button', { name: /Annuler/i });
        fireEvent.click(closeBtn);
        
        expect(mockOnClose).toHaveBeenCalled();
    });

    it('filtre les films avant de lancer la roue', () => {
        render(<DiscoverModal isOpen={true} onClose={mockOnClose} />);
        
        // Sélectionner genre Comedy et durée max 120
        fireEvent.change(screen.getByLabelText('Filtrer par genre'), { target: { value: 'Comedy' } });
        fireEvent.change(screen.getByLabelText('Filtrer par durée maximum'), { target: { value: '120' } });
        
        const discoverBtn = screen.getByRole('button', { name: /Découvrir/i });
        fireEvent.click(discoverBtn);
        
        // Avancer le temps pour finir l'animation (15 spins * 100ms)
        act(() => {
            jest.advanceTimersByTime(1600);
        });
        
        // Le seul film qui matche est Comedy 1
        expect(screen.getByText('Comedy 1')).toBeInTheDocument();
        expect(screen.queryByText('Action 1')).not.toBeInTheDocument();
    });

    it('présente des films uniques (ignore ceux finissant par -2)', () => {
        render(<DiscoverModal isOpen={true} onClose={mockOnClose} />);
        
        // Demander 5 films pour forcer tous les résultats possibles
        fireEvent.click(screen.getByRole('button', { name: '5' }));
        fireEvent.click(screen.getByRole('button', { name: /Découvrir/i }));
        
        act(() => {
            jest.advanceTimersByTime(1600);
        });
        
        expect(screen.getByText('Action 1')).toBeInTheDocument();
        expect(screen.getByText('Action 2')).toBeInTheDocument();
        expect(screen.getByText('Comedy 1')).toBeInTheDocument();
        
        // Action 1 Clone ne doit PAS être affiché
        expect(screen.queryByText('Action 1 Clone')).not.toBeInTheDocument();
    });

    it('affiche les films une fois l\'animation terminée et permet la navigation', () => {
        // Pour avoir un comportement déterministe
        jest.spyOn(Math, 'random').mockReturnValue(0.99); // Toujours le même élément
        
        render(<DiscoverModal isOpen={true} onClose={mockOnClose} />);
        
        fireEvent.click(screen.getByRole('button', { name: /Découvrir/i }));
        
        // Pendant l'animation
        expect(screen.getByText('Tirage en cours...')).toBeInTheDocument();
        
        act(() => {
            jest.advanceTimersByTime(1600);
        });
        
        // L'animation est finie
        expect(screen.getByText('Votre sélection')).toBeInTheDocument();
        
        // Cliquer sur le film affiché
        // Comme on a mocké random à 0.99, ça sortira le dernier film après le tri, on clique juste sur l'élément avec le cursor-pointer
        const titles = screen.getAllByRole('heading', { level: 3 });
        const card = titles[0].closest('div.group');
        if (card) fireEvent.click(card);
        
        expect(mockNavigate).toHaveBeenCalled();
        expect(mockOnClose).toHaveBeenCalled();
    });

    it('ne fait rien si non ouvert', () => {
        const { container } = render(<DiscoverModal isOpen={false} onClose={mockOnClose} />);
        expect(container).toBeEmptyDOMElement();
    });
});
