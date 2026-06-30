<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user with sample data',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        // Check if user exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            $io->error(sprintf('User "%s" already exists.', $username));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        
        // Hash the password
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $password
            )
        );

        // Add dummy platforms and genres
        // In a real app we would fetch the actual Platform/Genre entities, but for this mock command we skip
        // or we need to fetch them from DB.
        $platformRepo = $this->entityManager->getRepository(\App\Entity\Platform::class);
        foreach (['Netflix', 'Amazon Prime', 'Disney+'] as $pName) {
            $p = $platformRepo->findOneBy(['platformName' => $pName]);
            if ($p) $user->addPlatform($p);
        }

        $genreRepo = $this->entityManager->getRepository(\App\Entity\Genre::class);
        foreach (['Action', 'Sci-Fi', 'Comedy'] as $gName) {
            $g = $genreRepo->findOneBy(['genreName' => $gName]);
            if ($g) $user->addFavoriteGenre($g);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User "%s" successfully created with sample platforms and genres!', $username));

        return Command::SUCCESS;
    }
}
