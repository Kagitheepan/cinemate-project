<?php

namespace App\Tests\Repository;

use App\Entity\Movie;
use App\Entity\Genre;
use App\Entity\Casting;
use App\Entity\MovieCasting;
use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class MovieRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private MovieRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Movie::class);
    }

    public function testFindMoviesWithDetails(): void
    {
        // Add a new movie with genre and casting
        $genre = new Genre();
        $genre->setGenreName('Repo Test Genre');
        $this->entityManager->persist($genre);

        $casting = new Casting();
        $casting->setName('Repo Test Actor');
        $this->entityManager->persist($casting);

        $movie = new Movie();
        $movie->setTitle('Repo Test Movie')
              ->setTmdbId(99993)
              ->addGenre($genre)
              ->setReleaseDate(new \DateTime('2030-01-01')); // Future release to be first

        $movieCasting = new MovieCasting();
        $movieCasting->setMovie($movie)
                     ->setCasting($casting)
                     ->setCharacterName('Lead Actor');
        $this->entityManager->persist($movieCasting);

        $this->entityManager->persist($movie);
        $this->entityManager->flush();
        $this->entityManager->clear(); // Detach to ensure we fetch from DB

        $movies = $this->repository->findMoviesWithDetails(1);
        
        $this->assertCount(1, $movies);
        
        /** @var Movie $fetchedMovie */
        $fetchedMovie = $movies[0];
        $this->assertSame('Repo Test Movie', $fetchedMovie->getTitle());
        $this->assertCount(1, $fetchedMovie->getGenres());
        $this->assertSame('Repo Test Genre', $fetchedMovie->getGenres()->first()->getGenreName());
        $this->assertCount(1, $fetchedMovie->getMovieCastings());
        $this->assertSame('Repo Test Actor', $fetchedMovie->getMovieCastings()->first()->getCasting()->getName());
    }
}
