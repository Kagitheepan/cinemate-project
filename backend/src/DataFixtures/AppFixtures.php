<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(
        private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new \App\Entity\User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
        $manager->persist($user);

        $genre = new \App\Entity\Genre();
        $genre->setGenreName('Science Fiction');
        $manager->persist($genre);

        $movie = new \App\Entity\Movie();
        $movie->setTitle('Test Movie');
        $movie->setTmdbId(101);
        $movie->setRating(9.5);
        $movie->setRuntime(120);
        $movie->setDirector('Test Director');
        $movie->addGenre($genre);
        $manager->persist($movie);

        $manager->flush();
    }
}
