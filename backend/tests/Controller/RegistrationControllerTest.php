<?php

namespace App\Tests\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationControllerTest extends TestCase
{
    public function testRegisterRejectsMissingFields(): void
    {
        $controller = $this->createController();
        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'alice',
            'email' => 'alice@example.com',
        ]));

        $response = $controller->register(
            $request,
            $this->createMock(UserPasswordHasherInterface::class),
            $this->createMock(EntityManagerInterface::class)
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['message' => 'Missing fields'], json_decode($response->getContent(), true));
    }

    public function testRegisterRejectsAlreadyUsedEmail(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'alice@example.com'])
            ->willReturn(new User());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $response = $this->createController()->register(
            $this->createRegistrationRequest(),
            $this->createMock(UserPasswordHasherInterface::class),
            $entityManager
        );

        self::assertSame(409, $response->getStatusCode());
        self::assertSame(['message' => 'Email already used'], json_decode($response->getContent(), true));
    }

    public function testRegisterCreatesUserWhenPayloadIsValid(): void
    {
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $platformRepository = $this->createMock(EntityRepository::class);
        $platform = new \App\Entity\Platform();
        $platform->setPlatformName('Netflix');
        $platformRepository->method('findOneBy')->willReturn($platform);

        $genreRepository = $this->createMock(EntityRepository::class);
        $genre = new \App\Entity\Genre();
        $genre->setGenreName('Action');
        $genreRepository->method('findOneBy')->willReturn($genre);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($userRepository, $platformRepository, $genreRepository) {
                if ($entityClass === User::class) return $userRepository;
                if ($entityClass === \App\Entity\Platform::class) return $platformRepository;
                if ($entityClass === \App\Entity\Genre::class) return $genreRepository;
                return $userRepository;
            });

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (User $user): bool {
                return $user->getUsername() === 'alice'
                    && $user->getEmail() === 'alice@example.com'
                    && $user->getPassword() === 'hashed-password'
                    && $user->getPlatforms()->count() === 1
                    && $user->getPlatforms()->first()->getPlatformName() === 'Netflix'
                    && $user->getFavoriteGenres()->count() === 1
                    && $user->getFavoriteGenres()->first()->getGenreName() === 'Action';
            }));
        $entityManager->expects(self::once())->method('flush');

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with(self::isInstanceOf(User::class), 'plain-password')
            ->willReturn('hashed-password');

        $response = $this->createController()->register(
            $this->createRegistrationRequest(),
            $passwordHasher,
            $entityManager
        );

        self::assertSame(201, $response->getStatusCode());
        self::assertSame([
            'message' => 'User created successfully',
            'user' => [
                'username' => 'alice',
                'email' => 'alice@example.com',
                'platforms' => ['Netflix'],
            ],
        ], json_decode($response->getContent(), true));
    }

    private function createRegistrationRequest(): Request
    {
        return new Request([], [], [], [], [], [], json_encode([
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'plain-password',
            'platforms' => ['Netflix'],
            'favoriteGenres' => ['Action'],
        ]));
    }

    private function createController(): RegistrationController
    {
        $controller = new RegistrationController();
        $controller->setContainer(new Container());

        return $controller;
    }
}
