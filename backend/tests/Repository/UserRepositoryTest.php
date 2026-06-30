<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;
    private User $testUser;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(User::class);
        $this->testUser = $this->repository->findOneBy(['username' => 'testuser']);
    }

    public function testUpgradePassword(): void
    {
        $newHash = 'new_hashed_password_123';
        
        $this->repository->upgradePassword($this->testUser, $newHash);
        
        $this->entityManager->clear();
        
        $reloadedUser = $this->repository->find($this->testUser->getId());
        $this->assertSame($newHash, $reloadedUser->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $unsupportedUser = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return null; }
        };

        $this->expectException(UnsupportedUserException::class);
        $this->repository->upgradePassword($unsupportedUser, 'hash');
    }
}
