<?php

namespace App\Tests\Entity;

use App\Entity\Platform;
use PHPUnit\Framework\TestCase;

class PlatformTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $platform = new Platform();
        $platform->setPlatformName('Netflix');

        self::assertNull($platform->getId());
        self::assertSame('Netflix', $platform->getPlatformName());
    }
}
