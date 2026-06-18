<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Platform;
use App\Entity\Genre;
use App\Entity\Movie;
use App\Entity\UserWatchlist;
use App\Entity\UserAgenda;
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

        // Format platforms
        $platforms = [];
        foreach ($user->getPlatforms() as $platform) {
            $platforms[] = $platform->getPlatformName();
        }

        // Format favorite genres
        $favoriteGenres = [];
        foreach ($user->getFavoriteGenres() as $genre) {
            $favoriteGenres[] = $genre->getGenreName();
        }

        $watchlist = ['toWatch' => [], 'watched' => []];
        foreach ($user->getWatchlists() as $userWatchlist) {
            $movieId = (string) $userWatchlist->getMovie()->getId();
            if ($userWatchlist->getStatut() === 'vu') {
                $watchlist['watched'][] = $movieId;
            } else {
                $watchlist['toWatch'][] = $movieId;
            }
        }

        // Format agenda
        $agenda = [];
        foreach ($user->getAgendas() as $userAgenda) {
            $start = new \DateTime($userAgenda->getEventDate()->format('c'));
            $end = (clone $start)->modify('+2 hours');

            $agenda[] = [
                'id' => uniqid(),
                'movieId' => (string) $userAgenda->getMovie()->getId(),
                'title' => $userAgenda->getMovie()->getTitle(),
                'start' => $start->format('c'),
                'end' => $end->format('c'),
                // Conservation pour compatibilité
                'date' => $start->format('Y-m-d'),
                'timeSlot' => $userAgenda->getTimeSlot()
            ];
        }

        return $this->json([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'platforms' => $platforms,
            'favoriteGenres' => $favoriteGenres,
            'watchlist' => $watchlist,
            'agenda' => $agenda,
        ]);
    }

    #[Route('', name: 'api_profile_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['platforms'])) {
            // Clear existing
            foreach ($user->getPlatforms() as $p) {
                $user->removePlatform($p);
            }
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

        if (isset($data['favoriteGenres'])) {
            // Clear existing
            foreach ($user->getFavoriteGenres() as $g) {
                $user->removeFavoriteGenre($g);
            }
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

        if (isset($data['watchlist'])) {
            $existingWatchlists = $user->getWatchlists();
            
            // Build an array of desired state: movieId => status
            $desiredState = [];
            if (is_array($data['watchlist']) && !isset($data['watchlist']['toWatch']) && !isset($data['watchlist']['watched'])) {
                foreach ($data['watchlist'] as $movieId) {
                    $desiredState[(int)$movieId] = 'a_voir';
                }
            } else {
                if (isset($data['watchlist']['toWatch'])) {
                    foreach ($data['watchlist']['toWatch'] as $movieId) {
                        $desiredState[(int)$movieId] = 'a_voir';
                    }
                }
                if (isset($data['watchlist']['watched'])) {
                    foreach ($data['watchlist']['watched'] as $movieId) {
                        $desiredState[(int)$movieId] = 'vu';
                    }
                }
            }

            // Update existing or remove them if not in desired state
            foreach ($existingWatchlists as $existing) {
                $movieId = $existing->getMovie()->getId();
                if (isset($desiredState[$movieId])) {
                    // Update status if it changed
                    $existing->setStatut($desiredState[$movieId]);
                    // Remove from desiredState so we only have NEW ones left to insert
                    unset($desiredState[$movieId]);
                } else {
                    // Not in the new state, remove it
                    $user->removeWatchlistRelation($existing);
                    $entityManager->remove($existing);
                }
            }

            // Add remaining desired states as new Watchlist records
            foreach ($desiredState as $movieId => $status) {
                $movie = $entityManager->getRepository(Movie::class)->find($movieId);
                if ($movie) {
                    $uw = new UserWatchlist();
                    $uw->setUser($user);
                    $uw->setMovie($movie);
                    $uw->setStatut($status);
                    $entityManager->persist($uw);
                    $user->addWatchlistRelation($uw);
                }
            }
        }

        if (isset($data['agenda'])) {
            $existingAgendas = $user->getAgendas();
            $newMovieIds = [];

            foreach ($data['agenda'] as $agendaItem) {
                if (!isset($agendaItem['movieId'])) continue;
                
                $movieId = (int)$agendaItem['movieId'];
                $newMovieIds[] = $movieId;

                // Chercher si cet agenda existe déjà
                $existing = null;
                foreach ($existingAgendas as $a) {
                    if ($a->getMovie()->getId() === $movieId) {
                        $existing = $a;
                        break;
                    }
                }

                $eventDate = new \DateTime();
                if (isset($agendaItem['start'])) {
                    $eventDate = new \DateTime($agendaItem['start']);
                } elseif (isset($agendaItem['date'])) {
                    $eventDate = new \DateTime($agendaItem['date']);
                }

                if ($existing) {
                    // Mettre à jour l'existant
                    $existing->setEventDate($eventDate);
                    if (isset($agendaItem['timeSlot'])) {
                        $existing->setTimeSlot($agendaItem['timeSlot']);
                    }
                } else {
                    // Créer un nouveau
                    $movie = $entityManager->getRepository(Movie::class)->find($movieId);
                    if ($movie) {
                        $ua = new UserAgenda();
                        $ua->setUser($user);
                        $ua->setMovie($movie);
                        $ua->setEventDate($eventDate);
                        if (isset($agendaItem['timeSlot'])) {
                            $ua->setTimeSlot($agendaItem['timeSlot']);
                        }
                        $entityManager->persist($ua);
                        $user->addAgenda($ua);
                    }
                }
            }

            // Supprimer ceux qui ne sont plus dans la liste
            foreach ($existingAgendas as $a) {
                if (!in_array($a->getMovie()->getId(), $newMovieIds)) {
                    $user->removeAgenda($a);
                    $entityManager->remove($a);
                }
            }
        }
        
        if (isset($data['email'])) {
             $user->setEmail($data['email']);
        }

        $entityManager->flush();

        return $this->show(); // Re-use the show method to return the formatted data
    }

    #[Route('', name: 'api_profile_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Account deleted successfully']);
    }
}
