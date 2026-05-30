<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Platform;
use App\Entity\Genre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            return $this->json(['message' => 'Missing fields'], 400);
        }

        // Check if user already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['message' => 'Email already used'], 409);
        }

        $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return $this->json(['message' => 'Username already taken'], 409);
        }

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        if (!empty($data['platforms'])) {
            foreach ($data['platforms'] as $platformName) {
                $platform = $entityManager->getRepository(Platform::class)->findOneBy(['platformName' => $platformName]);
                if (!$platform) {
                    $platform = new Platform();
                    $platform->setPlatformName($platformName);
                    $entityManager->persist($platform);
                }
                $user->addPlatform($platform);
            }
        }

        if (!empty($data['favoriteGenres'])) {
            foreach ($data['favoriteGenres'] as $genreName) {
                $genre = $entityManager->getRepository(Genre::class)->findOneBy(['genreName' => $genreName]);
                if (!$genre) {
                    $genre = new Genre();
                    $genre->setGenreName($genreName);
                    $entityManager->persist($genre);
                }
                $user->addFavoriteGenre($genre);
            }
        }

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'platforms' => $data['platforms'] ?? [],
            ]
        ], 201);
    }
}
