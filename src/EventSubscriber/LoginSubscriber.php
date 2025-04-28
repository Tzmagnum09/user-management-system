<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess'
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // Vérifier que l'utilisateur est bien une instance de notre entité User
        if ($user instanceof User) {
            // Utiliser explicitement logLogin plutôt que log pour s'assurer que le type est correct
            $this->auditLogger->logLogin(
                $user,
                'user_login',
                'User logged in to the application.'
            );
            
            // Mettre à jour la date de dernière connexion
            $user->setLastLoginAt(new \DateTimeImmutable());
        }
    }
}