import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Play, Plus, Share2, Star, Calendar, Clock, ChevronLeft, ChevronRight, User } from 'lucide-react';
import Button from '../components/ui/Button';
import MovieCard from '../components/MovieCard';
import { useMovies, type Movie } from '../context/MovieContext';
import { useAuth } from '../context/AuthContext';
import api from '../services/api';
import AddEventModal, { type NewEvent } from '../components/AddEventModal';

const MovieDetails = () => {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const { fetchMovieDetails, getMovie, movies, isLoading: isContextLoading } = useMovies();
    const { user, updateUser } = useAuth();
    const [movie, setMovie] = useState<Movie | undefined>(undefined);
    const [isLocalLoading, setIsLocalLoading] = useState(false);
    const [isEventModalOpen, setIsEventModalOpen] = useState(false);
    
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

    // Scroll to top when ID changes
    useEffect(() => {
        window.scrollTo(0, 0);
    }, [id]);

    // Only show full screen loader if we have NO data at all
    if (isContextLoading && !movie) {
        return (
             <div className="min-h-screen flex items-center justify-center bg-neutral-950 text-white">
                 <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
             </div>
        );
    }

    if (!movie && !isLocalLoading) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-neutral-950 text-white">
                <h2 className="text-2xl font-bold mb-4">Film non trouvé</h2>
                <Button onClick={() => navigate('/')}>Retour à l'accueil</Button>
            </div>
        );
    }

    // Fallback for missing movie during initial load
    if (!movie) return null;

    if (!movie) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-neutral-950 text-white">
                <h2 className="text-2xl font-bold mb-4">Film non trouvé</h2>
                <Button onClick={() => navigate('/')}>Retour à l'accueil</Button>
            </div>
        );
    }

    // Filter recommendations (same category, excluding current movie) - take 3
    // Use movies from context
    const recommendations = movies
        .filter(m => m.category === movie.category && m.id !== movie.id)
        .slice(0, 3);

    const isInWatchlist = Array.isArray(user?.watchlist) ? user.watchlist.includes(movie.id) : false;

    const handleToggleWatchlist = async () => {
        if (!user) {
            alert("Veuillez vous connecter pour gérer votre Watchlist.");
            return;
        }

        const currentWatchlist = Array.isArray(user.watchlist) ? user.watchlist : [];
        const newWatchlist = isInWatchlist 
            ? currentWatchlist.filter(id => id !== movie.id)
            : [...currentWatchlist, movie.id];
            
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
        <div className="min-h-screen bg-neutral-950 pt-20 pb-12">
            {/* Back Button */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <button 
                    onClick={() => navigate(-1)} 
                    className="flex items-center text-gray-400 hover:text-white transition-colors"
                >
                    <ArrowLeft className="w-5 h-5 mr-2" />
                    Retour
                </button>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    {/* Left Column: Poster */}
                    <div className="lg:col-span-1">
                        <div className="aspect-[2/3] w-full rounded-2xl overflow-hidden shadow-2xl shadow-purple-900/20 bg-neutral-900 relative group">
                             {movie.imageUrl ? (
                                <img src={movie.imageUrl} alt={movie.title} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center bg-neutral-800">
                                    <span className="text-white/20 text-6xl font-black uppercase tracking-tighter mix-blend-overlay">Poster</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column: Details */}
                    <div className="lg:col-span-2 space-y-8">
                        <div>
                            <div className="flex flex-wrap items-center gap-4 text-sm text-purple-400 font-medium tracking-wider uppercase mb-3">
                                <span className="px-3 py-1 bg-purple-500/10 rounded-full border border-purple-500/20">
                                    {movie.category || 'Non classé'}
                                </span>
                                {movie.availableOn && movie.availableOn.length > 0 && (
                                    <span className="text-gray-400 normal-case">
                                        Disponible sur : <span className="text-white">{movie.availableOn.join(", ")}</span>
                                    </span>
                                )}
                            </div>
                            
                            <h1 className="text-4xl md:text-5xl font-black text-white mb-4 leading-tight">
                                {movie.title}
                            </h1>

                            <div className="flex items-center space-x-6 text-gray-300 mb-6">
                                <div className="flex items-center">
                                    <Star className="w-5 h-5 text-yellow-500 fill-current mr-2" />
                                    <span className="font-bold text-white">{movie.rating}</span>/10
                                </div>
                                <div className="flex items-center">
                                    <Calendar className="w-5 h-5 mr-2 text-gray-500" />
                                    <span>{movie.year}</span>
                                </div>
                                <div className="flex items-center">
                                    <Clock className="w-5 h-5 mr-2 text-gray-500" />
                                    <span>
                                        {movie.duration && movie.duration > 0 
                                            ? `${Math.floor(movie.duration / 60)}h ${movie.duration % 60}m` 
                                            : "Donnée non disponible"}
                                    </span>
                                </div>
                            </div>

                            <p className="text-lg text-gray-400 leading-relaxed max-w-2xl">
                                {movie.description}
                            </p>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-wrap gap-4">
                            <Button size="lg" className="px-8 shadow-purple-500/25">
                                <Play className="w-5 h-5 mr-2 fill-current" />
                                Regarder
                            </Button>
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
                            <Button variant="ghost" size="icon" className="rounded-full border border-white/10">
                                <Share2 className="w-5 h-5" />
                            </Button>
                        </div>

                        {/* Cast */}
                        <div className="border-t border-white/5 pt-8">
                            <h3 className="text-lg font-bold text-white mb-6">Casting & Équipe</h3>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-6">
                                {movie.cast && movie.cast.length > 0 ? (
                                    movie.cast.map((actor, idx) => (
                                        <div key={idx} className="flex flex-col items-center text-center space-y-3 group cursor-pointer">
                                            <div className="w-20 h-20 rounded-full bg-neutral-800 overflow-hidden border-2 border-transparent group-hover:border-purple-500 transition-all">
                                                {actor.imageUrl ? (
                                                    <img src={actor.imageUrl} alt={actor.name} className="w-full h-full object-cover" />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-500">
                                                        <User size={32} />
                                                    </div>
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium text-white group-hover:text-purple-400 transition-colors">{actor.name}</p>
                                                <p className="text-xs text-gray-500">{actor.role}</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-gray-500 col-span-2">Casting non disponible</p>
                                )}
                                
                                {movie.director && (
                                    <div className="flex flex-col items-center text-center space-y-3">
                                         <div className="w-20 h-20 rounded-full bg-neutral-800 flex items-center justify-center text-gray-500 border border-white/5">
                                             <span className="text-xs font-bold">DIR</span>
                                         </div>
                                         <div>
                                            <p className="font-medium text-white">{movie.director}</p>
                                            <p className="text-xs text-gray-500">Réalisateur</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recommendations */}
                <div className="mt-24 border-t border-white/5 pt-12">
                     <div className="flex items-center justify-between mb-8">
                        <h2 className="text-2xl font-bold text-white">Autres films similaires</h2>
                        <div className="flex space-x-2">
                            <button className="p-2 rounded-full border border-white/10 hover:bg-white/10 text-white transition-colors">
                                <ChevronLeft size={20} />
                            </button>
                            <button className="p-2 rounded-full border border-white/10 hover:bg-white/10 text-white transition-colors">
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
        </div>
    );
};

export default MovieDetails;
