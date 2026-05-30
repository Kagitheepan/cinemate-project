<?php

namespace App\Tests\Repository;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class MovieRepositoryTest extends TestCase
{
    public function testConstructor(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(Movie::class));
        $registry->method('getManagerForClass')->willReturn($em);

        $repository = new MovieRepository($registry);
        self::assertInstanceOf(MovieRepository::class, $repository);
    }
}
