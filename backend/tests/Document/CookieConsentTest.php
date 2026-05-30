<?php

namespace App\Tests\Document;

use App\Document\CookieConsent;
use PHPUnit\Framework\TestCase;

class CookieConsentTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $consent = new CookieConsent();
        $date = new \DateTime();

        $consent->setDecidedAt($date);

        self::assertNull($consent->getId());
        self::assertSame($date, $consent->getDecidedAt());
    }
}
