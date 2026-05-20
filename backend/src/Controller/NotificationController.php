<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(NotificationRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $notifications = $repo->findByUser($user->getId());
        $unreadCount = $repo->countUnreadByUser($user->getId());

        $data = [];
        foreach ($notifications as $notif) {
            $data[] = [
                'id' => $notif->getId(),
                'message' => $notif->getMessage(),
                'type' => $notif->getType(),
                'isRead' => $notif->isRead(),
                'movieId' => $notif->getMovieId(),
                'eventId' => $notif->getEventId(),
                'createdAt' => $notif->getCreatedAt()->format('c'),
                'eventDate' => $notif->getEventDate()?->format('c'),
            ];
        }

        return $this->json([
            'notifications' => $data,
            'unreadCount' => $unreadCount,
        ]);
    }

    #[Route('/read/{id}', name: 'api_notifications_read', methods: ['PATCH'])]
    public function markAsRead(int $id, NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $notif = $repo->find($id);
        if (!$notif || $notif->getUser()->getId() !== $user->getId()) {
            return $this->json(['message' => 'Notification introuvable'], 404);
        }

        $notif->setIsRead(true);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/read-all', name: 'api_notifications_read_all', methods: ['PATCH'])]
    public function markAllAsRead(NotificationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $unread = $repo->findUnreadByUser($user->getId());
        foreach ($unread as $notif) {
            $notif->setIsRead(true);
        }
        $em->flush();

        return $this->json(['success' => true, 'marked' => count($unread)]);
    }
}
