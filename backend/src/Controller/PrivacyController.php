<?php

namespace App\Controller;

use App\Document\CookieConsent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/privacy')]
class PrivacyController extends AbstractController
{
    private const POLICY_VERSION = '2026-05-18';

    #[Route('/consent', name: 'api_privacy_consent', methods: ['POST'])]
    public function saveConsent(Request $request, DocumentManager $documentManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $choice = $data['choice'] ?? null;

        if (!in_array($choice, ['accepted', 'refused'], true)) {
            return $this->json(['message' => 'Invalid consent choice'], 400);
        }

        $consentId = $request->cookies->get('cinemate_consent_id') ?: bin2hex(random_bytes(16));
        $user = $this->getUser();

        $consent = (new CookieConsent())
            ->setConsentId($consentId)
            ->setChoice($choice)
            ->setUsername($user instanceof UserInterface ? $user->getUserIdentifier() : null)
            ->setDecidedAt(new \DateTimeImmutable())
            ->setPolicyVersion(self::POLICY_VERSION);

        $documentManager->persist($consent);
        $documentManager->flush();

        $response = $this->json([
            'choice' => $choice,
            'policyVersion' => self::POLICY_VERSION,
        ]);
        $response->headers->setCookie(Cookie::create('cinemate_consent_id')
            ->withValue($consentId)
            ->withExpires(strtotime('+13 months'))
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX));

        return $response;
    }
}
