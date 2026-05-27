import { Search } from 'lucide-react';

interface SearchBarProps {
    value: string;
    onChange: (value: string) => void;
}

const SearchBar = ({ value, onChange }: SearchBarProps) => {
    return (
        <div className="relative w-full max-w-4xl mx-auto my-12 px-4 sm:px-6 lg:px-8">
            <div className="relative group">
                <div className="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                    <Search className="h-6 w-6 text-gray-500 dark:text-gray-400 group-hover:text-purple-400 transition-colors duration-200" />
                </div>
                <input
                    type="text"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="block w-full pl-16 pr-4 py-4 border-2 border-black/10 dark:border-white/10 rounded-full bg-white dark:bg-white/5 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent backdrop-blur-sm transition-all duration-300 hover:bg-gray-50 dark:hover:bg-white/10 shadow-lg text-lg"
                    placeholder="Rechercher un film/série à visionner..."
                />
                <div className="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <kbd className="hidden sm:inline-block border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-transparent rounded px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 tracking-widest">
                        CTRL K
                    </kbd>
                </div>
            </div>
        </div>
    );
};

export default SearchBar;
