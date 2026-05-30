<?php

namespace App\Tests\Entity;

use App\Entity\Genre;
use PHPUnit\Framework\TestCase;

class GenreTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $genre = new Genre();
        $genre->setGenreName('Action');

        self::assertNull($genre->getId());
        self::assertSame('Action', $genre->getGenreName());
    }
}
