import { useState, useMemo, useRef } from 'react';
import { X, Shuffle, Film, Clock, Calendar } from 'lucide-react';
import Button from './ui/Button';
import { useMovies, type Movie } from '../context/MovieContext';
import { useNavigate } from 'react-router-dom';

interface DiscoverModalProps {
    isOpen: boolean;
    onClose: () => void;
}

const DiscoverModal = ({ isOpen, onClose }: DiscoverModalProps) => {
    const { movies } = useMovies();
    const navigate = useNavigate();

    const [step, setStep] = useState<1 | 2>(1);
    const [isSpinning, setIsSpinning] = useState(false);
    const [selectedGenre, setSelectedGenre] = useState('');
    const [maxDuration, setMaxDuration] = useState<number | null>(null);
    const [count, setCount] = useState<number>(1);
    const [displayedMovies, setDisplayedMovies] = useState<Movie[]>([]);
    const spinIntervalRef = useRef<any>(null);

    const allGenres = useMemo(() => {
        return Array.from(new Set(movies.flatMap(m => m.genres || []))).sort();
    }, [movies]);

    // Close and reset
    const handleClose = () => {
        if (spinIntervalRef.current) clearInterval(spinIntervalRef.current);
        setStep(1);
        setIsSpinning(false);
        setSelectedGenre('');
        setMaxDuration(null);
        setCount(1);
        setDisplayedMovies([]);
        onClose();
    };

    const handleDiscover = () => {
        // Filter unique movies
        const uniqueMovies = movies.filter(m => !m.id.endsWith('-2'));

        const filtered = uniqueMovies.filter(movie => {
            const matchesDuration = maxDuration ? (movie.duration || 999) <= maxDuration : true;
            const matchesGenre = selectedGenre ? (movie.genres || []).includes(selectedGenre) : true;
            return matchesDuration && matchesGenre;
        });

        // Shuffle
        const shuffled = [...filtered].sort(() => 0.5 - Math.random());
        const selected = shuffled.slice(0, count);

        setStep(2);
        setIsSpinning(true);
        
        let spins = 0;
        const maxSpins = 15; // 1.5 seconds at 100ms
        
        spinIntervalRef.current = setInterval(() => {
            spins++;
            if (spins >= maxSpins) {
                if (spinIntervalRef.current) clearInterval(spinIntervalRef.current);
                setIsSpinning(false);
                setDisplayedMovies(selected);
            } else {
                // Show random busy movies while spinning
                const randomSpins = [...uniqueMovies].sort(() => 0.5 - Math.random()).slice(0, count);
                setDisplayedMovies(randomSpins);
            }
        }, 100);
    };

    const navigateToMovie = (id: string) => {
        if (isSpinning) return;
        handleClose();
        navigate(`/movie/${id}`);
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div 
                className="absolute inset-0 bg-black/80 backdrop-blur-sm"
                onClick={handleClose}
            ></div>
            
            <div className="relative bg-neutral-900 border border-white/10 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                {/* Header */}
                <div className="p-6 border-b border-white/5 flex justify-between items-center">
                    <h2 className="text-xl font-bold text-white flex items-center">
                        <Shuffle className={`w-5 h-5 mr-2 text-purple-400 ${isSpinning ? 'animate-spin' : ''}`} />
                        {step === 1 ? 'Découvrir un film' : isSpinning ? 'Tirage en cours...' : 'Votre sélection'}
                    </h2>
                    <button 
                        onClick={handleClose}
                        className="text-gray-400 hover:text-white transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Body */}
                <div className="p-6 overflow-y-auto">
                    {step === 1 ? (
                        <div className="space-y-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">Genre</label>
                                <select 
                                    value={selectedGenre}
                                    onChange={(e) => setSelectedGenre(e.target.value)}
                                    className="w-full bg-black/50 border border-white/10 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500"
                                >
                                    <option value="">Tous les genres</option>
                                    {allGenres.map(genre => (
                                        <option key={genre} value={genre}>{genre}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">Durée maximum</label>
                                <select 
                                    value={maxDuration || ''}
                                    onChange={(e) => setMaxDuration(e.target.value ? parseInt(e.target.value) : null)}
                                    className="w-full bg-black/50 border border-white/10 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500"
                                >
                                    <option value="">Toutes durées</option>
                                    <option value="90">- de 1h30</option>
                                    <option value="120">- de 2h00</option>
                                    <option value="150">- de 2h30</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-300 mb-2">Nombre de propositions</label>
                                <div className="flex gap-4">
                                    {[1, 5, 10].map(num => (
                                        <button
                                            key={num}
                                            onClick={() => setCount(num)}
                                            className={`flex-1 py-3 rounded-lg border text-sm font-medium transition-all ${
                                                count === num 
                                                    ? 'bg-purple-600 border-purple-500 text-white' 
                                                    : 'bg-white/5 border-white/10 text-gray-400 hover:bg-white/10 hover:text-white'
                                            }`}
                                        >
                                            {num}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {displayedMovies.length > 0 ? (
                                displayedMovies.map((movie, index) => (
                                    <div 
                                        key={isSpinning ? `spin-${index}-${movie.id}` : movie.id} 
                                        className={`bg-black/40 border border-white/5 rounded-xl p-4 flex gap-4 transition-all duration-75 ${
                                            isSpinning 
                                                ? 'blur-[2px] opacity-70 scale-[0.98] border-purple-500/20' 
                                                : 'hover:border-purple-500/50 cursor-pointer group'
                                        }`}
                                        onClick={() => navigateToMovie(movie.id)}
                                    >
                                        {/* Thumbnail */}
                                        <div className="w-16 h-24 rounded-lg bg-neutral-800 flex-shrink-0 overflow-hidden">
                                            {movie.imageUrl ? (
                                                <img src={movie.imageUrl} alt={movie.title} className="w-full h-full object-cover" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-gray-600">
                                                    <Film size={24} />
                                                </div>
                                            )}
                                        </div>
                                        
                                        {/* Info */}
                                        <div className="flex flex-col justify-center flex-grow">
                                            <h3 className="font-bold text-white group-hover:text-purple-400 transition-colors line-clamp-1">
                                                {movie.title}
                                            </h3>
                                            <div className="flex items-center text-xs text-gray-400 mt-2 space-x-3">
                                                <span className="flex items-center">
                                                    <Calendar className="w-3 h-3 mr-1" />
                                                    {movie.year}
                                                </span>
                                                <span className="flex items-center">
                                                    <Clock className="w-3 h-3 mr-1" />
                                                    {movie.duration ? `${Math.floor(movie.duration / 60)}h ${movie.duration % 60}m` : 'N/A'}
                                                </span>
                                            </div>
                                            <p className="text-xs text-gray-500 mt-2 line-clamp-2">
                                                {movie.description}
                                            </p>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="text-center py-10">
                                    <p className="text-gray-400">Aucun film ne correspond à vos critères.</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="p-6 border-t border-white/5 flex gap-4">
                    {isSpinning ? null : step === 1 ? (
                        <>
                            <Button variant="ghost" fullWidth onClick={handleClose}>
                                Annuler
                            </Button>
                            <Button fullWidth onClick={handleDiscover}>
                                Découvrir
                            </Button>
                        </>
                    ) : (
                        <>
                            <Button variant="ghost" fullWidth onClick={() => setStep(1)}>
                                Refaire une recherche
                            </Button>
                            <Button fullWidth onClick={handleClose}>
                                Fermer
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default DiscoverModal;
