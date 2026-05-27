<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'api_profile_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        return $this->json([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'platforms' => $user->getPlatforms(),
            'favoriteGenres' => $user->getFavoriteGenres(),
            'watchlist' => $user->getWatchlist(),
            'agenda' => $user->getAgenda(),
        ]);
    }

    #[Route('', name: 'api_profile_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['platforms'])) {
            $user->setPlatforms($data['platforms']);
        }

        if (isset($data['favoriteGenres'])) {
            $user->setFavoriteGenres($data['favoriteGenres']);
        }


        
        if (isset($data['watchlist'])) {
             $user->setWatchlist($data['watchlist']);
        }

        if (isset($data['agenda'])) {
             $user->setAgenda($data['agenda']);
        }
        
        // Optional: Update email/username if needed
        if (isset($data['email'])) {
             $user->setEmail($data['email']);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'platforms' => $user->getPlatforms(),
                'favoriteGenres' => $user->getFavoriteGenres(),
                'watchlist' => $user->getWatchlist(),
                'agenda' => $user->getAgenda(),
            ]
        ]);
    }

    #[Route('', name: 'api_profile_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        // Create a DeletedUser record
        $deletedUser = new \App\Entity\DeletedUser();
        $deletedUser->setOriginalId($user->getId());
        $deletedUser->setUsername($user->getUsername());
        $deletedUser->setEmail($user->getEmail());
        $deletedUser->setDeletedAt(new \DateTime());

        // We could also store more data if we added those columns to DeletedUser,
        // but for now we'll stick to the requested basic info.

        $entityManager->persist($deletedUser);
        
        // Hard delete the original User
        $entityManager->remove($user);
        
        $entityManager->flush();

        return $this->json(['message' => 'Account deleted successfully']);
    }
}
