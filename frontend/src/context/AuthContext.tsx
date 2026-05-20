import { createContext, useContext, useState, useEffect, type ReactNode } from 'react';
import api from '../services/api';

export interface CalendarEvent {
    id: string;
    movieId: string;
    title: string;
    start: string; // ISO string from backend
    end: string;   // ISO string from backend
}

export interface User {
    username: string;
    email: string;
    platforms: string[];
    favoriteGenres: string[];
    averageTimeAvailable: number | null;
    watchlist: string[]; // Array of Movie IDs
    agenda: CalendarEvent[];
}

interface AuthContextType {
    user: User | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    login: (userData: User) => void;
    logout: () => Promise<void>;
    updateUser: (userData: Partial<User>) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const initAuth = async () => {
            try {
                const response = await api.get('/profile');
                setUser(response.data);
            } catch (error) {
                setUser(null);
            }
            setIsLoading(false);
        };

        initAuth();
    }, []);

    const login = (userData: User) => {
        setUser(userData);
    };

    const logout = async () => {
        try {
            await api.post('/logout');
        } catch (error) {
            console.error('Failed to clear auth cookie:', error);
        }
        setUser(null);
    };

    const updateUser = (userData: Partial<User>) => {
        if (user) {
            setUser({ ...user, ...userData });
        }
    };

    return (
        <AuthContext.Provider value={{
            user,
            isAuthenticated: !!user,
            isLoading,
            login,
            logout,
            updateUser
        }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
