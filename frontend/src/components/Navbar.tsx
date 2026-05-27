import { useState } from 'react';
import { Menu, X, User as UserIcon, LogOut, Sun, Moon } from 'lucide-react';
import { Link } from 'react-router-dom';
import AuthModal from './AuthModal';
import ConfirmModal from './ConfirmModal';
import { useAuth } from '../context/AuthContext';
import { useTheme } from '../context/ThemeContext';

import NotificationBell from './NotificationBell';

const Navbar = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
    const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false);
    const { isAuthenticated, user, logout } = useAuth();
    const { theme, toggleTheme } = useTheme();

    const toggleMenu = () => setIsOpen(!isOpen);

    interface NavLink {
        name: string;
        href?: string;
        action?: () => void;
        icon?: React.ReactNode;
        variant?: 'default' | 'danger';
    }

    const navLinks: NavLink[] = [
        { name: 'Accueil', href: '/' },
        { name: 'Ma Watchlist', href: '/watchlist' },
        { name: 'Agenda', href: '/agenda' },
    ];

    if (!isAuthenticated) {
        navLinks.push({ name: 'Inscription/Connexion', action: () => setIsAuthModalOpen(true) });
    } else {
        navLinks.splice(2, 0, { name: 'Pour Vous', href: '/recommendations' }); // Insère "Pour Vous" après "Ma Watchlist"
        navLinks.push({ name: 'Profil', href: '/profile' });
    }

    return (
        <>
            <nav className="fixed top-0 left-0 w-full z-50 bg-white/80 dark:bg-black/60 backdrop-blur-md border-b border-black/10 dark:border-white/10 transition-colors duration-300">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        {/* Logo/Brand */}
                        <Link to="/" className="flex-shrink-0 cursor-pointer">
                            <span className="text-2xl font-bold bg-gradient-to-r from-purple-400 to-pink-600 bg-clip-text text-transparent">
                                Cinemate
                            </span>
                        </Link>

                        {/* Desktop Menu */}
                        <div className="hidden md:block">
                            <div className="ml-10 flex items-center space-x-4">
                                {navLinks.map((link) => (
                                    link.href ? (
                                        <Link
                                            key={link.name}
                                            to={link.href}
                                            className="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                                        >
                                            {link.name}
                                        </Link>
                                    ) : (
                                        <button
                                            key={link.name}
                                            onClick={() => {
                                                link.action?.();
                                                setIsOpen(false);
                                            }}
                                            className="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 cursor-pointer"
                                        >
                                            {link.name}
                                        </button>
                                    )
                                ))}

                                {isAuthenticated && user && (
                                    <div className="flex items-center gap-4 ml-4 pl-4 border-l border-black/10 dark:border-white/10">
                                        <NotificationBell />
                                        <div className="flex items-center gap-2 text-purple-600 dark:text-purple-400 border-l border-black/10 dark:border-white/10 pl-4">
                                            <UserIcon size={18} />
                                            <span className="text-sm font-medium">{user.username}</span>
                                        </div>
                                        <button
                                            onClick={() => setIsLogoutModalOpen(true)}
                                            className="text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 p-2 rounded-md transition-colors"
                                            title="Se déconnecter"
                                        >
                                            <LogOut size={18} />
                                        </button>
                                    </div>
                                )}
                                
                                <button
                                    onClick={toggleTheme}
                                    className="p-2 ml-4 text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 rounded-md transition-colors"
                                    aria-label="Basculer le thème"
                                >
                                    {theme === 'dark' ? <Sun size={20} /> : <Moon size={20} />}
                                </button>
                            </div>
                        </div>

                        {/* Mobile menu button */}
                        <div className="-mr-2 flex md:hidden">
                            <button
                                onClick={toggleMenu}
                                className="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-800 focus:ring-black dark:focus:ring-white transition-colors"
                            >
                                <span className="sr-only">Open main menu</span>
                                {isOpen ? <X size={24} /> : <Menu size={24} />}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile Menu Open State */}
                {isOpen && (
                    <div className="md:hidden bg-white/95 dark:bg-black/95 backdrop-blur-xl absolute w-full border-b border-black/10 dark:border-white/10 h-screen z-40 transition-colors duration-300">
                        <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                            {navLinks.map((link) => (
                                link.href ? (
                                    <Link
                                        key={link.name}
                                        to={link.href}
                                        className="text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 block px-3 py-2 rounded-md text-base font-medium transition-colors"
                                        onClick={() => setIsOpen(false)}
                                    >
                                        {link.name}
                                    </Link>
                                ) : (
                                    <button
                                        key={link.name}
                                        onClick={() => {
                                            link.action?.();
                                            setIsOpen(false);
                                        }}
                                        className="text-left w-full text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 block px-3 py-2 rounded-md text-base font-medium transition-colors"
                                    >
                                        {link.name}
                                    </button>
                                )
                            ))}

                            {isAuthenticated && user && (
                                <div className="border-t border-black/10 dark:border-white/10 pt-4 mt-4">
                                    <div className="px-3 flex items-center gap-3 text-purple-600 dark:text-purple-400 mb-3">
                                        <UserIcon size={20} />
                                        <span className="font-medium">{user.username}</span>
                                    </div>
                                    <button
                                        onClick={() => {
                                            setIsOpen(false);
                                            setIsLogoutModalOpen(true);
                                        }}
                                        className="w-full text-left text-red-500 dark:text-red-400 hover:bg-black/5 dark:hover:bg-white/10 block px-3 py-2 rounded-md text-base font-medium flex items-center gap-2 transition-colors"
                                    >
                                        <LogOut size={18} />
                                        Se déconnecter
                                    </button>
                                </div>
                            )}
                            
                            <div className="border-t border-black/10 dark:border-white/10 pt-4 mt-4 px-3 flex items-center justify-between">
                                <span className="text-gray-600 dark:text-gray-300 font-medium">Thème</span>
                                <button
                                    onClick={toggleTheme}
                                    className="p-2 text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 rounded-md bg-black/5 dark:bg-white/5 transition-colors"
                                >
                                    {theme === 'dark' ? <Sun size={20} /> : <Moon size={20} />}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </nav>

            <AuthModal isOpen={isAuthModalOpen} onClose={() => setIsAuthModalOpen(false)} />
            
            <ConfirmModal
                isOpen={isLogoutModalOpen}
                onClose={() => setIsLogoutModalOpen(false)}
                onConfirm={logout}
                title="Déconnexion"
                message="Êtes-vous sûr de vouloir vous déconnecter de votre compte ?"
                confirmText="Se déconnecter"
            />
        </>
    );
};

export default Navbar;
