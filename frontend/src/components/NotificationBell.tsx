import { useState, useEffect, useRef } from 'react';
import { Bell, Check, CheckCheck, Film, Clock } from 'lucide-react';
import { useAuth } from '../context/AuthContext';
import { Link } from 'react-router-dom';
import api from '../services/api';

interface Notification {
    id: number;
    message: string;
    type: string;
    isRead: boolean;
    movieId: string | null;
    eventId: string | null;
    createdAt: string;
    eventDate: string | null;
}

const NotificationBell = () => {
    const { isAuthenticated } = useAuth();
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    const fetchNotifications = async () => {
        if (!isAuthenticated) return;
        try {
            const response = await api.get('/notifications');
            setNotifications(response.data.notifications || []);
            setUnreadCount(response.data.unreadCount || 0);
        } catch {
            // Silently fail
        }
    };

    useEffect(() => {
        fetchNotifications();
        // Poll every 60 seconds
        const interval = setInterval(fetchNotifications, 60000);
        return () => clearInterval(interval);
    }, [isAuthenticated]);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const markAsRead = async (id: number) => {
        try {
            await api.patch(`/notifications/read/${id}`);
            setNotifications(prev => prev.map(n => n.id === id ? { ...n, isRead: true } : n));
            setUnreadCount(prev => Math.max(0, prev - 1));
        } catch { /* silently fail */ }
    };

    const markAllAsRead = async () => {
        try {
            await api.patch('/notifications/read-all');
            setNotifications(prev => prev.map(n => ({ ...n, isRead: true })));
            setUnreadCount(0);
        } catch { /* silently fail */ }
    };

    const formatDate = (dateStr: string) => {
        const date = new Date(dateStr);
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return "À l'instant";
        if (diffMins < 60) return `Il y a ${diffMins}min`;
        if (diffHours < 24) return `Il y a ${diffHours}h`;
        if (diffDays < 7) return `Il y a ${diffDays}j`;
        return date.toLocaleDateString('fr-FR');
    };

    if (!isAuthenticated) return null;

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Bell Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="relative p-2 text-gray-300 hover:text-white rounded-lg hover:bg-white/10 transition-all duration-200"
                title="Notifications"
                id="notification-bell"
            >
                <Bell size={20} className={unreadCount > 0 ? 'animate-[swing_1s_ease-in-out]' : ''} />
                {unreadCount > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-full shadow-lg shadow-purple-500/30 animate-pulse">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {/* Dropdown */}
            {isOpen && (
                <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-neutral-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl shadow-black/50 z-50 overflow-hidden">
                    {/* Header */}
                    <div className="flex items-center justify-between px-4 py-3 border-b border-white/10">
                        <h3 className="text-sm font-semibold text-white flex items-center gap-2">
                            <Bell size={14} className="text-purple-400" />
                            Notifications
                            {unreadCount > 0 && (
                                <span className="text-xs bg-purple-500/20 text-purple-400 px-2 py-0.5 rounded-full">
                                    {unreadCount}
                                </span>
                            )}
                        </h3>
                        {unreadCount > 0 && (
                            <button
                                onClick={markAllAsRead}
                                className="text-xs text-purple-400 hover:text-purple-300 flex items-center gap-1 transition-colors"
                            >
                                <CheckCheck size={12} />
                                Tout lire
                            </button>
                        )}
                    </div>

                    {/* Notification List */}
                    <div className="max-h-80 overflow-y-auto">
                        {notifications.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-10 px-4">
                                <Bell size={32} className="text-gray-600 mb-3" />
                                <p className="text-sm text-gray-500">Aucune notification</p>
                                <p className="text-xs text-gray-600 mt-1">Planifiez un film dans l'agenda pour recevoir des rappels</p>
                            </div>
                        ) : (
                            notifications.map((notif) => (
                                <div
                                    key={notif.id}
                                    className={`flex items-start gap-3 px-4 py-3 border-b border-white/5 transition-colors cursor-pointer ${
                                        notif.isRead 
                                            ? 'bg-transparent hover:bg-white/5' 
                                            : 'bg-purple-500/5 hover:bg-purple-500/10'
                                    }`}
                                    onClick={() => !notif.isRead && markAsRead(notif.id)}
                                >
                                    {/* Icon */}
                                    <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${
                                        notif.type === 'reminder' 
                                            ? 'bg-purple-500/20 text-purple-400' 
                                            : 'bg-blue-500/20 text-blue-400'
                                    }`}>
                                        {notif.type === 'reminder' ? <Clock size={14} /> : <Film size={14} />}
                                    </div>

                                    {/* Content */}
                                    <div className="flex-1 min-w-0">
                                        <p className={`text-sm leading-snug ${notif.isRead ? 'text-gray-400' : 'text-white'}`}>
                                            {notif.message}
                                        </p>
                                        <div className="flex items-center gap-2 mt-1">
                                            <span className="text-[11px] text-gray-500">
                                                {formatDate(notif.createdAt)}
                                            </span>
                                            {notif.movieId && (
                                                <Link
                                                    to={`/movie/${notif.movieId}`}
                                                    className="text-[11px] text-purple-400 hover:text-purple-300"
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    Voir le film →
                                                </Link>
                                            )}
                                        </div>
                                    </div>

                                    {/* Read indicator */}
                                    {!notif.isRead && (
                                        <div className="flex-shrink-0 w-2 h-2 rounded-full bg-purple-500 mt-2" />
                                    )}
                                    {notif.isRead && (
                                        <Check size={14} className="flex-shrink-0 text-gray-600 mt-1" />
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default NotificationBell;
