import { useState, useEffect } from 'react';
import { Sparkles } from 'lucide-react';
import MovieGrid from '../components/MovieGrid';
import { Link, useNavigate } from 'react-router-dom';
import TimeModal from '../components/TimeModal';
import DiscoverModal from '../components/DiscoverModal';
import Button from '../components/ui/Button';
import { useMovies } from '../context/MovieContext';

const Home = () => {
    const { movies, isLoading } = useMovies();
    const navigate = useNavigate();
    const [isTimeModalOpen, setIsTimeModalOpen] = useState(false);
    const [isDiscoverModalOpen, setIsDiscoverModalOpen] = useState(false);
    const [maxDuration, setMaxDuration] = useState<number | null>(() => {
        const saved = sessionStorage.getItem('cinemate_home_maxDuration');
        return saved ? parseInt(saved) : null;
    });
    const [searchQuery, setSearchQuery] = useState(() => {
        return sessionStorage.getItem('cinemate_home_searchQuery') || '';
    });
    const [selectedGenre, setSelectedGenre] = useState(() => {
        return sessionStorage.getItem('cinemate_home_selectedGenre') || '';
    });

    // Derived state for available genres
    const allGenres = Array.from(new Set(movies.flatMap(m => m.genres || []))).sort();

    // Save filters to sessionStorage whenever they change
    useEffect(() => {
        if (maxDuration === null) {
            sessionStorage.removeItem('cinemate_home_maxDuration');
        } else {
            sessionStorage.setItem('cinemate_home_maxDuration', maxDuration.toString());
        }
    }, [maxDuration]);

    useEffect(() => {
        sessionStorage.setItem('cinemate_home_searchQuery', searchQuery);
    }, [searchQuery]);

    useEffect(() => {
        sessionStorage.setItem('cinemate_home_selectedGenre', selectedGenre);
    }, [selectedGenre]);

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
        const query = searchQuery.toLowerCase();
        const matchesSearch = query ? (
            movie.title.toLowerCase().includes(query) ||
            (movie.director && movie.director.toLowerCase().includes(query)) ||
            (movie.castNames && movie.castNames.some(name => name.toLowerCase().includes(query)))
        ) : true;
        const matchesGenre = selectedGenre ? (movie.genres || []).includes(selectedGenre) : true;
        return matchesDuration && matchesSearch && matchesGenre;
    });

    // Show all matching if searching or filtering, otherwise show top 6
    const isFiltering = searchQuery || selectedGenre || maxDuration;
    const displayedMovies = isFiltering ? filteredMovies : filteredMovies.slice(0, 6);

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
                    
                    <p className="text-lg text-white mb-10 max-w-2xl mx-auto font-light leading-relaxed">
                        Explorez des milliers de films et séries, créez votre watchlist et partagez vos favoris avec la communauté.
                    </p>

                    <div className="flex flex-wrap justify-center items-center gap-3 w-full relative z-10 max-w-3xl mx-auto">
                        <input 
                            type="text" 
                            placeholder="Titre, acteur, réalisateur..."
                            aria-label="Rechercher un film"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="bg-white/5 border border-gray-600 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500 w-full md:w-64 shadow-xl"
                        />
                        
                        <select 
                            value={selectedGenre}
                            aria-label="Filtrer par genre"
                            onChange={(e) => setSelectedGenre(e.target.value)}
                            className="bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500 flex-1 md:flex-none shadow-xl"
                        >
                            <option value="">Tous les genres</option>
                            {allGenres.map(genre => (
                                <option key={genre} value={genre}>{genre}</option>
                            ))}
                        </select>

                        <select 
                            value={maxDuration || ''}
                            aria-label="Filtrer par durée"
                            onChange={(e) => setMaxDuration(e.target.value ? parseInt(e.target.value) : null)}
                            className="bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500 flex-1 md:flex-none shadow-xl"
                        >
                            <option value="">Toutes durées</option>
                            <option value="90">- de 1h30</option>
                            <option value="120">- de 2h00</option>
                            <option value="150">- de 2h30</option>
                        </select>
                    </div>

                    <div className="mt-8 flex justify-center">
                        <Button 
                            variant="primary" 
                            onClick={() => setIsDiscoverModalOpen(true)}
                            className="shadow-purple-500/25 group"
                        >
                            <Sparkles className="w-5 h-5 mr-2 group-hover:animate-pulse" />
                            Découvrir un film
                        </Button>
                    </div>
                </section>

                <DiscoverModal 
                    isOpen={isDiscoverModalOpen}
                    onClose={() => setIsDiscoverModalOpen(false)}
                />

                <section className="mt-12">
                   <div className="flex items-center justify-between mb-8 pb-4 border-b border-white/5">
                        <div className="flex items-center gap-4">
                            <h2 className="text-2xl font-bold bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
                                {isFiltering ? `${filteredMovies.length} résultat${filteredMovies.length > 1 ? 's' : ''}` : 'Tendances du moment'}
                            </h2>
                            {isFiltering && (
                                <button 
                                    onClick={() => { setMaxDuration(null); setSearchQuery(''); setSelectedGenre(''); }}
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
                       <>
                           <MovieGrid movies={displayedMovies} />
                           {!isFiltering && (
                               <div className="mt-12 flex justify-center">
                                   <Button 
                                       variant="secondary" 
                                       size="lg" 
                                       onClick={() => navigate('/movies')}
                                       className="text-purple-400 hover:bg-purple-500/10 border-purple-500/30 hover:border-purple-500/60"
                                   >
                                       Voir tout le catalogue
                                   </Button>
                               </div>
                           )}
                       </>
                   ) : (
                       <div className="text-center py-20 bg-white/5 rounded-2xl border border-white/5">
                           <p className="text-xl text-white font-medium">Aucun film ne correspond à votre recherche.</p>
                           <button 
                                onClick={() => { setMaxDuration(null); setSearchQuery(''); setSelectedGenre(''); }}
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
