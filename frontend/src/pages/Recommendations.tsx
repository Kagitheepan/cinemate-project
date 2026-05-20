import { useState, useEffect } from 'react';
import { Sparkles } from 'lucide-react';
import MovieGrid from '../components/MovieGrid';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';
import { useMovies, type Movie as ContextMovie } from '../context/MovieContext';

// Extend context movie for the specific score field returned by the API
interface Movie extends ContextMovie {
    score?: number;
}

const Recommendations = () => {
    const { isAuthenticated } = useAuth();
    const { movies: allMovies } = useMovies(); // used to get all genres
    const [recommendedMovies, setRecommendedMovies] = useState<Movie[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedGenre, setSelectedGenre] = useState('');
    const [maxDuration, setMaxDuration] = useState<number | null>(null);

    const allGenres = Array.from(new Set(allMovies.flatMap(m => m.genres || []))).sort();

    useEffect(() => {
        const fetchRecommendations = async () => {
            if (!isAuthenticated) {
                setIsLoading(false);
                return;
            }
            try {
                const response = await api.get('/movies/recommendations/for-you');
                setRecommendedMovies(response.data);
            } catch (error) {
                console.error("Erreur lors de la récupération des recommandations", error);
            } finally {
                setIsLoading(false);
            }
        };

        fetchRecommendations();
    }, [isAuthenticated]);

    if (!isAuthenticated) {
        return (
            <div className="flex-grow pt-24 pb-12 flex flex-col items-center justify-center min-h-screen text-center">
                <Sparkles size={48} className="text-purple-500 mb-4" />
                <h1 className="text-3xl font-bold mb-2">Nos Recommandations</h1>
                <p className="text-gray-400 max-w-md">Connectez-vous et remplissez votre profil pour obtenir des suggestions 100% personnalisées.</p>
            </div>
        );
    }

    const filteredMovies = recommendedMovies.filter(movie => {
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

    return (
        <div className="flex-grow pt-24 pb-12 transition-all duration-500 ease-in-out">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                {/* Header */}
                <section className="text-center py-12 relative overflow-hidden">
                    <h1 className="text-4xl sm:text-5xl font-black mb-4 tracking-tight bg-gradient-to-br from-purple-400 via-pink-500 to-red-500 bg-clip-text text-transparent drop-shadow-sm flex items-center justify-center gap-3">
                        <Sparkles className="text-purple-500" size={36} />
                        Recommandé Pour Vous
                    </h1>
                    <p className="text-lg text-gray-400 max-w-2xl mx-auto font-light leading-relaxed mb-8">
                        Basé sur votre historique et vos plateformes favorites, voici notre sélection sur-mesure.
                    </p>

                    {/* Filtres */}
                    <div className="flex flex-wrap justify-center items-center gap-3 w-full relative z-10 max-w-3xl mx-auto">
                        <input 
                            type="text" 
                            placeholder="Filtrer (Titre, acteur...)"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500 w-full md:w-64 shadow-xl"
                        />
                        
                        <select 
                            value={selectedGenre}
                            onChange={(e) => setSelectedGenre(e.target.value)}
                            className="bg-gray-900 border border-white/10 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500 flex-1 md:flex-none shadow-xl"
                        >
                            <option value="">Tous les genres</option>
                            {allGenres.map(genre => (
                                <option key={genre} value={genre}>{genre}</option>
                            ))}
                        </select>

                        <select 
                            value={maxDuration || ''}
                            onChange={(e) => setMaxDuration(e.target.value ? parseInt(e.target.value) : null)}
                            className="bg-gray-900 border border-white/10 rounded-lg px-4 py-3 text-white text-sm focus:outline-none focus:border-purple-500 flex-1 md:flex-none shadow-xl"
                        >
                            <option value="">Toutes durées</option>
                            <option value="90">- de 1h30</option>
                            <option value="120">- de 2h00</option>
                            <option value="150">- de 2h30</option>
                        </select>
                    </div>
                </section>

                <section className="mt-8">
                   {isLoading ? (
                       <div className="flex justify-center py-20">
                           <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
                       </div>
                   ) : filteredMovies.length > 0 ? (
                       <MovieGrid movies={filteredMovies} />
                   ) : (
                       <div className="text-center py-20 bg-white/5 rounded-2xl border border-white/5">
                           <p className="text-xl text-gray-400 font-medium">Aucun film de la sélection ne correspond à vos filtres actuels.</p>
                           <button 
                                onClick={() => { setMaxDuration(null); setSearchQuery(''); setSelectedGenre(''); }}
                                className="mt-4 text-purple-400 hover:text-white underline"
                           >
                               Effacer les filtres
                           </button>
                       </div>
                   )}
                </section>
            </div>
        </div>
    );
};

export default Recommendations;
