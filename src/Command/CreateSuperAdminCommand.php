<?php

namespace App\Command;

use App\Entity\User;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Create a new super admin user',
)]
class CreateSuperAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserManager $userManager;

    public function __construct(EntityManagerInterface $entityManager, UserManager $userManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('firstName', InputArgument::REQUIRED, 'First name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Last name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        // Create the user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);
        $user->setIsVerified(true);
        $user->setIsApproved(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $user->setApprovedAt(new \DateTimeImmutable());
        $user->setStreet('Admin Street');
        $user->setHouseNumber('1');
        $user->setPostalCode('1000');
        $user->setCity('Admin City');
        $user->setCountry('BE');
        $user->setPhoneNumber('+3200000000');
        $user->setLocale('fr');

        $this->userManager->createUser($user, $password);

        $io->success(sprintf('Super admin user "%s" created successfully.', $email));

        return Command::SUCCESS;
    }
}
