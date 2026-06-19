<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    /**
     * Retrieves movies with all necessary relationships to avoid N+1 queries.
     */
    public function findMoviesWithDetails(int $limit = 200): array
    {
        $query = $this->createQueryBuilder('m')
            ->leftJoin('m.genres', 'g')
            ->addSelect('g')
            ->leftJoin('m.movieCastings', 'mc')
            ->addSelect('mc')
            ->leftJoin('mc.casting', 'c')
            ->addSelect('c')
            ->orderBy('m.releaseDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        // Paginator is required when limiting results with fetch-joined collections
        $paginator = new Paginator($query, true);

        $movies = [];
        foreach ($paginator as $movie) {
            $movies[] = $movie;
        }

        return $movies;
    }
}
