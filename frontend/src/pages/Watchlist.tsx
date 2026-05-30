import { useState, useEffect } from 'react';
import { Plus, X } from 'lucide-react';
import {
    DndContext,
    DragOverlay,
    closestCorners,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    useDroppable,
    type DragStartEvent,
    type DragOverEvent,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';

import MovieCard from '../components/MovieCard';
import Button from '../components/ui/Button';
import { SortableItem } from '../components/SortableItem';
import { useAuth } from '../context/AuthContext';
import { useMovies, type Movie } from '../context/MovieContext';
import api from '../services/api';

interface WatchlistData {
    toWatch: string[];
    watched: string[];
}

const DroppableContainer = ({ id, children, className }: { id: string, children: React.ReactNode, className?: string }) => {
    const { setNodeRef } = useDroppable({ id });
    return (
        <div ref={setNodeRef} className={className} id={id}>
            {children}
        </div>
    );
};

const Watchlist = () => {
    const { user, updateUser } = useAuth();
    const { movies, getMovie } = useMovies();
    
    // Lists of full Movie objects
    const [toWatchMovies, setToWatchMovies] = useState<Movie[]>([]);
    const [watchedMovies, setWatchedMovies] = useState<Movie[]>([]);
    
    const [activeId, setActiveId] = useState<string | null>(null);
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);
    const [addModalTarget, setAddModalTarget] = useState<'toWatch' | 'watched' | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    // Hydrate lists from user profile
    useEffect(() => {
        if (user && user.watchlist) {
            let data: WatchlistData = { toWatch: [], watched: [] };
            
            // Handle legacy array format or new object format
            if (Array.isArray(user.watchlist)) {
                // If it's just an array, assume it's 'toWatch' for now, or check structure
                // But wait, if we save { toWatch: [], watched: [] }, it comes back as object.
                // If backend/php casts to array, it might be [ 'toWatch' => [], 'watched' => [] ]
                // which JS sees as object.
                // If it was stored as simple array [id1, id2], we map to toWatch.
                const isSimpleArray = user.watchlist.length > 0 && typeof user.watchlist[0] === 'string';
                if (isSimpleArray) {
                     data.toWatch = user.watchlist as unknown as string[];
                } else {
                     // It might be the object structure spread? No, user.watchlist IS the data.
                     // DB stores JSON. PHP deserializes.
                     // If we saved { toWatch: [...], watched: [...] }, it should be that object.
                     // Is it possible user.watchlist is typed as string[] in TS but has object at runtime?
                     // Yes.
                     const raw = user.watchlist as unknown as any;
                     if (raw.toWatch || raw.watched) {
                         data = raw;
                     }
                }
            } else {
                 // Object structure
                 const raw = user.watchlist as unknown as any;
                 if (raw && (raw.toWatch || raw.watched)) {
                     data = raw;
                 }
            }

            // Hydrate movies
            if (movies.length > 0) {
                const toWatchResolves = (data.toWatch || []).map(id => getMovie(id)).filter((m): m is Movie => !!m);
                const watchedResolves = (data.watched || []).map(id => getMovie(id)).filter((m): m is Movie => !!m);
                
                setToWatchMovies(toWatchResolves);
                setWatchedMovies(watchedResolves);
            }
        }
    }, [user, movies, getMovie]);

    const saveWatchlist = async (newToWatch: Movie[], newWatched: Movie[]) => {
        if (!user) return;

        const data: WatchlistData = {
            toWatch: newToWatch.map(m => m.id),
            watched: newWatched.map(m => m.id)
        };

        try {
            updateUser({ watchlist: data as any });
            await api.put('/profile', { watchlist: data });
        } catch (error) {
            console.error("Failed to save watchlist", error);
        }
    };

    const handleAddMovie = (movie: Movie) => {
        if (!addModalTarget) return;

        const newToWatch = addModalTarget === 'toWatch' 
            ? [...toWatchMovies.filter(m => m.id !== movie.id), movie]
            : toWatchMovies.filter(m => m.id !== movie.id);
            
        const newWatched = addModalTarget === 'watched'
            ? [...watchedMovies.filter(m => m.id !== movie.id), movie]
            : watchedMovies.filter(m => m.id !== movie.id);

        setToWatchMovies(newToWatch);
        setWatchedMovies(newWatched);
        saveWatchlist(newToWatch, newWatched);
        setIsAddModalOpen(false);
        setSearchQuery('');
    };

    const moveToWatched = (movie: Movie) => {
        const newToWatch = toWatchMovies.filter(m => m.id !== movie.id);
        const newWatched = [...watchedMovies, movie];
        setToWatchMovies(newToWatch);
        setWatchedMovies(newWatched);
        saveWatchlist(newToWatch, newWatched);
    };

    const moveToToWatch = (movie: Movie) => {
        const newWatched = watchedMovies.filter(m => m.id !== movie.id);
        const newToWatch = [...toWatchMovies, movie];
        setWatchedMovies(newWatched);
        setToWatchMovies(newToWatch);
        saveWatchlist(newToWatch, newWatched);
    };

    const removeMovie = (movie: Movie, from: 'toWatch' | 'watched') => {
        let newToWatch = toWatchMovies;
        let newWatched = watchedMovies;
        if (from === 'toWatch') {
            newToWatch = toWatchMovies.filter(m => m.id !== movie.id);
            setToWatchMovies(newToWatch);
        } else {
            newWatched = watchedMovies.filter(m => m.id !== movie.id);
            setWatchedMovies(newWatched);
        }
        saveWatchlist(newToWatch, newWatched);
    };

    const clearList = (listName: 'toWatch' | 'watched') => {
        if (!confirm(`Voulez-vous vraiment vider la liste "${listName === 'toWatch' ? 'Films à voir' : 'Films vus'}" ?`)) return;
        
        let newToWatch = toWatchMovies;
        let newWatched = watchedMovies;
        
        if (listName === 'toWatch') {
            newToWatch = [];
            setToWatchMovies([]);
        } else {
            newWatched = [];
            setWatchedMovies([]);
        }
        saveWatchlist(newToWatch, newWatched);
    };

    // Sensors
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
    );

    const findContainer = (id: string) => {
        if (toWatchMovies.find((m) => m.id === id)) return 'toWatch';
        if (watchedMovies.find((m) => m.id === id)) return 'watched';
        return null;
    };

    const handleDragStart = (event: DragStartEvent) => {
        setActiveId(event.active.id as string);
    };

    const handleDragOver = (event: DragOverEvent) => {
        const { active, over } = event;
        const overId = over?.id;
        if (!overId || active.id === overId) return;

        const activeContainer = findContainer(active.id as string);
        const overContainer = findContainer(overId as string) || (overId === 'toWatchContainer' ? 'toWatch' : overId === 'watchedContainer' ? 'watched' : null);

        if (!activeContainer || !overContainer || activeContainer === overContainer) return;

        // Move item between containers
        if (activeContainer === 'toWatch') {
             const activeItem = toWatchMovies.find(m => m.id === active.id);
             if (activeItem) {
                 const newToWatch = toWatchMovies.filter((item) => item.id !== active.id);
                 const newWatched = [...watchedMovies, activeItem];
                 setToWatchMovies(newToWatch);
                 setWatchedMovies(newWatched);
                 // Don't save on DragOver, only DragEnd
             }
        } else {
             const activeItem = watchedMovies.find(m => m.id === active.id);
             if (activeItem) {
                 const newWatched = watchedMovies.filter((item) => item.id !== active.id);
                 const newToWatch = [...toWatchMovies, activeItem];
                 setWatchedMovies(newWatched);
                 setToWatchMovies(newToWatch);
             }
        }
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        const activeContainer = findContainer(active.id as string);
        const overContainer = findContainer(over?.id as string) || (over?.id === 'toWatchContainer' ? 'toWatch' : over?.id === 'watchedContainer' ? 'watched' : null);

        let finalToWatch = [...toWatchMovies];
        let finalWatched = [...watchedMovies];

        if (activeContainer && overContainer && activeContainer === overContainer) {
            const activeIndex = (activeContainer === 'toWatch' ? finalToWatch : finalWatched).findIndex((m) => m.id === active.id);
            const overIndex = (overContainer === 'toWatch' ? finalToWatch : finalWatched).findIndex((m) => m.id === over?.id);

            if (activeIndex !== overIndex) {
                 if (activeContainer === 'toWatch') {
                     finalToWatch = arrayMove(finalToWatch, activeIndex, overIndex);
                     setToWatchMovies(finalToWatch);
                 } else {
                     finalWatched = arrayMove(finalWatched, activeIndex, overIndex);
                     setWatchedMovies(finalWatched);
                 }
            }
        }
        
        setActiveId(null);
        // Persist changes
        saveWatchlist(finalToWatch, finalWatched);
    };

    if (!user) {
         return (
            <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-32 pb-12 flex justify-center transition-colors duration-300">
                <p className="text-gray-600 dark:text-gray-400">Veuillez vous connecter pour voir votre watchlist.</p>
            </div>
        );
    }

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragEnd={handleDragEnd}
        >
            <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-32 pb-12 transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-12 text-center">Ma Watchlist</h1>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 relative items-start">
                        {/* Vertical Divider */}
                        <div className="hidden md:block absolute top-0 bottom-0 left-1/2 w-px bg-gradient-to-b from-transparent via-black/10 dark:via-white/10 to-transparent -translate-x-1/2 min-h-[500px]"></div>

                        {/* Left Column: Film à voir */}
                        <div className="flex flex-col space-y-8 bg-white dark:bg-white/5 rounded-2xl p-6 min-h-[600px] border border-black/5 dark:border-white/5 shadow-lg dark:shadow-none relative">
                            <div className="text-center mb-4 relative">
                                <h2 className="text-2xl font-semibold text-purple-400">Films à voir</h2>
                                <p className="text-gray-600 dark:text-gray-400 text-sm mt-1">Vos prochaines découvertes</p>
                                {toWatchMovies.length > 0 && (
                                    <button 
                                        onClick={() => clearList('toWatch')}
                                        className="absolute right-0 top-0 text-xs text-red-400 hover:text-red-300 transition-colors bg-red-500/10 px-2 py-1 rounded"
                                    >
                                        Tout vider
                                    </button>
                                )}
                            </div>

                            <SortableContext 
                                id="toWatch" 
                                items={toWatchMovies.map(m => m.id)} 
                                strategy={verticalListSortingStrategy}
                            >
                                <DroppableContainer id="toWatchContainer" className="space-y-6 px-2 flex-grow min-h-[200px]">
                                    {toWatchMovies.map((movie) => (
                                        <div key={movie.id} className="flex flex-col space-y-3">
                                            <SortableItem id={movie.id}>
                                                <div className="transform transition-transform cursor-grab active:cursor-grabbing">
                                                    <MovieCard 
                                                        id={movie.id}
                                                        title={movie.title}
                                                        description={movie.description}
                                                        year={String(movie.year)}
                                                        rating={movie.rating}
                                                        imageUrl={movie.imageUrl}
                                                    />
                                                </div>
                                            </SortableItem>
                                            <div className="flex gap-2 px-1">
                                                <Button size="sm" variant="secondary" className="flex-1 text-xs" onClick={() => moveToWatched(movie)}>
                                                    Passer aux vus
                                                </Button>
                                                <Button size="sm" variant="ghost" className="text-red-400 hover:text-red-300 hover:bg-red-500/10 px-3" onClick={() => removeMovie(movie, 'toWatch')}>
                                                    <X size={16} />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </DroppableContainer>
                            </SortableContext>
                            
                            <div className="flex justify-center mt-auto pt-4">
                                <Button 
                                    variant="secondary" 
                                    className="flex items-center gap-2"
                                    onClick={() => {
                                        setAddModalTarget('toWatch');
                                        setIsAddModalOpen(true);
                                        setSearchQuery('');
                                    }}
                                >
                                    <Plus size={18} />
                                    Ajouter un film
                                </Button>
                            </div>
                        </div>

                        {/* Right Column: Film Vu */}
                        <div className="flex flex-col space-y-8 bg-white dark:bg-white/5 rounded-2xl p-6 min-h-[600px] border border-black/5 dark:border-white/5 shadow-lg dark:shadow-none relative">
                            <div className="text-center mb-4 relative">
                                <h2 className="text-2xl font-semibold text-green-400">Films vus</h2>
                                <p className="text-gray-600 dark:text-gray-400 text-sm mt-1">Votre historique de visionnage</p>
                                {watchedMovies.length > 0 && (
                                    <button 
                                        onClick={() => clearList('watched')}
                                        className="absolute right-0 top-0 text-xs text-red-400 hover:text-red-300 transition-colors bg-red-500/10 px-2 py-1 rounded"
                                    >
                                        Tout vider
                                    </button>
                                )}
                            </div>

                            <SortableContext 
                                id="watched" 
                                items={watchedMovies.map(m => m.id)} 
                                strategy={verticalListSortingStrategy}
                            >
                                <DroppableContainer id="watchedContainer" className="space-y-6 px-2 flex-grow min-h-[200px]">
                                    {watchedMovies.map((movie) => (
                                        <div key={movie.id} className="flex flex-col space-y-3">
                                            <SortableItem id={movie.id}>
                                                <div className="transform transition-transform opacity-90 hover:opacity-100 cursor-grab active:cursor-grabbing">
                                                     <MovieCard 
                                                        id={movie.id}
                                                        title={movie.title}
                                                        description={movie.description}
                                                        year={String(movie.year)}
                                                        rating={movie.rating}
                                                        imageUrl={movie.imageUrl}
                                                    />
                                                </div>
                                            </SortableItem>
                                            <div className="flex gap-2 px-1">
                                                <Button size="sm" variant="secondary" className="flex-1 text-xs" onClick={() => moveToToWatch(movie)}>
                                                    Passer aux à voir
                                                </Button>
                                                <Button size="sm" variant="ghost" className="text-red-400 hover:text-red-300 hover:bg-red-500/10 px-3" onClick={() => removeMovie(movie, 'watched')}>
                                                    <X size={16} />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </DroppableContainer>
                            </SortableContext>

                            <div className="flex justify-center mt-auto pt-4">
                                <Button 
                                    variant="secondary" 
                                    className="flex items-center gap-2"
                                    onClick={() => {
                                        setAddModalTarget('watched');
                                        setIsAddModalOpen(true);
                                        setSearchQuery('');
                                    }}
                                >
                                    <Plus size={18} />
                                    Ajouter un film
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <DragOverlay>
                {activeId ? (
                     <div className="transform scale-105 shadow-2xl rotate-2 opacity-90 pointer-events-none w-[300px] sm:w-[350px]">
                        {(() => {
                            const movie = [...toWatchMovies, ...watchedMovies].find(m => m.id === activeId);
                            return movie ? <MovieCard 
                                id={movie.id}
                                title={movie.title}
                                description={movie.description}
                                year={String(movie.year)}
                                rating={movie.rating}
                                imageUrl={movie.imageUrl}
                            /> : null;
                        })()}
                    </div>
                ) : null}
            </DragOverlay>

            {isAddModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white/80 dark:bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-2xl w-full max-w-md p-6 shadow-2xl relative">
                        <button 
                            onClick={() => setIsAddModalOpen(false)}
                            className="absolute top-4 right-4 text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors"
                        >
                            <X size={20} />
                        </button>
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            Ajouter à {addModalTarget === 'toWatch' ? 'Films à voir' : 'Films vus'}
                        </h2>
                        <div className="space-y-4">
                            <input 
                                type="text" 
                                placeholder="Rechercher un film..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-lg p-3 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none"
                            />
                            <div className="max-h-60 overflow-y-auto custom-scrollbar space-y-2">
                                {searchQuery ? movies.filter(m => m.title.toLowerCase().includes(searchQuery.toLowerCase())).map(movie => (
                                    <div key={movie.id} className="flex justify-between items-center bg-white dark:bg-neutral-800 p-2 rounded-lg border border-black/5 dark:border-white/5">
                                        <div className="flex items-center gap-3">
                                            {movie.imageUrl && (
                                                <img src={movie.imageUrl} alt={movie.title} className="w-8 h-12 object-cover rounded" />
                                            )}
                                            <span className="text-gray-900 dark:text-white text-sm font-medium">{movie.title}</span>
                                        </div>
                                        <button 
                                            onClick={() => handleAddMovie(movie)}
                                            className="bg-gray-100 dark:bg-white/5 hover:bg-purple-600 text-gray-900 dark:text-white hover:text-white px-3 py-1.5 rounded text-sm transition-colors border border-black/10 dark:border-white/10"
                                        >
                                            Ajouter
                                        </button>
                                    </div>
                                )) : (
                                    <p className="text-gray-600 dark:text-gray-500 text-sm text-center py-4">Commencez à taper pour chercher...</p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </DndContext>
    );
};

export default Watchlist;

