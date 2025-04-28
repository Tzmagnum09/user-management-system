<?php

namespace App\EventListener;

use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LogoutSubscriber implements EventSubscriberInterface
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        // S'assurer qu'un token existe
        if (!$token instanceof TokenInterface) {
            return;
        }
        
        $user = $token->getUser();
        
        // S'assurer que l'utilisateur est un objet User et pas juste un nom d'utilisateur
        if ($user instanceof \App\Entity\User) {
            // Enregistrer la déconnexion
            $this->auditLogger->logLogin(
                $user,
                'user_logged_out',
                'User logged out from the application.'
            );
        }
    }
}