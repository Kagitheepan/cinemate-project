import { useState, useEffect } from 'react';
import { Plus } from 'lucide-react';
import {
    DndContext,
    DragOverlay,
    closestCorners,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
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

const Watchlist = () => {
    const { user, updateUser } = useAuth();
    const { movies, getMovie } = useMovies();
    
    // Lists of full Movie objects
    const [toWatchMovies, setToWatchMovies] = useState<Movie[]>([]);
    const [watchedMovies, setWatchedMovies] = useState<Movie[]>([]);
    
    const [activeId, setActiveId] = useState<string | null>(null);

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
            // Update context immediately for optimistic UI
            // Cast to any because our TS type says string[], but we want to store object structure if backend allows.
            // Backend User.php has `watchlist` as `array`. Doctrine JSON type maps to array/object.
            // So we can store object.
            // We need to trick TS or update TS interface. 
            // I updated TS interface to string[]. I should update it to any or specific shape.
            // For now cast to any.
            updateUser({ watchlist: data as any });
            await api.put('/profile', { watchlist: data });
        } catch (error) {
            console.error("Failed to save watchlist", error);
        }
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
            <div className="min-h-screen bg-neutral-950 pt-32 pb-12 flex justify-center">
                <p className="text-gray-400">Veuillez vous connecter pour voir votre watchlist.</p>
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
            <div className="min-h-screen bg-neutral-950 pt-32 pb-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-white mb-12 text-center">Ma Watchlist</h1>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8 relative items-start">
                        {/* Vertical Divider */}
                        <div className="hidden md:block absolute top-0 bottom-0 left-1/2 w-px bg-gradient-to-b from-transparent via-white/10 to-transparent -translate-x-1/2 min-h-[500px]"></div>

                        {/* Left Column: Film à voir */}
                        <div className="flex flex-col space-y-8 bg-white/5 rounded-2xl p-6 min-h-[600px] border border-white/5 relative">
                            <div className="text-center mb-4">
                                <h2 className="text-2xl font-semibold text-purple-400">Films à voir</h2>
                                <p className="text-gray-400 text-sm mt-1">Vos prochaines découvertes</p>
                            </div>

                            <SortableContext 
                                id="toWatch" 
                                items={toWatchMovies.map(m => m.id)} 
                                strategy={verticalListSortingStrategy}
                            >
                                <div className="space-y-4 px-2 flex-grow min-h-[200px]" id="toWatchContainer">
                                    {toWatchMovies.map((movie) => (
                                        <SortableItem key={movie.id} id={movie.id}>
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
                                    ))}
                                </div>
                            </SortableContext>
                            
                            <div className="flex justify-center mt-auto pt-4">
                                <Button variant="secondary" className="flex items-center gap-2">
                                    <Plus size={18} />
                                    Ajouter un film
                                </Button>
                            </div>
                        </div>

                        {/* Right Column: Film Vu */}
                        <div className="flex flex-col space-y-8 bg-white/5 rounded-2xl p-6 min-h-[600px] border border-white/5 relative">
                            <div className="text-center mb-4">
                                <h2 className="text-2xl font-semibold text-green-400">Films vus</h2>
                                <p className="text-gray-400 text-sm mt-1">Votre historique de visionnage</p>
                            </div>

                            <SortableContext 
                                id="watched" 
                                items={watchedMovies.map(m => m.id)} 
                                strategy={verticalListSortingStrategy}
                            >
                                <div className="space-y-4 px-2 flex-grow min-h-[200px]" id="watchedContainer">
                                    {watchedMovies.map((movie) => (
                                        <SortableItem key={movie.id} id={movie.id}>
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
                                    ))}
                                </div>
                            </SortableContext>

                            <div className="flex justify-center mt-auto pt-4">
                                <Button variant="secondary" className="flex items-center gap-2">
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
        </DndContext>
    );
};

export default Watchlist;
