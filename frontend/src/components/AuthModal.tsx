import { useState, type FormEvent } from 'react';
import { X, Mail, Lock, User, Eye, EyeOff, AlertCircle } from 'lucide-react';
import Button from './ui/Button';
import { useAuth } from '../context/AuthContext';
import api from '../services/api';

interface AuthModalProps {
    isOpen: boolean;
    onClose: () => void;
}

const AuthModal = ({ isOpen, onClose }: AuthModalProps) => {
    const { login } = useAuth();
    const [isLogin, setIsLogin] = useState(true);
    const [showPassword, setShowPassword] = useState(false);
    
    const [username, setUsername] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    if (!isOpen) return null;

    const toggleMode = () => {
        setIsLogin(!isLogin);
        setError(null);
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            if (isLogin) {
                // Login Flow
                const response = await api.post('/login_check', {
                    username,
                    password
                });
                
                const { token } = response.data;
                
                // Fetch user profile with the new token
                // We need to manually set the header here for the immediate request if interceptor relies on localStorage which isn't set yet in context
                // But context login function sets localStorage. 
                // However, avoiding race conditions, let's just use the token in header explicitly or rely on interceptor if we set localStorage first.
                // For safety, let's set it in localStorage before fetching profile if we rely on interceptor, or pass headers.
                
                localStorage.setItem('token', token); // Temporarily set for the next request
                
                const profileResponse = await api.get('/profile');
                login(token, profileResponse.data);
                onClose();
            } else {
                // Register Flow
                await api.post('/register', {
                    username,
                    email,
                    password
                });
                
                // Auto-login after register
                const loginResponse = await api.post('/login_check', {
                    username,
                    password
                });
                
                const { token } = loginResponse.data;
                localStorage.setItem('token', token);
                
                const profileResponse = await api.get('/profile');
                login(token, profileResponse.data);
                onClose();
            }
        } catch (err: any) { // using any for simplicity with axios error handling
            console.error(err);
            if (err.response) {
                if (err.response.status === 401) {
                    setError('Identifiants incorrects.');
                } else if (err.response.data && err.response.data.message) {
                    setError(err.response.data.message);
                } else {
                    setError('Une erreur est survenue.');
                }
            } else {
                setError('Erreur de connexion au serveur.');
            }
            localStorage.removeItem('token'); // Cleanup if failed mid-way
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-in fade-in duration-300">
            <div className="bg-neutral-900 border border-white/10 rounded-2xl w-full max-w-md p-8 relative shadow-2xl shadow-purple-500/20 transform scale-100 transition-transform duration-300">
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors p-1"
                >
                    <X size={20} />
                </button>

                <div className="text-center mb-8">
                    <h2 className="text-3xl font-bold bg-gradient-to-r from-purple-400 to-pink-600 bg-clip-text text-transparent mb-2">
                        {isLogin ? 'Connexion' : 'Inscription'}
                    </h2>
                    <p className="text-gray-400 text-sm">
                        {isLogin
                            ? 'Heureux de vous revoir sur Cinemate !'
                            : 'Rejoignez la communauté Cinemate aujourd\'hui.'}
                    </p>
                </div>

                {error && (
                    <div className="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg flex items-center gap-2 text-red-200 text-sm">
                        <AlertCircle size={16} />
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="relative group">
                        <User className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-5 w-5 group-focus-within:text-purple-400 transition-colors" />
                        <input
                            type="text"
                            placeholder="Nom d'utilisateur"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            className="w-full bg-black/20 border border-white/10 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all"
                            required
                        />
                    </div>
                    
                    {!isLogin && (
                        <div className="relative group animate-in fade-in slide-in-from-top-2">
                            <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-5 w-5 group-focus-within:text-purple-400 transition-colors" />
                            <input
                                type="email"
                                placeholder="Email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="w-full bg-black/20 border border-white/10 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all"
                                required={!isLogin}
                            />
                        </div>
                    )}

                    <div className="relative group">
                        <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-5 w-5 group-focus-within:text-purple-400 transition-colors" />
                        <input
                            type={showPassword ? 'text' : 'password'}
                            placeholder="Mot de passe"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="w-full bg-black/20 border border-white/10 rounded-lg py-3 pl-10 pr-12 text-white placeholder-gray-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all"
                            required
                        />
                         <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white focus:outline-none"
                        >
                            {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                        </button>
                    </div>

                    <Button 
                        type="submit" 
                        fullWidth 
                        className="mt-6 py-3 text-lg font-semibold shadow-purple-500/30"
                        disabled={loading}
                    >
                        {loading ? 'Chargement...' : (isLogin ? 'Se connecter' : "S'inscrire")}
                    </Button>
                </form>

                <div className="mt-6 text-center text-sm text-gray-400">
                    {isLogin ? "Pas encore de compte ? " : "Déjà un compte ? "}
                    <button
                        onClick={toggleMode}
                        className="text-purple-400 hover:text-purple-300 font-medium underline underline-offset-2 decoration-transparent hover:decoration-purple-400 transition-all"
                    >
                        {isLogin ? "S'inscrire" : 'Se connecter'}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AuthModal;
