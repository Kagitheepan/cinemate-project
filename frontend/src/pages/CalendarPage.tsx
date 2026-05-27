import { useState, useEffect } from 'react';
import { 
    format, 
    startOfMonth, 
    endOfMonth, 
    startOfWeek, 
    endOfWeek, 
    eachDayOfInterval, 
    isSameMonth, 
    isSameDay, 
    addMonths, 
    subMonths,
    isToday
} from 'date-fns';
import { fr } from 'date-fns/locale';
import { ChevronLeft, ChevronRight, Calendar as CalendarIcon, List, Plus, Trash2, X } from 'lucide-react';
import Button from '../components/ui/Button';
import AddEventModal, { type NewEvent } from '../components/AddEventModal';
import { useAuth, type CalendarEvent } from '../context/AuthContext';
import { useMovies } from '../context/MovieContext';
import api from '../services/api';

const CalendarPage = () => {
    const { user, updateUser } = useAuth();
    const { movies } = useMovies();
    
    // We'll manage events locally but sync with user.agenda
    const [currentDate, setCurrentDate] = useState(new Date());
    const [view, setView] = useState<'calendar' | 'list'>('calendar');
    const [events, setEvents] = useState<NewEvent[]>([]);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedDate, setSelectedDate] = useState<Date | null>(null);

    // Sync events with user.agenda
    useEffect(() => {
        if (user && user.agenda) {
            const mappedEvents: NewEvent[] = user.agenda.map(item => ({
                id: item.id,
                movieId: item.movieId,
                title: item.title,
                date: new Date(item.start),
                color: 'bg-purple-500' // Default or derive from type
            }));
            setEvents(mappedEvents);
        } else {
             setEvents([]);
        }
    }, [user]);

    const saveAgenda = async (newEvents: NewEvent[]) => {
        // Convert NewEvent back to CalendarEvent format for storage
        const agendaData: CalendarEvent[] = newEvents.map(e => ({
            id: e.id,
            movieId: e.movieId,
            title: e.title,
            start: e.date.toISOString(),
            end: new Date(e.date.getTime() + 2 * 60 * 60 * 1000).toISOString() // Assume 2h duration
        }));

        try {
            // Optimistic update
            updateUser({ agenda: agendaData });
            await api.put('/profile', { agenda: agendaData });
        } catch (error) {
            console.error("Failed to save agenda", error);
        }
    };

    const nextMonth = () => setCurrentDate(addMonths(currentDate, 1));
    const prevMonth = () => setCurrentDate(subMonths(currentDate, 1));
    const goToToday = () => setCurrentDate(new Date());

    const daysInMonth = eachDayOfInterval({
        start: startOfWeek(startOfMonth(currentDate), { weekStartsOn: 1 }),
        end: endOfWeek(endOfMonth(currentDate), { weekStartsOn: 1 })
    });

    const getEventsForDay = (date: Date) => {
        return events.filter(event => isSameDay(event.date, date));
    };

    const handleAddEvent = (newEvent: Omit<NewEvent, 'id'>) => {
        if (!user) {
            alert("Veuillez vous connecter pour ajouter des événements.");
            return;
        }
        const eventWithId = {
            ...newEvent,
            id: Math.random().toString(36).substr(2, 9)
        };
        const updatedEvents = [...events, eventWithId];
        setEvents(updatedEvents);
        saveAgenda(updatedEvents);
    };

    const handleDeleteEvent = (id: string, e?: React.MouseEvent) => {
        e?.stopPropagation(); // Prevent triggering other clicks if necessary
        if (window.confirm('Voulez-vous vraiment supprimer cet événement ?')) {
             const updatedEvents = events.filter(event => event.id !== id);
             setEvents(updatedEvents);
             saveAgenda(updatedEvents);
        }
    };

    const handleDeleteAll = () => {
        if (events.length === 0) return;
        if (window.confirm('ATTENTION: Voulez-vous vraiment supprimer TOUS les événements ? Cette action est irréversible.')) {
            setEvents([]);
            saveAgenda([]);
        }
    };

    const openAddModal = (date?: Date) => {
        if (!user) {
            alert("Veuillez vous connecter pour gérer votre agenda.");
            return;
        }
        setSelectedDate(date || new Date());
        setIsModalOpen(true);
    };

    const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

    if (!user) {
         return (
            <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-24 pb-12 flex justify-center transition-colors duration-300">
                 <div className="text-gray-600 dark:text-gray-400 text-center">
                     <p className="mb-4">Connectez-vous pour accéder à votre agenda.</p>
                 </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-24 pb-12 transition-colors duration-300">
            <AddEventModal 
                isOpen={isModalOpen} 
                onClose={() => setIsModalOpen(false)} 
                onAddEvent={handleAddEvent}
                preselectedDate={selectedDate}
            />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">Mon Agenda</h1>
                        <p className="text-gray-600 dark:text-gray-400">Planifiez vos soirées films et ne manquez aucune sortie.</p>
                    </div>

                    <div className="flex items-center gap-4">
                         <div className="flex bg-white dark:bg-neutral-900 p-1 rounded-lg border border-black/10 dark:border-white/10 self-start md:self-auto">
                            <button
                                onClick={() => setView('calendar')}
                                className={`flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all ${
                                    view === 'calendar' 
                                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/20' 
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5'
                                }`}
                            >
                                <CalendarIcon size={16} className="mr-2" />
                                Calendrier
                            </button>
                            <button
                                onClick={() => setView('list')}
                                className={`flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all ${
                                    view === 'list' 
                                        ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/20' 
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5'
                                }`}
                            >
                                <List size={16} className="mr-2" />
                                Liste
                            </button>
                        </div>
                        
                        {events.length > 0 && (
                            <Button 
                                variant="secondary"
                                className="flex items-center gap-2 hover:bg-red-500/20 hover:text-red-400 border-transparent"
                                onClick={handleDeleteAll}
                                title="Tout supprimer"
                            >
                                <Trash2 size={18} />
                                <span className="hidden sm:inline">Tout effacer</span>
                            </Button>
                        )}
                        
                        <Button className="flex items-center gap-2" onClick={() => openAddModal()}>
                            <Plus size={18} />
                            Ajouter
                        </Button>
                    </div>
                </div>

                {/* Calendar View */}
                {view === 'calendar' && (
                    <div className="bg-white dark:bg-neutral-900/50 border border-black/10 dark:border-white/5 rounded-2xl overflow-hidden backdrop-blur-sm">
                        {/* Calendar Header */}
                        <div className="flex items-center justify-between p-6 border-b border-black/10 dark:border-white/5">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white capitalize">
                                {format(currentDate, 'MMMM yyyy', { locale: fr })}
                            </h2>
                            <div className="flex items-center space-x-2">
                                <button 
                                    onClick={goToToday}
                                    className="px-3 py-1 text-sm bg-black/5 dark:bg-white/5 hover:bg-black/10 dark:hover:bg-white/10 rounded-md text-gray-700 dark:text-gray-300 transition-colors mr-2"
                                >
                                    Aujourd'hui
                                </button>
                                <button onClick={prevMonth} className="p-2 hover:bg-black/5 dark:hover:bg-white/10 rounded-full text-gray-700 dark:text-gray-300 transition-colors">
                                    <ChevronLeft size={20} />
                                </button>
                                <button onClick={nextMonth} className="p-2 hover:bg-black/5 dark:hover:bg-white/10 rounded-full text-gray-700 dark:text-gray-300 transition-colors">
                                    <ChevronRight size={20} />
                                </button>
                            </div>
                        </div>

                        {/* Days Header */}
                        <div className="grid grid-cols-7 border-b border-black/10 dark:border-white/5 bg-gray-100 dark:bg-neutral-900/80">
                            {weekDays.map(day => (
                                <div key={day} className="py-3 text-center text-sm font-medium text-gray-500">
                                    {day}
                                </div>
                            ))}
                        </div>

                        {/* Days Grid */}
                        <div className="grid grid-cols-7 auto-rows-fr bg-gray-300 dark:bg-neutral-800 gap-[1px] border-b border-black/10 dark:border-white/5">
                            {daysInMonth.map((day, idx) => {
                                const isCurrentMonth = isSameMonth(day, currentDate);
                                const isTodayDate = isToday(day);
                                const dayEvents = getEventsForDay(day);

                                return (
                                    <div 
                                        key={idx} 
                                        className={`min-h-[120px] bg-white dark:bg-neutral-950 p-2 transition-colors hover:bg-gray-50 dark:hover:bg-neutral-900/80 relative group ${
                                            !isCurrentMonth ? 'opacity-30' : ''
                                        }`}
                                    >
                                        <div className={`text-sm font-medium w-7 h-7 flex items-center justify-center rounded-full mb-1 ${
                                            isTodayDate 
                                                ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' 
                                                : 'text-gray-500 dark:text-gray-400'
                                        }`}>
                                            {format(day, 'd')}
                                        </div>

                                        {/* Events */}
                                        <div className="space-y-1 mt-1">
                                            {dayEvents.map(event => (
                                                <div 
                                                    key={event.id}
                                                    className={`text-xs p-1.5 rounded-md truncate text-gray-900 dark:text-white ${event.color} bg-opacity-20 border border-black/5 dark:border-white/5 hover:bg-opacity-30 cursor-pointer transition-all group/event pr-5 relative`}
                                                    title={event.title}
                                                >
                                                    <span className={`inline-block w-2 h-2 rounded-full mr-1.5 ${event.color}`}></span>
                                                    {event.title}
                                                    <button
                                                        onClick={(e) => handleDeleteEvent(event.id, e)}
                                                        className="absolute right-1 top-1/2 -translate-y-1/2 opacity-0 group-hover/event:opacity-100 hover:text-red-400 transition-opacity"
                                                    >
                                                        <X size={12} />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>

                                        <button 
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                openAddModal(day);
                                            }}
                                            className="absolute bottom-2 right-2 w-6 h-6 rounded-full bg-black/10 dark:bg-white/10 hover:bg-purple-600 dark:hover:bg-purple-500 flex items-center justify-center text-gray-900 dark:text-white opacity-0 group-hover:opacity-100 transition-all duration-200 z-10"
                                        >
                                            <span className="text-sm font-bold">+</span>
                                        </button>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* List View */}
                {view === 'list' && (
                    <div className="space-y-4">
                        {events
                            .sort((a, b) => a.date.getTime() - b.date.getTime())
                            .map(event => {
                             const movie = movies.find(m => m.id === event.movieId);
                             
                             return (
                                <div key={event.id} className="flex items-center gap-4 bg-white dark:bg-neutral-900/50 border border-black/10 dark:border-white/5 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-900/80 transition-colors group">
                                    <div className="flex flex-col items-center justify-center w-16 h-16 bg-black/5 dark:bg-white/5 rounded-lg border border-black/10 dark:border-white/10 group-hover:border-purple-500/30 transition-colors shrink-0">
                                        <span className="text-xl font-bold text-gray-900 dark:text-white">{format(event.date, 'd')}</span>
                                        <span className="text-xs text-gray-500 uppercase">{format(event.date, 'MMM', { locale: fr })}</span>
                                    </div>

                                    <div className="flex-grow">
                                        <div className="flex items-center gap-2 mb-1">
                                            <span className={`w-2 h-2 rounded-full ${event.color}`}></span>
                                            <div className="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2">
                                                 <h3 className="font-semibold text-gray-900 dark:text-white text-lg">{event.title}</h3>
                                                 <span className="text-sm text-gray-500">à {format(event.date, 'HH:mm')}</span>
                                            </div>
                                        </div>
                                        {movie && <p className="text-sm text-gray-600 dark:text-gray-400">Film : {movie.title}</p>}
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <button className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-black dark:hover:text-white bg-black/5 dark:bg-white/5 hover:bg-black/10 dark:hover:bg-white/10 rounded-lg transition-colors">
                                            Détails
                                        </button>
                                        <button 
                                            onClick={(e) => handleDeleteEvent(event.id, e)}
                                            className="p-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 bg-black/5 dark:bg-white/5 hover:bg-black/10 dark:hover:bg-white/10 rounded-lg transition-colors"
                                            title="Supprimer"
                                        >
                                            <Trash2 size={18} />
                                        </button>
                                    </div>
                                </div>
                             )
                        })}
                        
                        {events.length === 0 && (
                            <div className="text-center py-20 text-gray-500">
                                Aucun événement prévu.
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
};

export default CalendarPage;
