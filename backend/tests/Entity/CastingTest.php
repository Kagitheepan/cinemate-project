<?php

namespace App\Tests\Entity;

use App\Entity\Casting;
use PHPUnit\Framework\TestCase;

class CastingTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $casting = new Casting();
        
        self::assertNull($casting->getId());
        
        $casting->setName('Leonardo DiCaprio');
        self::assertSame('Leonardo DiCaprio', $casting->getName());
        
        $casting->setProfilePath('/path/to/pic.jpg');
        self::assertSame('/path/to/pic.jpg', $casting->getProfilePath());
        
        $casting->setProfilePath(null);
        self::assertNull($casting->getProfilePath());
    }
}
