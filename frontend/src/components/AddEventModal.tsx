import { useState, useEffect, type FormEvent } from 'react';
import { X, Clock, Film } from 'lucide-react';
import Button from './ui/Button';
import { useMovies } from '../context/MovieContext';
import { format } from 'date-fns';

export interface NewEvent {
    id: string; // Ensure ID is present for display logic
    movieId: string;
    title: string;
    date: Date;
    color: string;
}

interface AddEventModalProps {
    isOpen: boolean;
    onClose: () => void;
    onAddEvent: (event: Omit<NewEvent, 'id'>) => void; // Allow caller to generate ID or handle omit
    preselectedDate?: Date | null;
    preselectedMovieId?: string;
}

const AddEventModal = ({ isOpen, onClose, onAddEvent, preselectedDate, preselectedMovieId }: AddEventModalProps) => {
    const { movies } = useMovies();
    const [selectedMovieId, setSelectedMovieId] = useState('');
    const [dateString, setDateString] = useState('');
    const [timeString, setTimeString] = useState('20:00');
    const [customTitle, setCustomTitle] = useState('');
    const [searchQuery, setSearchQuery] = useState('');
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);

    useEffect(() => {
        if (isOpen) {
            if (preselectedDate) {
                setDateString(format(preselectedDate, 'yyyy-MM-dd'));
            } else {
                setDateString(format(new Date(), 'yyyy-MM-dd'));
            }
            if (preselectedMovieId) {
                const movie = movies.find(m => m.id === preselectedMovieId);
                if (movie) {
                    setSelectedMovieId(movie.id);
                    setSearchQuery(movie.title);
                }
            }
        }
    }, [isOpen, preselectedDate, preselectedMovieId, movies]);

    if (!isOpen) return null;

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        
        const movie = movies.find(m => m.id === selectedMovieId);
        // Combine date and time
        const eventDate = new Date(`${dateString}T${timeString}`);
        
        const newEvent = {
            movieId: selectedMovieId,
            title: customTitle || (movie ? movie.title : 'Soirée Ciné'),
            date: eventDate,
            color: 'bg-purple-500' // Default color
        };

        onAddEvent(newEvent);
        
        // Reset form
        setSelectedMovieId('');
        setSearchQuery('');
        setCustomTitle('');
        setTimeString('20:00');
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white/80 dark:bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div className="bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-2xl w-full max-w-md p-6 shadow-2xl relative">
                <button 
                    onClick={onClose}
                    className="absolute top-4 right-4 text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors"
                >
                    <X size={20} />
                </button>

                <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Ajouter un événement</h2>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Movie Selection */}
                    <div className="relative z-20">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Film</label>
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="Rechercher un film..."
                                value={searchQuery}
                                onChange={(e) => {
                                    setSearchQuery(e.target.value);
                                    setIsDropdownOpen(true);
                                    if (selectedMovieId) setSelectedMovieId('');
                                    if (e.target.value) setCustomTitle('');
                                }}
                                onFocus={() => setIsDropdownOpen(true)}
                                onBlur={() => setTimeout(() => setIsDropdownOpen(false), 200)}
                                className="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-lg p-3 pr-10 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none placeholder-gray-500"
                            />
                            <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                                <Film size={18} />
                            </div>
                        </div>
                        
                        {isDropdownOpen && (
                            <div className="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-lg shadow-xl max-h-48 overflow-y-auto z-50">
                                {movies.filter(m => m.title.toLowerCase().includes(searchQuery.toLowerCase())).length > 0 ? (
                                    movies.filter(m => m.title.toLowerCase().includes(searchQuery.toLowerCase())).map(movie => (
                                        <button
                                            key={movie.id}
                                            type="button"
                                            className="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-purple-600/50 focus:bg-gray-100 dark:focus:bg-purple-600/50 outline-none transition-colors"
                                            onClick={() => {
                                                setSelectedMovieId(movie.id);
                                                setSearchQuery(movie.title);
                                                setCustomTitle('');
                                                setIsDropdownOpen(false);
                                            }}
                                        >
                                            {movie.title}
                                        </button>
                                    ))
                                ) : (
                                    <div className="px-4 py-3 text-sm text-gray-500">Aucun film trouvé</div>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Or Custom Title */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Ou titre personnalisé</label>
                        <input
                            type="text"
                            value={customTitle}
                            onChange={(e) => {
                                setCustomTitle(e.target.value);
                                if (e.target.value) setSelectedMovieId(''); // Clear selection if typing custom
                            }}
                            placeholder="Ex: Soirée popcorn"
                            className="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-lg p-3 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none placeholder-gray-500 dark:placeholder-gray-600"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {/* Date */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Date</label>
                            <div className="relative">
                                <input
                                    type="date"
                                    value={dateString}
                                    onChange={(e) => setDateString(e.target.value)}
                                    required
                                    className="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-lg p-3 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none dark:[color-scheme:dark]"
                                />
                            </div>
                        </div>

                        {/* Time */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Heure</label>
                            <div className="relative">
                                <input
                                    type="time"
                                    value={timeString}
                                    onChange={(e) => setTimeString(e.target.value)}
                                    required
                                    className="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-lg p-3 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none dark:[color-scheme:dark]"
                                />
                                <div className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">
                                    <Clock size={16} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="pt-4 flex gap-3">
                        <Button 
                            type="button" 
                            variant="secondary" 
                            onClick={onClose}
                            className="flex-1"
                        >
                            Annuler
                        </Button>
                        <Button 
                            type="submit" 
                            className="flex-1"
                            disabled={!selectedMovieId && !customTitle}
                        >
                            Ajouter
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default AddEventModal;
