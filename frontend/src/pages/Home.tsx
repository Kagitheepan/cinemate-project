import { useState, useEffect } from 'react';
import SearchBar from '../components/SearchBar';
import MovieGrid from '../components/MovieGrid';
import { Link } from 'react-router-dom';
import TimeModal from '../components/TimeModal';
import { useMovies } from '../context/MovieContext';

const Home = () => {
    const { movies, isLoading } = useMovies();
    const [isTimeModalOpen, setIsTimeModalOpen] = useState(false);
    const [maxDuration, setMaxDuration] = useState<number | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    useEffect(() => {
        // Check if user has already set a preference or visited recently
        const hasSeenModal = sessionStorage.getItem('hasSeenTimeModal');
        if (!hasSeenModal) {
            // Small delay for better UX
            const timer = setTimeout(() => {
                setIsTimeModalOpen(true);
            }, 1000);
            return () => clearTimeout(timer);
        }
    }, []);

    const handleTimeConfirm = (minutes: number) => {
        setMaxDuration(minutes);
        setIsTimeModalOpen(false);
        sessionStorage.setItem('hasSeenTimeModal', 'true');
    };

    const handleCloseModal = () => {
        setIsTimeModalOpen(false);
        sessionStorage.setItem('hasSeenTimeModal', 'true');
    };

    // Filter movies based on duration if set
    // Use unique movies (API movies are unique by ID usually, but mock might have duplicates if we kept that logic)
    const uniqueMovies = movies.filter(m => !m.id.endsWith('-2'));

    const filteredMovies = uniqueMovies.filter(movie => {
        const matchesDuration = maxDuration ? (movie.duration || 999) <= maxDuration : true;
        const matchesSearch = searchQuery 
            ? movie.title.toLowerCase().includes(searchQuery.toLowerCase()) 
            : true;
        return matchesDuration && matchesSearch;
    });

    // Show all matching if searching, otherwise show top 6
    const displayedMovies = searchQuery ? filteredMovies : filteredMovies.slice(0, 6);

    return (
        <div className="flex-grow pt-24 pb-12 transition-all duration-500 ease-in-out">
            <TimeModal 
                isOpen={isTimeModalOpen}
                onConfirm={handleTimeConfirm}
                onClose={handleCloseModal}
            />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                {/* Hero Section with Search */}
                <section className="text-center py-16 sm:py-24 relative overflow-hidden">
                    <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-4xl opacity-20 pointer-events-none">
                         <div className="absolute inset-0 bg-gradient-to-r from-purple-500/30 to-blue-500/30 blur-3xl rounded-full transform scale-150 animate-pulse"></div>
                    </div>

                    <h1 className="text-4xl sm:text-6xl font-black mb-6 tracking-tight bg-gradient-to-br from-white via-gray-200 to-gray-500 bg-clip-text text-transparent drop-shadow-sm">
                        Découvrez votre prochaine <br/> <span className="text-purple-400">obsession cinématographique</span>
                    </h1>
                    
                    <p className="text-lg text-gray-400 mb-10 max-w-2xl mx-auto font-light leading-relaxed">
                        Explorez des milliers de films et séries, créez votre watchlist et partagez vos favoris avec la communauté.
                    </p>

                    <div className="flex justify-center w-full relative z-10">
                        <SearchBar value={searchQuery} onChange={setSearchQuery} />
                    </div>
                </section>

                <section className="mt-12">
                   <div className="flex items-center justify-between mb-8 pb-4 border-b border-white/5">
                        <div className="flex items-center gap-4">
                            <h2 className="text-2xl font-bold bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
                                {searchQuery ? `Résultats pour "${searchQuery}"` : (maxDuration ? `Films de moins de ${Math.floor(maxDuration / 60)}h${maxDuration % 60 > 0 ? maxDuration % 60 : ''}` : 'Tendances du moment')}
                            </h2>
                            {(maxDuration || searchQuery) && (
                                <button 
                                    onClick={() => { setMaxDuration(null); setSearchQuery(''); }}
                                    className="text-xs text-purple-400 hover:text-white border border-purple-500/30 rounded-full px-3 py-1 transition-colors"
                                >
                                    Effacer les filtres
                                </button>
                            )}
                        </div>
                        <Link to="/movies" className="text-sm font-medium text-purple-400 hover:text-purple-300 transition-colors">
                            Voir tout &rarr;
                        </Link>
                   </div>
                   
                   {isLoading ? (
                       <div className="flex justify-center py-20">
                           <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                       </div>
                   ) : displayedMovies.length > 0 ? (
                       <MovieGrid movies={displayedMovies} />
                   ) : (
                       <div className="text-center py-20 bg-white/5 rounded-2xl border border-white/5">
                           <p className="text-xl text-gray-400 font-medium">Aucun film ne correspond à votre recherche.</p>
                           <button 
                                onClick={() => { setMaxDuration(null); setSearchQuery(''); }}
                                className="mt-4 text-purple-400 hover:text-white underline"
                           >
                               Voir tous les films
                           </button>
                       </div>
                   )}
                </section>
            </div>
        </div>
    );
};

export default Home;
