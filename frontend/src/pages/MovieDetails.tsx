import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Play, Plus, Share2, Star, Calendar, Clock, ChevronLeft, ChevronRight, User, X } from 'lucide-react';
import Button from '../components/ui/Button';
import MovieCard from '../components/MovieCard';
import { useMovies, type Movie } from '../context/MovieContext';
import { useAuth } from '../context/AuthContext';
import api from '../services/api';
import AddEventModal, { type NewEvent } from '../components/AddEventModal';

const getPlatformStyle = (platform: string) => {
    const p = platform.toLowerCase();
    if (p.includes('cinéma')) return 'bg-gradient-to-r from-yellow-500 to-amber-600 text-white border-yellow-500';
    if (p.includes('vod')) return 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white border-emerald-500';
    if (p.includes('gratuit')) return 'bg-gradient-to-r from-green-500 to-lime-500 text-white border-green-400';
    if (p.includes('netflix')) return 'bg-[#E50914] text-white border-[#E50914]';
    if (p.includes('prime') || p.includes('amazon')) return 'bg-[#00A8E1] text-white border-[#00A8E1]';
    if (p.includes('disney')) return 'bg-[#113CCF] text-white border-[#113CCF]';
    if (p.includes('canal')) return 'bg-black text-white border-white/20';
    if (p.includes('apple')) return 'bg-white text-black border-white';
    if (p.includes('hbo') || p.includes('max')) return 'bg-[#002BE7] text-white border-[#002BE7]';
    if (p.includes('paramount')) return 'bg-[#0064FF] text-white border-[#0064FF]';
    if (p.includes('crunchyroll')) return 'bg-[#F47521] text-white border-[#F47521]';
    return 'bg-neutral-800 text-gray-300 border-white/10';
};

