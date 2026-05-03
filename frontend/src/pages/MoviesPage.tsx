import { useState } from 'react';
import MovieGrid from '../components/MovieGrid';
import Button from '../components/ui/Button';
import { useMovies } from '../context/MovieContext';

const MoviesPage = () => {
    const { movies, isLoading } = useMovies();
    const [displayedCount, setDisplayedCount] = useState(9);
    const displayedMovies = movies.slice(0, displayedCount);

    const handleLoadMore = () => {
        setDisplayedCount((prev) => prev + 9);
    };

    if (isLoading) {
        return (
             <div className="flex-grow pt-32 pb-12 min-h-screen flex items-center justify-center">
                 <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
             </div>
        );
    }

    return (
        <div className="flex-grow pt-32 pb-12 transition-all duration-500 ease-in-out min-h-screen">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between mb-8 pb-4 border-b border-white/5">
                    <h2 className="text-3xl font-bold bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
                        Catalogue complet
                    </h2>
                    <span className="text-sm text-gray-500">
                        {displayedMovies.length} sur {movies.length} films affichés
                    </span>
                </div>
                
                <MovieGrid movies={displayedMovies} />

                {displayedCount < movies.length && (
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
            </div>
        </div>
    );
};

export default MoviesPage;
