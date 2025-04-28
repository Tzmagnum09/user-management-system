<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

class ClientInfoSubscriber implements EventSubscriberInterface
{
    private static $realUserAgent = null;
    private static $realClientIp = null;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999], // Priorité très élevée pour être exécuté en premier
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Capture l'adresse IP réelle de l'utilisateur
        self::$realClientIp = $this->getRealIpAddress($request);
        
        // Capture le User-Agent réel
        self::$realUserAgent = $this->getRealUserAgent($request);
        
        // Ajouter des attributs à la requête pour les rendre accessibles partout
        $request->attributes->set('real_user_agent', self::$realUserAgent);
        $request->attributes->set('real_client_ip', self::$realClientIp);
        
        // Log debug direct
        file_put_contents(
            dirname(__DIR__, 2) . '/var/log/client_info_debug.log',
            sprintf("[%s] IP: %s UA: %s\n", date('Y-m-d H:i:s'), self::$realClientIp, self::$realUserAgent),
            FILE_APPEND
        );
    }

    /**
     * Récupère l'adresse IP réelle de l'utilisateur 
     */
    private function getRealIpAddress(Request $request): string
    {
        // Ajouter des en-têtes spécifiques aux VPN
        $vpnHeaders = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_TRUE_CLIENT_IP', // Akamai et autres CDN
            'REMOTE_ADDR'
        ];
        
        // Vérifier chaque en-tête
        foreach ($vpnHeaders as $header) {
            $value = $request->server->get($header);
            if ($value) {
                // Si l'en-tête contient plusieurs IPs (ex: X-Forwarded-For: client, proxy1, proxy2)
                if (strpos($value, ',') !== false) {
                    $ips = explode(',', $value);
                    $clientIp = trim($ips[0]); // Première IP = client d'origine
                    if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                        return $clientIp;
                    }
                } else if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return $value;
                }
            }
        }
        
        // Fallback sur l'IP standard
        return $request->getClientIp() ?? '0.0.0.0';
    }

    /**
     * Récupère le User-Agent réel de l'utilisateur
     */
    private function getRealUserAgent(Request $request): string
    {
        // Vérifier tous les en-têtes possibles contenant l'User-Agent
        $userAgentHeaders = [
            'HTTP_USER_AGENT',
            'HTTP_X_ORIGINAL_USER_AGENT',
            'HTTP_X_FORWARDED_USER_AGENT',
            'HTTP_X_DEVICE_USER_AGENT',
            'HTTP_X_OPERAMINI_PHONE_UA',  // Spécifique à Opera Mini
            'HTTP_X_SKYFIRE_PHONE',      // Autre navigateur mobile
            'HTTP_X_BOLT_PHONE_UA',      // Autre navigateur mobile
            'HTTP_DEVICE_STOCK_UA',      // Spécifique aux dispositifs mobiles
            'HTTP_X_UCBROWSER_UA',       // UC Browser
            'HTTP_FROM'                  // Ancienne spécification, rarement utilisée
        ];
        
        // Vérifier directement les en-têtes de requête bruts aussi
        foreach ($request->headers->all() as $name => $values) {
            if (stripos($name, 'user-agent') !== false) {
                return $values[0];
            }
        }
        
        // Vérifier les en-têtes standards
        foreach ($userAgentHeaders as $header) {
            $value = $request->server->get($header);
            if ($value) {
                // Capture le header pour debug
                file_put_contents(
                    dirname(__DIR__, 2) . '/var/log/useragent_debug.log',
                    sprintf("[%s] Header %s: %s\n", date('Y-m-d H:i:s'), $header, $value),
                    FILE_APPEND
                );
                return $value;
            }
        }
        
        // Fallback sur l'User-Agent standard
        return $request->headers->get('User-Agent', 'Unknown');
    }

    /**
     * Obtenir l'User-Agent réel capturé
     */
    public static function getRealUserAgentStatic(): ?string
    {
        return self::$realUserAgent;
    }

    /**
     * Obtenir l'IP réelle capturée
     */
    public static function getRealClientIpStatic(): ?string
    {
        return self::$realClientIp;
    }
}