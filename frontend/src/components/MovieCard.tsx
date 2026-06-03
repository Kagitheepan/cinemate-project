import { PlayCircle, Star, Calendar } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useMovies } from '../context/MovieContext';

interface MovieCardProps {
    id: string; // Add ID for routing
    title: string;
    description: string;
    imageUrl?: string;
    rating?: number;
    year?: string;
    priority?: boolean;
}

const MovieCard = ({ id, title, description, imageUrl, rating = 7.5, year = "2024", priority = false }: MovieCardProps) => {
    const { fetchMovieDetails } = useMovies();

    const handlePrefetch = () => {
        // Start fetching full details in the background when hovering
        fetchMovieDetails(id);
    };

    return (
        <Link 
            to={`/movie/${id}`} 
            onMouseEnter={handlePrefetch}
            className="block group relative rounded-xl h-[450px] w-full overflow-hidden transition duration-300 transform hover:scale-[1.03] hover:shadow-2xl hover:shadow-purple-500/20 bg-white dark:bg-neutral-900 border border-black/5 dark:border-white/5"
        >
            {/* Image / Background */}
            <div className="absolute inset-0 w-full h-full bg-gray-100 dark:bg-neutral-800">
                {imageUrl ? (
                    <img
                        src={imageUrl}
                        alt={title}
                        {...(priority ? { fetchPriority: "high" } : { loading: "lazy" })}
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />
                ) : (
                    <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-neutral-800 dark:to-neutral-700 text-gray-500 dark:text-neutral-500">
                        <PlayCircle className="w-16 h-16 mb-4 opacity-50" />
                        <span className="text-sm tracking-wider uppercase opacity-75 text-gray-600 dark:text-gray-400">No Poster</span>
                    </div>
                )}
            </div>

            {/* Gradient Overlay */}
            <div className="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent opacity-90 transition-opacity duration-300 group-hover:opacity-100" />

            {/* Content */}
            <div className="absolute bottom-0 left-0 w-full p-6 translate-y-2 transition-transform duration-300 group-hover:translate-y-0">
                <h3 className="text-xl font-bold text-white mb-2 line-clamp-1 group-hover:text-purple-400 transition-colors">
                    {title}
                </h3>
                
                <div className="flex items-center space-x-6 mb-3 text-sm text-gray-300">
                    <div className="flex flex-col">
                        <div className="flex items-center space-x-1">
                            <Star className="w-4 h-4 text-yellow-500 fill-current" />
                            <span>{rating}</span>
                        </div>
                        <span className="text-[10px] text-gray-500 uppercase font-semibold tracking-wider mt-0.5">Note IMDB</span>
                    </div>
                    <div className="flex flex-col">
                        <div className="flex items-center space-x-1">
                            <Calendar className="w-4 h-4 text-gray-400" />
                            <span>{year}</span>
                        </div>
                        <span className="text-[10px] text-gray-500 uppercase font-semibold tracking-wider mt-0.5">Année</span>
                    </div>
                </div>

                <p className="text-gray-400 text-sm line-clamp-2 mb-4 opacity-0 group-hover:opacity-100 transition-opacity duration delay-100">
                    {description}
                </p>

                <div className="w-full py-2 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/10 rounded-lg text-white font-medium text-sm transition-colors duration-200 text-center">
                    Voir détails
                </div>
            </div>
        </Link>
    );
};

export default MovieCard;
