import { useState, useEffect } from 'react';
import { X, ShieldAlert, Check } from 'lucide-react';
import api from '../services/api';

const CookieBanner = () => {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        // Vérifier si l'utilisateur a déjà fait un choix
        const consent = localStorage.getItem('cookie_consent');
        if (!consent) {
            setIsVisible(true);
        } else {
            // Mettre à jour les headers d'API par défaut pour toutes les requêtes futures si consenti
            if (consent === 'accepted') {
                api.defaults.headers.common['X-Consent-Tracking'] = 'true';
            }
        }
    }, []);

    const saveConsent = async (choice: 'accepted' | 'refused') => {
        await api.post('/privacy/consent', { choice });
    };

    const handleAccept = async () => {
        try {
            await saveConsent('accepted');
        } catch (error) {
            console.error("Erreur lors de l'envoi du consentement:", error);
        }
        localStorage.setItem('cookie_consent', 'accepted');
        api.defaults.headers.common['X-Consent-Tracking'] = 'true';
        setIsVisible(false);
    };

    const handleRefuse = async () => {
        try {
            await saveConsent('refused');
        } catch (error) {
            console.error("Erreur lors de l'envoi du consentement:", error);
        }
        localStorage.setItem('cookie_consent', 'refused');
        delete api.defaults.headers.common['X-Consent-Tracking'];
        setIsVisible(false);
    };

    if (!isVisible) return null;

    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 p-4 sm:p-6 pb-safe pointer-events-none">
            <div className="max-w-4xl mx-auto bg-white/95 dark:bg-neutral-900/95 backdrop-blur-md border border-black/10 dark:border-white/10 rounded-2xl p-6 shadow-lg dark:shadow-2xl dark:shadow-black/50 pointer-events-auto transform transition-all translate-y-0 relative overflow-hidden">
                {/* Glow effect */}
                <div className="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-pink-500/10 pointer-events-none" />
                
                <div className="relative z-10 flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                    <div className="flex-shrink-0 bg-purple-500/20 p-3 rounded-xl border border-purple-500/30">
                        <ShieldAlert className="w-8 h-8 text-purple-400" />
                    </div>
                    
                    <div className="flex-grow">
                        <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Vos données, votre choix</h3>
                        <p className="text-gray-700 dark:text-gray-400 text-sm leading-relaxed">
                            Afin d'améliorer votre expérience et d'assurer le suivi de vos connexions (historique d'accès), 
                            nous utilisons des cookies et enregistrons certaines données dans notre base (MongoDB). 
                            En acceptant, vous nous aidez à sécuriser votre compte. Vous pouvez refuser, cela n'empêchera 
                            pas l'utilisation du site, mais vos logs de connexion ne seront pas sauvegardés.
                        </p>
                    </div>

                    <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-shrink-0">
                        <button 
                            onClick={handleRefuse}
                            className="px-5 py-2.5 rounded-lg border border-black/10 dark:border-white/10 hover:bg-black/5 dark:hover:bg-white/5 text-gray-700 dark:text-gray-300 text-sm font-medium transition-colors"
                        >
                            Continuer sans accepter
                        </button>
                        <button 
                            onClick={handleAccept}
                            className="px-5 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white text-sm font-bold shadow-lg shadow-purple-500/25 flex items-center justify-center gap-2 transition-all transform hover:scale-105 active:scale-95"
                        >
                            <Check size={18} />
                            Accepter tout
                        </button>
                    </div>
                </div>
                
                <button 
                    onClick={handleRefuse}
                    className="absolute top-4 right-4 text-gray-500 hover:text-black dark:hover:text-white transition-colors"
                    aria-label="Fermer"
                >
                    <X size={20} />
                </button>
            </div>
        </div>
    );
};

export default CookieBanner;
