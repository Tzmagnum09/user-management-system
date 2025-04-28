<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\EventSubscriber\ClientInfoSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class AuditLogger
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private LoggerInterface $logger;
    private BrowserDetectionService $browserDetection;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        LoggerInterface $logger,
        BrowserDetectionService $browserDetection
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->browserDetection = $browserDetection;
    }

    /**
     * Log any action to the audit log.
     */
    public function log(User $user, string $action, string $details = null): AuditLog
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Utiliser prioritairement l'IP réelle capturée par le subscriber
        $ipAddress = ClientInfoSubscriber::getRealClientIpStatic() ?? 
                    ($request ? $request->getClientIp() : '0.0.0.0');
        
        // Utiliser prioritairement l'User-Agent réel capturé par le subscriber
        $userAgent = ClientInfoSubscriber::getRealUserAgentStatic() ?? 
                    ($request ? $request->headers->get('User-Agent') : null);
        
        // Logging de debug
        $this->logger->debug('Capturing client info', [
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'action' => $action
        ]);
        
        $log = new AuditLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setDetails($details);
        $log->setIpAddress($ipAddress);
        $log->setUserAgent($userAgent);
        
        // Déterminer le type de log en fonction de l'action
        $log->setType($this->determineLogType($action));
        
        // Analyser l'user agent si présent
        if ($userAgent) {
            $this->parseUserAgent($log, $userAgent);
        }
        
        $this->entityManager->persist($log);
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to save audit log: ' . $e->getMessage(), [
                'exception' => $e,
                'user' => $user->getEmail(),
                'action' => $action
            ]);
        }
        
        return $log;
    }

    /**
     * Spécifiquement pour les logs de visualisation.
     */
    public function logView(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_VIEW);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs de création.
     */
    public function logCreate(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_CREATE);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs de mise à jour.
     */
    public function logUpdate(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_UPDATE);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs de suppression.
     */
    public function logDelete(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_DELETE);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs de connexion/déconnexion.
     */
    public function logLogin(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_LOGIN);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs de sécurité.
     */
    public function logSecurity(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_SECURITY);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs d'erreur.
     */
    public function logError(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_ERROR);
        $this->entityManager->flush();
        return $log;
    }

    /**
     * Spécifiquement pour les logs d'erreur avec un User-Agent spécifique.
     */
    public function logErrorWithUserAgent(User $user, string $action, string $details = null, string $userAgent = null): AuditLog
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Utiliser prioritairement l'IP réelle capturée par le subscriber
        $ipAddress = ClientInfoSubscriber::getRealClientIpStatic() ?? 
                    ($request ? $request->getClientIp() : '0.0.0.0');
        
        // Si aucun User-Agent n'est fourni explicitement, utiliser celui capturé par le subscriber
        if ($userAgent === null) {
            $userAgent = ClientInfoSubscriber::getRealUserAgentStatic() ?? 
                        ($request ? $request->headers->get('User-Agent') : null);
        }
        
        // Logging de debug
        $this->logger->debug('Capturing client info for error', [
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'action' => $action
        ]);
        
        $log = new AuditLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setDetails($details);
        $log->setIpAddress($ipAddress);
        $log->setUserAgent($userAgent);
        $log->setType(AuditLog::TYPE_ERROR);
        
        // Analyser l'user agent si présent
        if ($userAgent) {
            $this->parseUserAgent($log, $userAgent);
        }
        
        $this->entityManager->persist($log);
        $this->entityManager->flush();
        
        return $log;
    }

    /**
     * Spécifiquement pour les logs système.
     */
    public function logSystem(User $user, string $action, string $details = null): AuditLog
    {
        $log = $this->log($user, $action, $details);
        $log->setType(AuditLog::TYPE_SYSTEM);
        $this->entityManager->flush();
        return $log;
    }
    
    /**
     * Analyse l'user agent en utilisant le service BrowserDetection
     */
    private function parseUserAgent(AuditLog $log, string $userAgent): void
    {
        try {
            // Utiliser le service de détection pour analyser l'User-Agent
            $result = $this->browserDetection->analyzeUserAgent($userAgent);
            
            // Appliquer les résultats à l'entité AuditLog
            $log->setDeviceBrand($result['device_brand']);
            $log->setDeviceModel($result['device_model']);
            $log->setOsName($result['os_name']);
            $log->setOsVersion($result['os_version']);
            $log->setBrowserName($result['browser_name']);
            $log->setBrowserVersion($result['browser_version']);
            
        } catch (\Exception $e) {
            // Log l'erreur
            $this->logger->error('Error parsing user agent: ' . $e->getMessage(), [
                'user_agent' => $userAgent,
                'exception' => $e->getTraceAsString()
            ]);
            
            // Valeurs par défaut
            $log->setDeviceBrand('Unknown');
            $log->setDeviceModel('Unknown');
            $log->setOsName('Unknown');
            $log->setOsVersion('Unknown');
            $log->setBrowserName('Unknown');
            $log->setBrowserVersion('Unknown');
        }
    }
    
    /**
     * Déterminer automatiquement le type de log en fonction de l'action.
     */
    private function determineLogType(string $action): string
    {
        $action = strtolower($action);
        
        if (strpos($action, 'view') === 0 || 
            strpos($action, 'list') === 0 || 
            strpos($action, 'show') === 0 || 
            strpos($action, 'preview') === 0) { // Ajout de "preview"
            return AuditLog::TYPE_VIEW;
        }
        
        if (strpos($action, 'create') === 0 || 
            strpos($action, 'add') === 0 || 
            strpos($action, 'new') === 0) {
            return AuditLog::TYPE_CREATE;
        }
        
        if (strpos($action, 'update') === 0 || 
            strpos($action, 'edit') === 0 || 
            strpos($action, 'modify') === 0 || 
            strpos($action, 'updated') !== false || // Ajout de "updated"
            strpos($action, 'preferences') !== false) { // Ajout de "preferences"
            return AuditLog::TYPE_UPDATE;
        }
        
        if (strpos($action, 'delete') === 0 || 
            strpos($action, 'remove') === 0) {
            return AuditLog::TYPE_DELETE;
        }
        
        // Correction pour détecter également les actions de connexion/déconnexion
        if (strpos($action, 'login') === 0 || 
            strpos($action, 'logout') === 0 || 
            strpos($action, 'log_in') === 0 || 
            strpos($action, 'log_out') === 0 || 
            strpos($action, 'logged_in') !== false || 
            strpos($action, 'logged_out') !== false) {
            return AuditLog::TYPE_LOGIN;
        }
        
        if (strpos($action, 'security') === 0 || 
            strpos($action, 'permission') === 0 || 
            strpos($action, 'role') === 0) {
            return AuditLog::TYPE_SECURITY;
        }
        
        if (strpos($action, 'error') === 0 || 
            strpos($action, 'exception') === 0 || 
            strpos($action, 'fail') === 0) {
            return AuditLog::TYPE_ERROR;
        }
        
        // Par défaut, retourner le type système
        return AuditLog::TYPE_SYSTEM;
    }
}