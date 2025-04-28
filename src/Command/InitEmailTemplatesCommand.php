<?php

namespace App\Command;

use App\Service\EmailManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-email-templates',
    description: 'Initialize default email templates',
)]
class InitEmailTemplatesCommand extends Command
{
    private EmailManager $emailManager;

    public function __construct(EmailManager $emailManager)
    {
        parent::__construct();
        $this->emailManager = $emailManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Creating default email templates...');
        
        $this->emailManager->createDefaultTemplates();

        $io->success('Default email templates created successfully.');

        return Command::SUCCESS;
    }
}
