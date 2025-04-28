<?php

namespace App\Command;

use App\Entity\User;
use App\Service\EmailManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email',
    description: 'Envoie un email de test pour vérifier la compatibilité avec les clients de messagerie',
)]
class TestEmailCommand extends Command
{
    private EmailManager $emailManager;

    public function __construct(EmailManager $emailManager)
    {
        parent::__construct();
        $this->emailManager = $emailManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de destination')
            ->addArgument('template', InputArgument::OPTIONAL, 'Code du template à tester', 'registration_confirmation')
            ->addArgument('locale', InputArgument::OPTIONAL, 'Locale à utiliser', 'fr')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $templateCode = $input->getArgument('template');
        $locale = $input->getArgument('locale');

        $io->note(sprintf('Envoi d\'un email de test à %s avec le template "%s" et la locale "%s"', $email, $templateCode, $locale));

        try {
            // Créer un utilisateur temporaire pour le test
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName('Test');
            $user->setLastName('Utilisateur');
            $user->setLocale($locale);
            
            // Variables de test en fonction du template
            $variables = $this->getTestVariables($templateCode);
            
            // Envoyer l'email
            $this->emailManager->sendEmailToUser($user, $templateCode, $variables);
            
            $io->success('Email envoyé avec succès!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Récupère des variables de test pour un template spécifique
     */
    private function getTestVariables(string $templateCode): array
    {
        $baseUrl = 'https://example.com';
        
        switch ($templateCode) {
            case 'registration_confirmation':
                return [
                    'signedUrl' => $baseUrl . '/verify/email?token=test-token-' . uniqid(),
                ];
                
            case 'reset_password':
                return [
                    'resetToken' => $baseUrl . '/reset-password/test-token-' . uniqid(),
                ];
                
            case 'role_change':
                return [
                    'previousRole' => 'Utilisateur',
                    'newRole' => 'Administrateur',
                ];
                
            case 'permission_update':
                return [
                    'permissionChanges' => 'view_users: Refusé → Accordé<br>edit_users: Refusé → Accordé<br>view_email_templates: Accordé → Refusé',
                ];
                
            case 'account_approved':
            default:
                return [];
        }
    }
}