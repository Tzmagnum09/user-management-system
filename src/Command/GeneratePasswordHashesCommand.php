<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordHashService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-password-hashes',
    description: 'Generate and store password hashes for all or specific users',
)]
class GeneratePasswordHashesCommand extends Command
{
    private UserRepository $userRepository;
    private PasswordHashService $passwordHashService;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        PasswordHashService $passwordHashService,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->passwordHashService = $passwordHashService;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Process only a specific user by email')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Process only a specific user by ID')
            ->addOption('plain-password', null, InputOption::VALUE_REQUIRED, 'Plain password to use for all users (ONLY FOR TESTING)')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Number of users to process per batch', 20)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getOption('email');
        $id = $input->getOption('id');
        $plainPassword = $input->getOption('plain-password');
        $batchSize = $input->getOption('batch-size');

        if ($email) {
            $users = $this->userRepository->findBy(['email' => $email]);
            if (count($users) === 0) {
                $io->error(sprintf('No user found with email: %s', $email));
                return Command::FAILURE;
            }
        } elseif ($id) {
            $user = $this->userRepository->find($id);
            if (!$user) {
                $io->error(sprintf('No user found with ID: %s', $id));
                return Command::FAILURE;
            }
            $users = [$user];
        } else {
            $users = $this->userRepository->findAll();
        }

        $totalUsers = count($users);
        if ($totalUsers === 0) {
            $io->warning('No users found to process.');
            return Command::SUCCESS;
        }

        $io->title('Generating Password Hashes');
        $io->progressStart($totalUsers);

        $processedCount = 0;
        $errorCount = 0;

        if ($plainPassword) {
            $io->caution('Using provided plain password for all users. This should ONLY be used for testing!');
        }

        // Process users in batches
        $batches = array_chunk($users, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $user) {
                try {
                    if ($plainPassword) {
                        // Use provided plain password (TESTING ONLY)
                        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                        $user->setPassword($hashedPassword);
                        $this->passwordHashService->storeAllPasswordHashes($user, $plainPassword);
                    } else {
                        // We cannot recover plain passwords, so we add a new entry without updating anything
                        // This is meant for users who already have their password stored in the database
                        // but don't have their hashes yet
                        if (!$user->getHashStorage()) {
                            $io->note(sprintf('User %s (%s) has no hash storage and no plain password provided. Skipping.', 
                                $user->getUsername(), $user->getEmail()));
                        }
                    }
                    
                    $processedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $io->error(sprintf('Error processing user %s: %s', $user->getEmail(), $e->getMessage()));
                }
                
                $io->progressAdvance();
            }
            
            // Flush the batch
            if ($plainPassword) {
                $this->entityManager->flush();
            }
            
            // Clear entity manager to avoid memory issues
            $this->entityManager->clear();
        }

        $io->progressFinish();
        
        if ($errorCount > 0) {
            $io->warning(sprintf('%d users were processed with %d errors.', $processedCount, $errorCount));
            return Command::FAILURE;
        }
        
        $io->success(sprintf('%d users were processed successfully.', $processedCount));
        return Command::SUCCESS;
    }
}