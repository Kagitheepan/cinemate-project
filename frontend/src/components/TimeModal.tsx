import { useState } from 'react';
import { X, Clock } from 'lucide-react';
import Button from './ui/Button';

interface TimeModalProps {
    onConfirm: (totalMinutes: number) => void;
    isOpen: boolean;
    onClose: () => void;
}

const TimeModal = ({ onConfirm, isOpen, onClose }: TimeModalProps) => {
    const [hours, setHours] = useState(2);
    const [minutes, setMinutes] = useState(0);

    if (!isOpen) return null;

    const handleConfirm = () => {
        const totalMinutes = (hours * 60) + minutes;
        onConfirm(totalMinutes);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white/80 dark:bg-black/80 backdrop-blur-sm transition-opacity duration-300">
            <div className="bg-white dark:bg-neutral-900 border border-black/10 dark:border-white/10 rounded-2xl w-full max-w-md p-6 shadow-2xl relative animate-in fade-in zoom-in duration-300">
                <button 
                    onClick={onClose}
                    className="absolute top-4 right-4 text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors"
                >
                    <X size={20} />
                </button>

                <div className="text-center mb-8">
                    <div className="mx-auto w-16 h-16 bg-purple-500/10 rounded-full flex items-center justify-center mb-4 text-purple-400 border border-purple-500/20">
                        <Clock size={32} />
                    </div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Combien de temps avez-vous ?</h2>
                    <p className="text-gray-600 dark:text-gray-400">Nous filtrerons les films pour qu'ils rentrent dans votre soirée.</p>
                </div>

                <div className="flex items-center justify-center gap-4 mb-8">
                    <div className="flex flex-col items-center">
                        <label className="text-xs text-gray-500 uppercase tracking-wider mb-2 font-medium">Heures</label>
                        <input
                            type="number"
                            min="0"
                            max="12"
                            value={hours}
                            onChange={(e) => setHours(Math.max(0, parseInt(e.target.value) || 0))}
                            className="w-20 h-20 text-center text-4xl font-bold bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-xl text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 outline-none transition-all"
                        />
                    </div>
                    <span className="text-2xl font-bold text-gray-600 mt-6">:</span>
                    <div className="flex flex-col items-center">
                        <label className="text-xs text-gray-500 uppercase tracking-wider mb-2 font-medium">Minutes</label>
                        <input
                            type="number"
                            min="0"
                            max="59"
                            step="15"
                            value={minutes}
                            onChange={(e) => setMinutes(Math.max(0, Math.min(59, parseInt(e.target.value) || 0)))}
                            className="w-20 h-20 text-center text-4xl font-bold bg-gray-50 dark:bg-neutral-800 border border-gray-300 dark:border-white/10 rounded-xl text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 outline-none transition-all"
                        />
                    </div>
                </div>

                <div className="flex gap-3">
                    <Button 
                        variant="secondary" 
                        onClick={onClose}
                        className="flex-1"
                    >
                        Ignorer
                    </Button>
                    <Button 
                        onClick={handleConfirm}
                        className="flex-1"
                    >
                        Valider
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default TimeModal;
