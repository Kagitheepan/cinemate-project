import { useState, useEffect } from 'react';
import MovieGrid from '../components/MovieGrid';
import Button from '../components/ui/Button';
import { useMovies } from '../context/MovieContext';

const MoviesPage = () => {
    const { movies, isLoading } = useMovies();
    
    // Derived state for available genres
    const allGenres = Array.from(new Set(movies.flatMap(m => m.genres || []))).sort();

    // Persisted states
    const [displayedCount, setDisplayedCount] = useState(() => {
        const saved = sessionStorage.getItem('cinemate_movies_displayedCount');
        return saved ? parseInt(saved) : 18;
    });
    
    const [searchQuery, setSearchQuery] = useState(() => sessionStorage.getItem('cinemate_movies_searchQuery') || '');
    const [selectedGenre, setSelectedGenre] = useState(() => sessionStorage.getItem('cinemate_movies_selectedGenre') || '');
    const [maxDuration, setMaxDuration] = useState<number | null>(() => {
        const saved = sessionStorage.getItem('cinemate_movies_maxDuration');
        return saved ? parseInt(saved) : null;
    });

    // Sync to sessionStorage
    useEffect(() => {
        sessionStorage.setItem('cinemate_movies_displayedCount', displayedCount.toString());
        sessionStorage.setItem('cinemate_movies_searchQuery', searchQuery);
        sessionStorage.setItem('cinemate_movies_selectedGenre', selectedGenre);
        if (maxDuration === null) sessionStorage.removeItem('cinemate_movies_maxDuration');
        else sessionStorage.setItem('cinemate_movies_maxDuration', maxDuration.toString());
    }, [displayedCount, searchQuery, selectedGenre, maxDuration]);

    // Reset pagination when filters change
    useEffect(() => {
        setDisplayedCount(18);
    }, [searchQuery, selectedGenre, maxDuration]);

    // Apply filters
    const filteredMovies = movies.filter(movie => {
        const query = searchQuery.toLowerCase();
        const matchesSearch = query ? (
            movie.title.toLowerCase().includes(query) ||
            (movie.director && movie.director.toLowerCase().includes(query)) ||
            (movie.castNames && movie.castNames.some(name => name.toLowerCase().includes(query)))
        ) : true;
        
        const matchesGenre = selectedGenre ? (movie.genres || []).includes(selectedGenre) : true;
        const matchesDuration = maxDuration ? (movie.duration || 999) <= maxDuration : true;
        return matchesSearch && matchesGenre && matchesDuration;
    });

    const displayedMovies = filteredMovies.slice(0, displayedCount);

    const handleLoadMore = () => {
        setDisplayedCount((prev) => prev + 18);
    };

    if (isLoading) {
        return (
             <div className="flex-grow pt-32 pb-12 min-h-screen flex items-center justify-center bg-gray-50 dark:bg-neutral-950 transition-colors duration-300">
                 <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 dark:border-purple-500"></div>
             </div>
        );
    }

    return (
        <div className="flex-grow pt-32 pb-12 bg-gray-50 dark:bg-neutral-950 transition-colors duration-300 min-h-screen">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 pb-4 border-b border-black/10 dark:border-white/5 gap-4">
                    <div>
                        <h2 className="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-500 dark:from-white dark:to-gray-400 bg-clip-text text-transparent">
                            Catalogue complet
                        </h2>
                        <p className="text-sm text-gray-700 dark:text-white mt-1">
                            {filteredMovies.length} film{filteredMovies.length > 1 ? 's' : ''} trouvé{filteredMovies.length > 1 ? 's' : ''}
                        </p>
                    </div>
                    
                    {/* Filters Bar */}
                    <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
                        <input 
                            type="text" 
                            placeholder="Titre, acteur, réalisateur..."
                            aria-label="Rechercher un film"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="bg-white dark:bg-white/5 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-gray-900 dark:text-white text-sm focus:outline-none focus:border-purple-500 w-full md:w-48"
                        />
                        
                        <select 
                            value={selectedGenre}
                            aria-label="Filtrer par genre"
                            onChange={(e) => setSelectedGenre(e.target.value)}
                            className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-gray-900 dark:text-white text-sm focus:outline-none focus:border-purple-500 flex-1 md:flex-none"
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
                            className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-gray-900 dark:text-white text-sm focus:outline-none focus:border-purple-500 flex-1 md:flex-none"
                        >
                            <option value="">Toutes durées</option>
                            <option value="90">- de 1h30</option>
                            <option value="120">- de 2h00</option>
                            <option value="150">- de 2h30</option>
                        </select>
                        
                        {(searchQuery || selectedGenre || maxDuration) && (
                            <button 
                                onClick={() => { setSearchQuery(''); setSelectedGenre(''); setMaxDuration(null); }}
                                className="text-xs text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-white px-2 py-2"
                                title="Réinitialiser les filtres"
                            >
                                ✕ Effacer
                            </button>
                        )}
                    </div>
                </div>
                
                {filteredMovies.length > 0 ? (
                    <>
                        <MovieGrid movies={displayedMovies} />
                        {displayedCount < filteredMovies.length && (
                            <div className="mt-12 flex justify-center">
                                <Button 
                                    variant="secondary" 
                                    size="lg" 
                                    onClick={handleLoadMore}
                                    className="text-purple-400 hover:bg-purple-500/10 border-purple-500/30 hover:border-purple-500/60"
                                >
                                    Voir plus de films
                                </Button>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="text-center py-20 bg-white dark:bg-white/5 rounded-2xl border border-gray-200 dark:border-white/5 shadow-sm dark:shadow-none">
                        <p className="text-xl text-gray-900 dark:text-white font-medium">Aucun film ne correspond à vos critères.</p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default MoviesPage;
