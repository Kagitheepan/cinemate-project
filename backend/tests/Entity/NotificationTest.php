<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $notification = new Notification();
        
        self::assertNull($notification->getId());
        
        $notification->setEmailSent(true);
        self::assertTrue($notification->isEmailSent());
    }
}
