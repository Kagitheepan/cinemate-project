<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends TestCase
{
    public function testUpgradePasswordSuccess(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(User::class));
        $registry->method('getManagerForClass')->willReturn($em);
        
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $repo = new UserRepository($registry);
        
        $user = new User();
        $repo->upgradePassword($user, 'new_hashed_password');
        
        self::assertSame('new_hashed_password', $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionOnInvalidUser(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(User::class));
        $registry->method('getManagerForClass')->willReturn($em);
        
        $repo = new UserRepository($registry);
        
        $invalidUser = $this->createMock(PasswordAuthenticatedUserInterface::class);
        
        $this->expectException(UnsupportedUserException::class);
        $repo->upgradePassword($invalidUser, 'new_hashed_password');
    }
}
