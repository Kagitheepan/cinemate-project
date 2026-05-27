import { useState, useEffect, type FormEvent } from 'react';
import { useAuth } from '../context/AuthContext';
import Button from '../components/ui/Button';
import { User, Mail, Save, Clock, Film, Tv, Play, AlertTriangle } from 'lucide-react';
import api from '../services/api';
import ConfirmModal from '../components/ConfirmModal';

const AVAILABLE_PLATFORMS = ['Netflix', 'Amazon Prime Video', 'Disney+', 'Canal+', 'Apple TV+', 'HBO Max', 'Paramount+'];
const AVAILABLE_GENRES = ['Action', 'Aventure', 'Animation', 'Comédie', 'Crime', 'Documentaire', 'Drame', 'Familial', 'Fantastique', 'Histoire', 'Horreur', 'Musique', 'Mystère', 'Romance', 'Science-Fiction', 'Téléfilm', 'Thriller', 'Guerre', 'Western'];

const Profile = () => {
    const { user, updateUser } = useAuth();
    const [platforms, setPlatforms] = useState<string[]>([]);
    const [genres, setGenres] = useState<string[]>([]);
    const [averageTime, setAverageTime] = useState<number | ''>('');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const { logout } = useAuth();

    useEffect(() => {
        if (user) {
            setPlatforms(user.platforms || []);
            setGenres(user.favoriteGenres || []);
            setAverageTime(user.averageTimeAvailable || '');
        }
    }, [user]);

    const handleTogglePlatform = (platform: string) => {
        setPlatforms(prev => 
            prev.includes(platform) 
                ? prev.filter(p => p !== platform) 
                : [...prev, platform]
        );
    };

    const handleToggleGenre = (genre: string) => {
        setGenres(prev => 
            prev.includes(genre) 
                ? prev.filter(g => g !== genre) 
                : [...prev, genre]
        );
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setMessage(null);

        try {
            const dataToUpdate = {
                platforms,
                favoriteGenres: genres,
                averageTimeAvailable: averageTime === '' ? null : Number(averageTime)
            };

            const response = await api.put('/profile', dataToUpdate);
            
            // Context will be updated if we call updateUser, 
            // but api also returns updated user which we can use.
            if (response.data && response.data.user) {
                updateUser(response.data.user);
            } else {
                updateUser(dataToUpdate);
            }
            
            setMessage({ type: 'success', text: 'Profil mis à jour avec succès !' });
        } catch (error) {
            console.error(error);
            setMessage({ type: 'error', text: 'Erreur lors de la mise à jour.' });
        } finally {
            setLoading(false);
        }
    };

    const handleDeleteAccount = async () => {
        try {
            await api.delete('/profile');
            logout(); // Will clear state and redirect
        } catch (error) {
            console.error(error);
            setMessage({ type: 'error', text: 'Erreur lors de la suppression du compte.' });
        }
    };

    if (!user) {
        return (
            <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-24 pb-12 flex items-center justify-center transition-colors duration-300">
                <div className="text-center text-gray-600 dark:text-gray-400">
                    <p>Veuillez vous connecter pour accéder à votre profil.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-neutral-950 pt-24 pb-12 transition-colors duration-300">
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">Mon Profil</h1>
                    <p className="text-gray-600 dark:text-gray-400">Gérez vos préférences et informations personnelles.</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    {/* Sidebar / User Card */}
                    <div className="md:col-span-1">
                        <div className="bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-xl p-6 shadow-lg shadow-purple-900/10">
                            <div className="flex flex-col items-center mb-6">
                                <div className="w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-4 text-white shadow-xl shadow-purple-500/20">
                                    <User size={40} />
                                </div>
                                <h2 className="text-xl font-bold text-gray-900 dark:text-white">{user.username}</h2>
                                <div className="flex items-center text-gray-600 dark:text-gray-400 mt-1 text-sm">
                                    <Mail size={14} className="mr-2" />
                                    {user.email}
                                </div>
                            </div>
                            
                            <hr className="border-black/10 dark:border-white/10 mb-6" />
                            
                            <div className="space-y-4">
                                <div className="flex items-center text-gray-700 dark:text-gray-300">
                                    <Clock size={18} className="mr-3 text-purple-400" />
                                    <span>{user.averageTimeAvailable ? `${user.averageTimeAvailable} min / jour` : 'Non défini'}</span>
                                </div>
                                <div className="flex items-center text-gray-700 dark:text-gray-300">
                                    <Tv size={18} className="mr-3 text-purple-400" />
                                    <span>{user.platforms.length} plateforme(s)</span>
                                </div>
                                <div className="flex items-center text-gray-700 dark:text-gray-300">
                                    <Film size={18} className="mr-3 text-purple-400" />
                                    <span>{user.favoriteGenres.length} genre(s) favori(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Main Content / Form */}
                    <div className="md:col-span-2">
                        <form onSubmit={handleSubmit} className="bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-xl p-8 shadow-lg">
                            {message && (
                                <div className={`mb-6 p-4 rounded-lg flex items-center ${
                                    message.type === 'success' ? 'bg-green-50 dark:bg-green-500/20 text-green-700 dark:text-green-200 border border-green-300 dark:border-green-500/30' : 'bg-red-50 dark:bg-red-500/20 text-red-700 dark:text-red-200 border border-red-300 dark:border-red-500/30'
                                }`}>
                                    {message.text}
                                </div>
                            )}

                            <div className="space-y-8">
                                {/* Time Available */}
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                        <Clock className="mr-2 text-purple-400" size={20} />
                                        Disponibilité Quotidienne
                                    </h3>
                                    <div className="bg-gray-50 dark:bg-black/20 p-4 rounded-lg border border-black/5 dark:border-white/5">
                                        <label className="block text-sm text-gray-700 dark:text-gray-400 mb-2">Temps disponible moyen (minutes)</label>
                                        <input
                                            type="number"
                                            value={averageTime}
                                            onChange={(e) => setAverageTime(e.target.value === '' ? '' : parseInt(e.target.value))}
                                            placeholder="Ex: 120"
                                            className="w-full bg-white dark:bg-black/40 border border-gray-300 dark:border-white/10 rounded-lg py-3 px-4 text-gray-900 dark:text-white focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500 transition-colors"
                                        />
                                    </div>
                                </div>

                                {/* Platforms */}
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                        <Play className="mr-2 text-purple-400" size={20} />
                                        Vos Plateformes
                                    </h3>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        {AVAILABLE_PLATFORMS.map(platform => (
                                            <button
                                                key={platform}
                                                type="button"
                                                onClick={() => handleTogglePlatform(platform)}
                                                className={`py-3 px-4 rounded-lg border text-sm font-medium transition-all ${
                                                    platforms.includes(platform)
                                                        ? 'bg-purple-600 border-purple-500 text-white shadow-lg shadow-purple-900/20'
                                                        : 'bg-gray-50 dark:bg-black/20 border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-black dark:hover:text-white'
                                                }`}
                                            >
                                                {platform}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Genres */}
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                        <Film className="mr-2 text-purple-400" size={20} />
                                        Vos Genres Favoris
                                    </h3>
                                    <div className="flex flex-wrap gap-2">
                                        {AVAILABLE_GENRES.map(genre => (
                                            <button
                                                key={genre}
                                                type="button"
                                                onClick={() => handleToggleGenre(genre)}
                                                className={`py-2 px-4 rounded-full border text-sm transition-all ${
                                                    genres.includes(genre)
                                                        ? 'bg-gray-900 dark:bg-white text-white dark:text-black border-gray-900 dark:border-white font-semibold'
                                                        : 'bg-transparent border-black/20 dark:border-white/20 text-gray-600 dark:text-gray-400 hover:border-black/40 dark:hover:border-white/40 hover:text-black dark:hover:text-white'
                                                }`}
                                            >
                                                {genre}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-8 pt-6 border-t border-black/10 dark:border-white/10 flex justify-end">
                                <Button type="submit" className="flex items-center gap-2 px-8" disabled={loading}>
                                    <Save size={18} />
                                    {loading ? 'Enregistrement...' : 'Enregistrer les modifications'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Danger Zone */}
                <div className="mt-8 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/30 rounded-xl p-6 md:p-8">
                    <h3 className="text-xl font-bold text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
                        <AlertTriangle size={24} />
                        Zone Dangereuse
                    </h3>
                    <p className="text-gray-700 dark:text-gray-400 mb-6 max-w-2xl">
                        La suppression de votre compte entraînera la perte définitive de votre accès. 
                        Vos données seront conservées en base (suppression logique) mais vous ne pourrez plus vous connecter.
                    </p>
                    <button
                        onClick={() => setIsDeleteModalOpen(true)}
                        className="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors border border-red-500 shadow-lg shadow-red-900/20"
                    >
                        Supprimer mon compte
                    </button>
                </div>
            </div>

            <ConfirmModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteAccount}
                title="Suppression du compte"
                message="Êtes-vous sûr de vouloir supprimer votre compte ? Cette action vous empêchera de vous reconnecter."
                confirmText="Oui, supprimer mon compte"
                cancelText="Annuler"
                isDanger={true}
            />
        </div>
    );
};

export default Profile;