const MovieDetails = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { fetchMovieDetails, getMovie, movies, isLoading: isContextLoading } = useMovies();
    const { user, updateUser } = useAuth();
    const [movie, setMovie] = useState<Movie | undefined>(undefined);
    const [isLocalLoading, setIsLocalLoading] = useState(false);
    const [isEventModalOpen, setIsEventModalOpen] = useState(false);
    const [isVideoModalOpen, setIsVideoModalOpen] = useState(false);
    const [recPage, setRecPage] = useState(0);
    
    useEffect(() => {
        const loadMovieDetails = async () => {
            if (id) {
                // 1. Try to get partial data immediately from context
                const existing = getMovie(id);
                if (existing) {
                    setMovie(existing);
                    // If we already have description, no need to show local loader
                    if (!existing.description) {
                        setIsLocalLoading(true);
                    }
                } else {
                    // We don't even have partial data, show loader
                    setIsLocalLoading(true);
                }

                // 2. Fetch full details in background
                const fullMovie = await fetchMovieDetails(id);
                if (fullMovie) {
                    setMovie(fullMovie);
                }
                setIsLocalLoading(false);
            }
        };
        
        loadMovieDetails();
    }, [id, fetchMovieDetails, getMovie]);

    // Scroll to top and reset recommendations page when ID changes
    useEffect(() => {
        window.scrollTo(0, 0);
        setRecPage(0);
    }, [id]);

    // Only show full screen loader if we have NO data at all
    if (isContextLoading && !movie) {
        return (
             <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-neutral-950 text-gray-900 dark:text-white transition-colors duration-300">
                 <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 dark:border-purple-500"></div>
             </div>
        );
    }

    if (!movie && !isLocalLoading) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-neutral-950 text-gray-900 dark:text-white transition-colors duration-300">
                <h2 className="text-2xl font-bold mb-4">Film non trouvé</h2>
                <Button onClick={() => navigate('/')}>Retour à l'accueil</Button>
            </div>
        );
    }

    // Fallback for missing movie during initial load
    if (!movie) return null;

    if (!movie) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-neutral-950 text-gray-900 dark:text-white transition-colors duration-300">
                <h2 className="text-2xl font-bold mb-4">Film non trouvé</h2>
                <Button onClick={() => navigate('/')}>Retour à l'accueil</Button>
            </div>
        );
    }

    // Filter recommendations (same category, excluding current movie)
    // Use movies from context
    const allRecommendations = movies.filter(m => m.category === movie.category && m.id !== movie.id);
    const recItemsPerPage = 3;
    const totalRecPages = Math.ceil(allRecommendations.length / recItemsPerPage);
    const recommendations = allRecommendations.slice(recPage * recItemsPerPage, (recPage + 1) * recItemsPerPage);

    const watchlistData = user?.watchlist as unknown as { toWatch?: string[], watched?: string[] } | null;
    const isObjectWatchlist = user?.watchlist && !Array.isArray(user.watchlist);
    
    let isInWatchlist = false;
    if (isObjectWatchlist && watchlistData) {
        isInWatchlist = !!(watchlistData.toWatch?.includes(movie.id) || watchlistData.watched?.includes(movie.id));
    } else if (Array.isArray(user?.watchlist)) {
        isInWatchlist = user.watchlist.includes(movie.id);
    }

    const handleToggleWatchlist = async () => {
        if (!user) {
            alert("Veuillez vous connecter pour gérer votre Watchlist.");
            return;
        }

        let newWatchlist: any;
        if (isObjectWatchlist && watchlistData) {
            if (isInWatchlist) {
                newWatchlist = {
                    toWatch: (watchlistData.toWatch || []).filter(id => id !== movie.id),
                    watched: (watchlistData.watched || []).filter(id => id !== movie.id)
                };
            } else {
                newWatchlist = {
                    toWatch: [...(watchlistData.toWatch || []), movie.id],
                    watched: watchlistData.watched || []
                };
            }
        } else {
            const currentArray = Array.isArray(user.watchlist) ? user.watchlist : [];
            newWatchlist = isInWatchlist 
                ? currentArray.filter(id => id !== movie.id)
                : [...currentArray, movie.id];
        }
            
        updateUser({ watchlist: newWatchlist });
        try {
            await api.put('/profile', { watchlist: newWatchlist });
        } catch (e) {
            console.error("Failed to update watchlist", e);
        }
    };

    const handleAddEvent = async (newEvent: Omit<NewEvent, 'id'>) => {
        if (!user) return;
        
        const eventWithId = {
            ...newEvent,
            id: Math.random().toString(36).substr(2, 9)
        };
        
        const agendaItem = {
            id: eventWithId.id,
            movieId: eventWithId.movieId,
            title: eventWithId.title,
            start: eventWithId.date.toISOString(),
            end: new Date(eventWithId.date.getTime() + 2 * 60 * 60 * 1000).toISOString()
        };
        
        const updatedAgenda = [...(user.agenda || []), agendaItem];
        updateUser({ agenda: updatedAgenda });
        
        try {
            await api.put('/profile', { agenda: updatedAgenda });
            alert("Événement ajouté à l'agenda avec succès !");
        } catch (error) {
            console.error("Failed to save agenda", error);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-20 pb-12 transition-colors duration-300">
            {/* Back Button */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <button 
                    onClick={() => navigate(-1)} 
                    className="flex items-center text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors"
                >
                    <ArrowLeft className="w-5 h-5 mr-2" />
                    Retour
                </button>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    {/* Left Column: Poster */}
                    <div className="lg:col-span-1">
                        <div className="aspect-[2/3] w-full rounded-2xl overflow-hidden shadow-2xl shadow-purple-900/20 bg-gray-200 dark:bg-neutral-900 relative group">
                             {movie.imageUrl ? (
                                <img src={movie.imageUrl} alt={movie.title} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center bg-gray-300 dark:bg-neutral-800">
                                    <span className="text-black/20 dark:text-white/20 text-6xl font-black uppercase tracking-tighter mix-blend-overlay">Poster</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column: Details */}
                    <div className="lg:col-span-2 space-y-8">
                        <div>
                            <div className="flex flex-wrap items-center gap-2 text-sm text-purple-400 font-medium tracking-wider uppercase mb-3">
                                {movie.genres && movie.genres.length > 0 ? (
                                    movie.genres.map(genre => (
                                        <span key={genre} className="px-3 py-1 bg-purple-500/10 rounded-full border border-purple-500/20">
                                            {genre}
                                        </span>
                                    ))
                                ) : (
                                    <span className="px-3 py-1 bg-purple-500/10 rounded-full border border-purple-500/20">
                                        {movie.category || 'Non classé'}
                                    </span>
                                )}
                            </div>
                            
                            <h1 className="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-4 leading-tight">
                                {movie.title}
                            </h1>

                            <div className="flex items-center space-x-8 text-gray-700 dark:text-gray-300 mb-6">
                                <div className="flex flex-col">
                                    <div className="flex items-center">
                                        <Star className="w-5 h-5 text-yellow-500 fill-current mr-2" />
                                        <span className="font-bold text-gray-900 dark:text-white">{movie.rating}</span>/10
                                    </div>
                                    <span className="text-xs text-gray-500 uppercase mt-1 tracking-wider font-semibold">Note IMDB</span>
                                </div>
                                <div className="flex flex-col">
                                    <div className="flex items-center">
                                        <Calendar className="w-5 h-5 mr-2 text-gray-500" />
                                        <span>{movie.year}</span>
                                    </div>
                                    <span className="text-xs text-gray-500 uppercase mt-1 tracking-wider font-semibold">Année</span>
                                </div>
                                <div className="flex flex-col">
                                    <div className="flex items-center">
                                        <Clock className="w-5 h-5 mr-2 text-gray-500" />
                                        <span>
                                            {movie.duration && movie.duration > 0 
                                                ? `${Math.floor(movie.duration / 60)}h ${movie.duration % 60}m` 
                                                : "N/A"}
                                        </span>
                                    </div>
                                    <span className="text-xs text-gray-500 uppercase mt-1 tracking-wider font-semibold">Durée</span>
                                </div>
                            </div>

                            <p className="text-lg text-gray-700 dark:text-gray-400 leading-relaxed max-w-2xl">
                                {movie.description}
                            </p>

                            {/* Streaming Platforms (JustWatch) */}
                            {movie.availableOn && movie.availableOn.length > 0 && (
                                <div className="pt-4">
                                    <h3 className="text-sm text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3">Disponible en streaming sur</h3>
                                    <div className="flex flex-wrap gap-2">
                                        {movie.availableOn.map((platform, idx) => {
                                            const pStyle = getPlatformStyle(platform);
                                            return (
                                                <div key={idx} className={`px-3 py-1.5 rounded-lg border font-medium text-sm ${pStyle} opacity-90 flex items-center`}>
                                                    {platform}
                                                </div>
                                            );
                                        })}
                                    </div>
                                    <p className="text-xs text-gray-500 dark:text-gray-600 mt-3 italic">Données fournies par JustWatch</p>
                                </div>
                            )}
                        </div>

                        {/* Actions */}
                        <div className="flex flex-wrap gap-4">
                            {movie.trailerKey ? (
                                <Button 
                                    size="lg" 
                                    className="px-8 shadow-purple-500/25"
                                    onClick={() => setIsVideoModalOpen(true)}
                                >
                                    <Play className="w-5 h-5 mr-2 fill-current" />
                                    Bande Annonce
                                </Button>
                            ) : (
                                <Button size="lg" className="px-8 shadow-purple-500/25 opacity-50 cursor-not-allowed">
                                    <Play className="w-5 h-5 mr-2 fill-current opacity-50" />
                                    Bande Annonce (Indisponible)
                                </Button>
                            )}
                            <Button 
                                variant={isInWatchlist ? "primary" : "secondary"} 
                                size="lg"
                                onClick={handleToggleWatchlist}
                                className={isInWatchlist ? "bg-purple-600 hover:bg-purple-700 text-white" : ""}
                            >
                                <Plus className={`w-5 h-5 mr-2 transition-transform ${isInWatchlist ? "rotate-45" : ""}`} />
                                {isInWatchlist ? "Dans la Watchlist" : "Watchlist"}
                            </Button>
                            <Button 
                                variant="secondary" 
                                size="lg"
                                onClick={() => {
                                    if (!user) {
                                        alert("Veuillez vous connecter pour planifier un film.");
                                        return;
                                    }
                                    setIsEventModalOpen(true);
                                }}
                            >
                                <Calendar className="w-5 h-5 mr-2" />
                                Planifier
                            </Button>
                            <Button variant="ghost" size="icon" className="rounded-full border border-black/10 dark:border-white/10 text-gray-700 dark:text-white">
                                <Share2 className="w-5 h-5" />
                            </Button>
                        </div>

                        {/* Cast */}
                        <div className="border-t border-black/10 dark:border-white/5 pt-8">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-6">Casting & Équipe</h3>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-6">
                                {movie.cast && movie.cast.length > 0 ? (
                                    movie.cast.map((actor, idx) => (
                                        <div key={idx} className="flex flex-col items-center text-center space-y-3">
                                            <div className="w-20 h-20 rounded-full bg-gray-200 dark:bg-neutral-800 overflow-hidden">
                                                {actor.imageUrl ? (
                                                    <img src={actor.imageUrl} alt={actor.name} className="w-full h-full object-cover" />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-500">
                                                        <User size={32} />
                                                    </div>
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white">{actor.name}</p>
                                                <p className="text-xs text-gray-500">{actor.role}</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-gray-500 col-span-2">Casting non disponible</p>
                                )}
                                
                                {movie.director && (
                                    <div className="flex flex-col items-center text-center space-y-3">
                                         <div className="w-20 h-20 rounded-full bg-gray-200 dark:bg-neutral-800 flex items-center justify-center text-gray-500 border border-black/5 dark:border-white/5">
                                             <span className="text-xs font-bold">DIR</span>
                                         </div>
                                         <div>
                                            <p className="font-medium text-gray-900 dark:text-white">{movie.director}</p>
                                            <p className="text-xs text-gray-500">Réalisateur</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recommendations */}
                <div className="mt-24 border-t border-black/10 dark:border-white/5 pt-12">
                     <div className="flex items-center justify-between mb-8">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Autres films similaires</h2>
                        <div className="flex space-x-2">
                            <button 
                                onClick={() => setRecPage(p => Math.max(0, p - 1))}
                                disabled={recPage === 0}
                                className="p-2 rounded-full border border-black/10 dark:border-white/10 hover:bg-black/5 dark:hover:bg-white/10 text-gray-900 dark:text-white transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                aria-label="Précédent"
                            >
                                <ChevronLeft size={20} />
                            </button>
                            <button 
                                onClick={() => setRecPage(p => Math.min(totalRecPages - 1, p + 1))}
                                disabled={recPage >= totalRecPages - 1 || totalRecPages === 0}
                                className="p-2 rounded-full border border-black/10 dark:border-white/10 hover:bg-black/5 dark:hover:bg-white/10 text-gray-900 dark:text-white transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                aria-label="Suivant"
                            >
                                <ChevronRight size={20} />
                            </button>
                        </div>
                    </div>
                    
                    {recommendations.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                             {recommendations.map(recommendedMovie => (
                                 <MovieCard 
                                    key={recommendedMovie.id}
                                    id={recommendedMovie.id}
                                    title={recommendedMovie.title}
                                    description={recommendedMovie.description}
                                    year={String(recommendedMovie.year)}
                                    rating={recommendedMovie.rating}
                                    imageUrl={recommendedMovie.imageUrl}
                                 />
                             ))}
                        </div>
                    ) : (
                         <p className="text-gray-500 italic">Aucune recommandation disponible pour ce film.</p>
                    )}
                </div>
            </div>

            <AddEventModal 
                isOpen={isEventModalOpen} 
                onClose={() => setIsEventModalOpen(false)} 
                onAddEvent={handleAddEvent}
                preselectedMovieId={movie.id}
            />

            {/* Video Modal */}
            {isVideoModalOpen && movie.trailerKey && (
                <div 
                    className="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-white/90 dark:bg-black/90 backdrop-blur-sm"
                    onClick={() => setIsVideoModalOpen(false)}
                >
                    <div 
                        className="relative w-full max-w-5xl mx-auto" 
                        onClick={e => e.stopPropagation()}
                    >
                        <button 
                            onClick={() => setIsVideoModalOpen(false)}
                            className="absolute -top-12 right-0 sm:-right-12 sm:top-0 z-10 p-2 bg-black/10 dark:bg-white/10 hover:bg-black/20 dark:hover:bg-white/20 rounded-full text-gray-900 dark:text-white transition-colors"
                        >
                            <X className="w-6 h-6" />
                        </button>
                        
                        <div className="relative w-full bg-black rounded-xl overflow-hidden shadow-2xl border border-black/10 dark:border-white/10" style={{ paddingTop: '56.25%' }}>
                            <iframe 
                                src={`https://www.youtube.com/embed/${movie.trailerKey}?autoplay=1`} 
                                title="Trailer"
                                className="absolute top-0 left-0 w-full h-full"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowFullScreen
                            ></iframe>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default MovieDetails;
