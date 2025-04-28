<?php

namespace App\Command;

use App\Repository\ResetPasswordRequestRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reset-password:cleanup',
    description: 'Clean up expired password reset requests',
)]
class ResetPasswordCleanupCommand extends Command
{
    private ResetPasswordRequestRepository $resetPasswordRequestRepository;

    public function __construct(ResetPasswordRequestRepository $resetPasswordRequestRepository)
    {
        parent::__construct();
        $this->resetPasswordRequestRepository = $resetPasswordRequestRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $removedRequests = $this->resetPasswordRequestRepository->removeExpiredRequests();
        
        $io->success(sprintf('Successfully removed %d expired reset password request(s).', $removedRequests));

        return Command::SUCCESS;
    }
}