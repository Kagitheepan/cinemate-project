import MovieCard from './MovieCard';
import { type Movie } from '../context/MovieContext';

interface MovieGridProps {
    movies?: Movie[];
}

const MovieGrid = ({ movies = [] }: MovieGridProps) => {
    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 gap-8">
            {movies.map((movie, index) => (
                <MovieCard
                    key={movie.id}
                    id={movie.id}
                    title={movie.title}
                    description={movie.description}
                    year={String(movie.year)}
                    rating={movie.rating}
                    imageUrl={movie.imageUrl}
                    priority={index < 3}
                />
            ))}
        </div>
    );
};

export default MovieGrid;
