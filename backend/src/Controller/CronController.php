<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cron')]
class CronController extends AbstractController
{
    #[Route('/reminders', name: 'api_cron_reminders', methods: ['GET'])]
    public function sendReminders(Request $request, KernelInterface $kernel): JsonResponse
    {
        // On récupère le token secret envoyé dans l'URL (ex: ?token=mon_secret)
        $providedToken = $request->query->get('token');
        
        // On récupère le secret attendu depuis les variables d'environnement
        $expectedToken = $_ENV['CRON_SECRET'] ?? 'secret_par_defaut';

        // Vérification de la sécurité
        if (!$providedToken || $providedToken !== $expectedToken) {
            return $this->json(['error' => 'Non autorisé. Jeton invalide.'], 401);
        }

        // Exécution programmée de la commande Symfony
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'app:send-reminders',
        ]);

        $output = new BufferedOutput();
        
        try {
            // On exécute la commande et on capture la sortie
            $application->run($input, $output);
            
            return $this->json([
                'success' => true,
                'message' => 'Les rappels ont été traités avec succès.',
                'details' => $output->fetch()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors de l\'exécution de la commande.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
