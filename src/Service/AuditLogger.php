<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\EventSubscriber\ClientInfoSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use UAParser\Parser;
use Psr\Log\LoggerInterface;

class AuditLogger
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;
    private Parser $parser;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->parser = Parser::create();
        $this->logger = $logger;
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
            $this->parseUserAgentWithUAParser($log, $userAgent);
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
            $this->parseUserAgentWithUAParser($log, $userAgent);
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
     * Analyser l'user agent en utilisant la bibliothèque UA-Parser avec amélioration 
     * de la détection
     */
    private function parseUserAgentWithUAParser(AuditLog $log, string $userAgent): void
    {
        try {
            // Enregistrer l'User-Agent brut dans un fichier de log pour debug
            file_put_contents(
                dirname(__DIR__, 2) . '/var/log/user_agent_analysis.log',
                sprintf("[%s] Analyzing UA: %s\n", date('Y-m-d H:i:s'), $userAgent),
                FILE_APPEND
            );
            
            // Utiliser UAParser pour analyser l'User-Agent
            $result = $this->parser->parse($userAgent);
            
            // Logging des résultats de l'analyse pour debug
            file_put_contents(
                dirname(__DIR__, 2) . '/var/log/user_agent_analysis.log',
                sprintf(
                    "[%s] Analysis result: OS=%s/%s, Device=%s/%s, Browser=%s/%s\n", 
                    date('Y-m-d H:i:s'),
                    $result->os->family,
                    $result->os->toVersion(),
                    $result->device->brand,
                    $result->device->model,
                    $result->ua->family,
                    $result->ua->toVersion()
                ),
                FILE_APPEND
            );
            
            // Amélioration de la détection manuelle si les valeurs sont inconnues
            
            // Système d'exploitation
            $osName = $result->os->family;
            $osVersion = $result->os->toVersion();
            
            if ($osName === 'Other' || empty($osName)) {
                // Détection manuelle de l'OS
                $this->detectOsManually($log, $userAgent);
            } else {
                $log->setOsName($osName);
                if (!empty($osVersion)) {
                    $log->setOsVersion($osVersion);
                }
            }
            
            // Appareil
            $deviceBrand = $result->device->brand;
            $deviceModel = $result->device->model;
            
            // Amélioration pour la détection PC
            if (empty($deviceBrand) || $deviceBrand === 'Other' || $deviceBrand === 'Generic') {
                // Correction pour les PC de bureau
                if (preg_match('/(Windows|Mac OS|Linux|Ubuntu|Debian|Fedora)/i', $userAgent)) {
                    $deviceBrand = 'PC';
                    if (preg_match('/Windows/i', $userAgent)) {
                        $deviceModel = 'Windows PC';
                    } elseif (preg_match('/Mac OS/i', $userAgent)) {
                        $deviceBrand = 'Apple';
                        $deviceModel = 'Mac';
                    } else {
                        $deviceModel = 'Linux PC';
                    }
                } else {
                    // Détection manuelle du device
                    $this->detectDeviceManually($log, $userAgent);
                }
            } else {
                $log->setDeviceBrand($deviceBrand);
                $log->setDeviceModel($deviceModel ?: 'Unknown');
            }
            
            // Navigateur
            $browserName = $result->ua->family;
            $browserVersion = $result->ua->toVersion();
            
            if ($browserName === 'Other' || empty($browserName)) {
                // Détection manuelle du navigateur
                $this->detectBrowserManually($log, $userAgent);
            } else {
                $log->setBrowserName($browserName);
                if (!empty($browserVersion)) {
                    $log->setBrowserVersion($browserVersion);
                }
            }
            
        } catch (\Exception $e) {
            // Log l'erreur
            $this->logger->error('Error parsing user agent: ' . $e->getMessage(), [
                'user_agent' => $userAgent,
                'exception' => $e->getTraceAsString()
            ]);
            
            // Fallback vers la méthode manuelle complète
            $this->parseUserAgentManual($log, $userAgent);
        }
    }
    
    /**
     * Détection manuelle de l'OS
     */
    private function detectOsManually(AuditLog $log, string $userAgent): void
    {
        // Windows
        if (preg_match('/(Windows NT (\d+\.\d+))/i', $userAgent, $winMatches)) {
            $log->setOsName('Windows');
            $winVersionMap = [
                '10.0' => '10',
                '6.3' => '8.1',
                '6.2' => '8',
                '6.1' => '7',
                '6.0' => 'Vista',
                '5.2' => 'Server 2003/XP x64',
                '5.1' => 'XP',
                '5.0' => '2000'
            ];
            $log->setOsVersion($winVersionMap[$winMatches[2]] ?? $winMatches[2]);
            return;
        }
        
        // macOS / OS X
        if (preg_match('/Mac OS X (\d+[._]\d+[._]?\d*)/i', $userAgent, $macMatches)) {
            $log->setOsName('macOS');
            $log->setOsVersion(str_replace('_', '.', $macMatches[1]));
            return;
        }
        
        // iOS
        if (preg_match('/(iPhone|iPad|iPod)/i', $userAgent)) {
            $log->setOsName('iOS');
            if (preg_match('/OS (\d+[._]\d+[._]?\d*)/i', $userAgent, $iosMatches)) {
                $log->setOsVersion(str_replace('_', '.', $iosMatches[1]));
            }
            return;
        }
        
        // Android
        if (preg_match('/Android (\d+(\.\d+)*)/i', $userAgent, $androidMatches)) {
            $log->setOsName('Android');
            $log->setOsVersion($androidMatches[1]);
            return;
        }
        
        // Linux
        if (preg_match('/Linux/i', $userAgent)) {
            if (preg_match('/Ubuntu/i', $userAgent)) {
                $log->setOsName('Ubuntu');
            } elseif (preg_match('/Debian/i', $userAgent)) {
                $log->setOsName('Debian');
            } elseif (preg_match('/Fedora/i', $userAgent)) {
                $log->setOsName('Fedora');
            } else {
                $log->setOsName('Linux');
            }
            
            if (preg_match('/x86_64/i', $userAgent)) {
                $log->setOsVersion('x86_64');
            } elseif (preg_match('/i686/i', $userAgent)) {
                $log->setOsVersion('i686');
            }
            return;
        }
        
        // Par défaut
        $log->setOsName('Unknown');
        $log->setOsVersion('Unknown');
    }
    
    /**
     * Détection manuelle du device
     */
    private function detectDeviceManually(AuditLog $log, string $userAgent): void
    {
        // Apple
        if (preg_match('/(iPhone|iPad|iPod|Macintosh)/i', $userAgent, $appleMatches)) {
            $log->setDeviceBrand('Apple');
            $log->setDeviceModel($appleMatches[1]);
            return;
        }
        
        // Samsung
        if (preg_match('/(SM-[A-Z0-9]+|SAMSUNG|Galaxy)/i', $userAgent)) {
            $log->setDeviceBrand('Samsung');
            if (preg_match('/SM-([A-Z0-9]+)/i', $userAgent, $samsungMatches)) {
                $log->setDeviceModel('SM-' . $samsungMatches[1]);
            } else {
                $log->setDeviceModel('Galaxy');
            }
            return;
        }
        
        // Google
        if (preg_match('/Pixel (\d+)/i', $userAgent, $pixelMatches)) {
            $log->setDeviceBrand('Google');
            $log->setDeviceModel('Pixel ' . $pixelMatches[1]);
            return;
        }
        
        // Par défaut PC pour Windows/Linux/Mac
        if (preg_match('/(Windows|Mac OS|Linux)/i', $userAgent, $osMatches)) {
            $log->setDeviceBrand('PC');
            $log->setDeviceModel($osMatches[1] . ' PC');
            return;
        }
        
        // Par défaut
        $log->setDeviceBrand('Unknown');
        $log->setDeviceModel('Unknown');
    }
    
    /**
     * Détection manuelle du navigateur
     */
    private function detectBrowserManually(AuditLog $log, string $userAgent): void
    {
        // Chrome
        if (preg_match('/Chrome\/(\d+\.\d+)/i', $userAgent, $chromeMatches) && 
            !preg_match('/(Edge|Edg|OPR|Opera|SamsungBrowser|UCBrowser|YaBrowser)/i', $userAgent)) {
            $log->setBrowserName('Chrome');
            $log->setBrowserVersion($chromeMatches[1]);
            return;
        }
        
        // Edge
        if (preg_match('/(Edge|Edg)\/(\d+\.\d+)/i', $userAgent, $edgeMatches)) {
            $log->setBrowserName('Edge');
            $log->setBrowserVersion($edgeMatches[2]);
            return;
        }
        
        // Firefox
        if (preg_match('/Firefox\/(\d+\.\d+)/i', $userAgent, $ffMatches)) {
            $log->setBrowserName('Firefox');
            $log->setBrowserVersion($ffMatches[1]);
            return;
        }
        
        // Safari
        if (preg_match('/Safari\/(\d+\.\d+)/i', $userAgent, $safariMatches) && 
            !preg_match('/Chrome|Chromium/i', $userAgent)) {
            $log->setBrowserName('Safari');
            if (preg_match('/Version\/(\d+\.\d+)/i', $userAgent, $versionMatches)) {
                $log->setBrowserVersion($versionMatches[1]);
            } else {
                $log->setBrowserVersion($safariMatches[1]);
            }
            return;
        }
        
        // Opera
        if (preg_match('/(OPR|Opera)\/(\d+\.\d+)/i', $userAgent, $operaMatches)) {
            $log->setBrowserName('Opera');
            $log->setBrowserVersion($operaMatches[2]);
            return;
        }
        
        // IE
        if (preg_match('/(MSIE |Trident\/.*rv:)(\d+\.\d+)/i', $userAgent, $ieMatches)) {
            $log->setBrowserName('Internet Explorer');
            $log->setBrowserVersion($ieMatches[2]);
            return;
        }
        
        // Par défaut
        $log->setBrowserName('Unknown');
        $log->setBrowserVersion('Unknown');
    }
    
    /**
     * Méthode de repli pour l'analyse manuelle de l'user agent en cas d'échec complet
     */
    private function parseUserAgentManual(AuditLog $log, string $userAgent): void
    {
        // Pour la transparence, log l'User-Agent avant analyse
        file_put_contents(
            dirname(__DIR__, 2) . '/var/log/user_agent_manual.log',
            sprintf("[%s] Manual fallback for: %s\n", date('Y-m-d H:i:s'), $userAgent),
            FILE_APPEND
        );
        
        // Détection manuelle séparée
        $this->detectOsManually($log, $userAgent);
        $this->detectDeviceManually($log, $userAgent);
        $this->detectBrowserManually($log, $userAgent);
        
        // Log les résultats pour debug
        file_put_contents(
            dirname(__DIR__, 2) . '/var/log/user_agent_manual.log',
            sprintf(
                "[%s] Manual detection result: OS=%s/%s, Device=%s/%s, Browser=%s/%s\n", 
                date('Y-m-d H:i:s'),
                $log->getOsName(),
                $log->getOsVersion(),
                $log->getDeviceBrand(),
                $log->getDeviceModel(),
                $log->getBrowserName(),
                $log->getBrowserVersion()
            ),
            FILE_APPEND
        );
    }
    
    /**
     * Déterminer automatiquement le type de log en fonction de l'action.
     */
    private function determineLogType(string $action): string
    {
        $action = strtolower($action);
        
        if (strpos($action, 'view') === 0 || strpos($action, 'list') === 0 || strpos($action, 'show') === 0) {
            return AuditLog::TYPE_VIEW;
        }
        
        if (strpos($action, 'create') === 0 || strpos($action, 'add') === 0 || strpos($action, 'new') === 0) {
            return AuditLog::TYPE_CREATE;
        }
        
        if (strpos($action, 'update') === 0 || strpos($action, 'edit') === 0 || strpos($action, 'modify') === 0) {
            return AuditLog::TYPE_UPDATE;
        }
        
        if (strpos($action, 'delete') === 0 || strpos($action, 'remove') === 0) {
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
        
        if (strpos($action, 'security') === 0 || strpos($action, 'permission') === 0 || strpos($action, 'role') === 0) {
            return AuditLog::TYPE_SECURITY;
        }
        
        if (strpos($action, 'error') === 0 || strpos($action, 'exception') === 0 || strpos($action, 'fail') === 0) {
            return AuditLog::TYPE_ERROR;
        }
        
        // Par défaut, retourner le type système
        return AuditLog::TYPE_SYSTEM;
    }
}