<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Bundle\SecurityBundle\Security;

class HttpErrorSubscriber implements EventSubscriberInterface
{
    private AuditLogger $auditLogger;
    private TokenStorageInterface $tokenStorage;
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        AuditLogger $auditLogger,
        TokenStorageInterface $tokenStorage,
        UserRepository $userRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->auditLogger = $auditLogger;
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité basse pour s'exécuter après tout le reste
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -100],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        try {
            $exception = $event->getThrowable();
            $request = $event->getRequest();
            
            // S'assurer de capturer l'User-Agent complet exactement tel qu'il est envoyé
            $userAgent = $request->headers->get('User-Agent', '');
            
            // Capturer tous les en-têtes HTTP pour debug
            $allHeaders = [];
            foreach ($request->headers->all() as $key => $values) {
                if ($key !== 'cookie' && $key !== 'authorization') { // Exclure les données sensibles
                    $allHeaders[$key] = implode(', ', $values);
                }
            }
            
            // Log tous les en-têtes pour debug
            file_put_contents(
                dirname(__DIR__, 2) . '/var/log/http_error_headers.log',
                sprintf("[%s] Request headers for error: %s\n", date('Y-m-d H:i:s'), json_encode($allHeaders)),
                FILE_APPEND
            );
            
            // Capturer l'IP client pour debug
            $clientIp = ClientInfoSubscriber::getRealClientIpStatic() ?? $request->getClientIp();
            
            // Capturer tous les attributs de requête
            $attributes = [];
            foreach ($request->attributes->all() as $key => $value) {
                if (is_scalar($value)) {
                    $attributes[$key] = $value;
                }
            }
            
            // Log pour debug
            file_put_contents(
                dirname(__DIR__, 2) . '/var/log/http_error_request.log',
                sprintf(
                    "[%s] Error request: method=%s, uri=%s, ip=%s, attributes=%s\n", 
                    date('Y-m-d H:i:s'), 
                    $request->getMethod(),
                    $request->getRequestUri(),
                    $clientIp,
                    json_encode($attributes)
                ),
                FILE_APPEND
            );
            
            // Déterminer le code d'erreur et le titre
            $errorCode = '500';
            $errorTitle = 'Internal Server Error';
            
            // Si c'est une exception HTTP, récupérer le code de statut
            if ($exception instanceof HttpExceptionInterface) {
                $errorCode = (string) $exception->getStatusCode();
                
                // Mapper le code aux titres courants
                $titles = [
                    '400' => 'Bad Request',
                    '401' => 'Unauthorized',
                    '403' => 'Forbidden',
                    '404' => 'Not Found',
                    '405' => 'Method Not Allowed',
                    '408' => 'Request Timeout',
                    '429' => 'Too Many Requests',
                    '500' => 'Internal Server Error',
                    '502' => 'Bad Gateway',
                    '503' => 'Service Unavailable',
                    '504' => 'Gateway Timeout',
                ];
                $errorTitle = $titles[$errorCode] ?? 'HTTP Error';
            }

            // Construire les détails complets de l'erreur
            $errorDetails = sprintf(
                "%s: %s\n\nURL: %s\nMethod: %s",
                $errorCode,
                $errorTitle,
                $request->getRequestUri(),
                $request->getMethod()
            );
            
            // Ajouter toutes les informations sur l'exception
            $errorDetails .= sprintf(
                "\n\nException: %s\nMessage: %s\nFile: %s (line %d)",
                get_class($exception),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );
            
            // Ajouter les headers de la requête (sans les cookies sensibles)
            $headerDetails = [];
            foreach ($allHeaders as $key => $value) {
                $headerDetails[] = $key . ': ' . $value;
            }
            
            if (!empty($headerDetails)) {
                $errorDetails .= "\n\nRequest Headers:\n" . implode("\n", $headerDetails);
            }

            // Enregistrement dans les logs du système
            $this->logger->error('HTTP Error: ' . $errorCode . ' ' . $errorTitle . ' - ' . $exception->getMessage(), [
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'exception' => get_class($exception),
                'user_agent' => $userAgent,
                'ip' => $clientIp
            ]);
            
            // Récupérer l'utilisateur connecté avec plusieurs méthodes pour assurer la fiabilité
            $user = null;
            
            // 1. Essayer d'abord avec Security component
            try {
                $currentUser = $this->security->getUser();
                if ($currentUser instanceof User) {
                    $user = $currentUser;
                    
                    // Log pour debug
                    file_put_contents(
                        dirname(__DIR__, 2) . '/var/log/http_error_user.log',
                        sprintf("[%s] Found user from Security: %s\n", date('Y-m-d H:i:s'), $user->getEmail()),
                        FILE_APPEND
                    );
                }
            } catch (\Throwable $e) {
                $this->logger->debug('Could not get user from Security: ' . $e->getMessage());
            }
            
            // 2. Si échec, essayer avec TokenStorage
            if (!$user) {
                try {
                    $token = $this->tokenStorage->getToken();
                    if ($token && ($tokenUser = $token->getUser()) instanceof User) {
                        $user = $tokenUser;
                        
                        // Log pour debug
                        file_put_contents(
                            dirname(__DIR__, 2) . '/var/log/http_error_user.log',
                            sprintf("[%s] Found user from TokenStorage: %s\n", date('Y-m-d H:i:s'), $user->getEmail()),
                            FILE_APPEND
                        );
                    }
                } catch (\Throwable $e) {
                    $this->logger->debug('Could not get user from TokenStorage: ' . $e->getMessage());
                }
            }
            
            // 3. En dernier recours, utiliser un utilisateur système
            if (!$user) {
                $user = $this->findOrCreateSystemUser();
                if ($user) {
                    // Log pour debug
                    file_put_contents(
                        dirname(__DIR__, 2) . '/var/log/http_error_user.log',
                        sprintf("[%s] Using system user: %s\n", date('Y-m-d H:i:s'), $user->getEmail()),
                        FILE_APPEND
                    );
                } else {
                    $this->logger->error('No system user found for logging HTTP errors');
                    return; // Impossible de logger sans utilisateur
                }
            }
            
            // Vérifier si l'entity manager est disponible
            if (!$this->entityManager->isOpen()) {
                $this->logger->info('Entity manager is closed, reopening for error logging');
                // Réinitialiser l'entity manager
                $this->entityManager = $this->entityManager->create(
                    $this->entityManager->getConnection(),
                    $this->entityManager->getConfiguration()
                );
            }
            
            // Ajouter l'UserAgent complet dans les détails pour s'assurer qu'il est bien enregistré
            $errorDetails .= "\n\nFull User-Agent: " . $userAgent;
            
            // Log complet pour debug avant de sauvegarder dans la base
            file_put_contents(
                dirname(__DIR__, 2) . '/var/log/http_error_details.log',
                sprintf(
                    "[%s] Logging HTTP error %s for %s:\n%s\n\nWith User-Agent: %s\n\n",
                    date('Y-m-d H:i:s'),
                    $errorCode,
                    $user->getEmail(),
                    $errorDetails,
                    $userAgent
                ),
                FILE_APPEND
            );
            
            // Utiliser logErrorWithUserAgent pour passer l'User-Agent spécifiquement
            $this->auditLogger->logErrorWithUserAgent(
                $user,
                'http_error_' . $errorCode,
                $errorDetails,
                $userAgent
            );
            
        } catch (\Throwable $e) {
            // En cas d'erreur, logger mais ne pas propager l'exception
            $this->logger->error('Error in HttpErrorSubscriber: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Écrire les détails de l'erreur pour debug
            file_put_contents(
                dirname(__DIR__, 2) . '/var/log/http_error_failure.log',
                sprintf(
                    "[%s] Failed to log HTTP error: %s\n%s\n",
                    date('Y-m-d H:i:s'),
                    $e->getMessage(),
                    $e->getTraceAsString()
                ),
                FILE_APPEND
            );
        }
    }
    
    /**
     * Trouve un utilisateur système ou super admin pour enregistrer les erreurs
     * quand il n'y a pas d'utilisateur connecté
     */
    private function findOrCreateSystemUser(): ?User
    {
        try {
            // Chercher d'abord un super admin
            $superAdmins = $this->userRepository->findSuperAdmins();
            if (!empty($superAdmins)) {
                return $superAdmins[0]; // Retourner le premier super admin trouvé
            }
            
            // Sinon, chercher un admin
            $admins = $this->userRepository->findAdmins();
            if (!empty($admins)) {
                return $admins[0]; // Retourner le premier admin trouvé
            }
            
            // En dernier recours, prendre le premier utilisateur de la base
            $allUsers = $this->userRepository->findBy([], ['id' => 'ASC'], 1);
            if (!empty($allUsers)) {
                return $allUsers[0];
            }
            
            $this->logger->warning('No user found in database to log HTTP error');
            return null;
            
        } catch (\Throwable $e) {
            $this->logger->error('Error finding system user: ' . $e->getMessage());
            return null;
        }
    }
}