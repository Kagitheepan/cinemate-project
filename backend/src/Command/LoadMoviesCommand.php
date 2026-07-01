<?php

namespace App\Command;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-movies',
    description: 'Loads sample movies into MySQL',
)]
class LoadMoviesCommand extends Command
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Loading movies...');

        $moviesData = [
            [
                'tmdbId' => 27205,
                'title' => 'Inception',
                'description' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
                'releaseDate' => new \DateTime('2010-07-16'),
                'director' => 'Christopher Nolan',
                'rating' => 8.8,
                'genres' => ['Action', 'Adventure', 'Sci-Fi'],
                'actors' => ['Leonardo DiCaprio', 'Joseph Gordon-Levitt', 'Elliot Page']
            ],
            [
                'tmdbId' => 603,
                'title' => 'The Matrix',
                'description' => 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.',
                'releaseDate' => new \DateTime('1999-03-31'),
                'director' => 'Lana Wachowski, Lilly Wachowski',
                'rating' => 8.7,
                'genres' => ['Action', 'Sci-Fi'],
                'actors' => ['Keanu Reeves', 'Laurence Fishburne', 'Carrie-Anne Moss']
            ],
            [
                'tmdbId' => 157336,
                'title' => 'Interstellar',
                'description' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.',
                'releaseDate' => new \DateTime('2014-11-07'),
                'director' => 'Christopher Nolan',
                'rating' => 8.6,
                'genres' => ['Adventure', 'Drama', 'Sci-Fi'],
                'actors' => ['Matthew McConaughey', 'Anne Hathaway', 'Jessica Chastain']
            ]
        ];

        foreach ($moviesData as $data) {
            $movie = new Movie();
            $movie->setTmdbId($data['tmdbId']);
            $movie->setTitle($data['title']);
            $movie->setDescription($data['description']);
            $movie->setReleaseDate($data['releaseDate']);
            $movie->setDirector($data['director']);
            $movie->setRating($data['rating']);
            // Map genres
            foreach ($data['genres'] as $genreName) {
                $genre = $this->em->getRepository(\App\Entity\Genre::class)->findOneBy(['genreName' => $genreName]);
                if (!$genre) {
                    $genre = new \App\Entity\Genre();
                    $genre->setGenreName($genreName);
                    $this->em->persist($genre);
                }
                $movie->addGenre($genre);
            }
            
            $this->em->persist($movie);
        }

        $this->em->flush();

        $io->success('Movies loaded successfully into MySQL!');

        return Command::SUCCESS;
    }
}
