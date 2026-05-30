<?php

namespace App\Tests\Document;

use App\Document\ConnectionLog;
use PHPUnit\Framework\TestCase;

class ConnectionLogTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $log = new ConnectionLog();
        $date = new \DateTime();

        $log->setUsername('testuser');
        $log->setConnectedAt($date);

        self::assertSame('testuser', $log->getUsername());
        self::assertSame($date, $log->getConnectedAt());
        self::assertNull($log->getId());
    }
}
