<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class BrowserDetectionController extends AbstractController
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    #[Route('/browser-detection', name: 'app_browser_detection', methods: ['POST'])]
    public function detectBrowser(Request $request): JsonResponse
    {
        // Récupérer les données envoyées par le script JS
        $data = json_decode($request->getContent(), true);
        
        // Récupérer les en-têtes personnalisés
        $browserName = $request->headers->get('X-Browser-Name');
        $browserVersion = $request->headers->get('X-Browser-Version');
        $osName = $request->headers->get('X-OS-Name');
        $osVersion = $request->headers->get('X-OS-Version');
        $deviceType = $request->headers->get('X-Device-Type');
        $originalUserAgent = $request->headers->get('X-Original-User-Agent');
        
        // Log toutes les informations reçues pour debug
        $this->logger->info('Browser detection information received', [
            'browser_name' => $browserName,
            'browser_version' => $browserVersion,
            'os_name' => $osName,
            'os_version' => $osVersion,
            'device_type' => $deviceType,
            'user_agent' => $originalUserAgent,
            'client_ip' => $request->getClientIp(),
            'data' => $data
        ]);
        
        // Écrire également dans un fichier dédié pour faciliter le debug
        file_put_contents(
            $this->getParameter('kernel.logs_dir') . '/browser_detection.log',
            sprintf(
                "[%s] IP: %s, UA: %s, Browser: %s %s, OS: %s %s, Device: %s\nHeaders: %s\nData: %s\n\n",
                date('Y-m-d H:i:s'),
                $request->getClientIp(),
                $originalUserAgent,
                $browserName,
                $browserVersion,
                $osName,
                $osVersion,
                $deviceType,
                json_encode($request->headers->all()),
                json_encode($data)
            ),
            FILE_APPEND
        );
        
        // Enregistrer ces informations dans la session pour les utiliser plus tard
        $session = $request->getSession();
        $session->set('browser_info', [
            'browser_name' => $browserName,
            'browser_version' => $browserVersion,
            'os_name' => $osName,
            'os_version' => $osVersion,
            'device_type' => $deviceType,
            'user_agent' => $originalUserAgent,
        ]);
        
        // Définir un cookie avec ces informations
        $response = new JsonResponse(['status' => 'success']);
        $cookieValue = json_encode([
            'bn' => $browserName,
            'bv' => $browserVersion,
            'on' => $osName,
            'ov' => $osVersion,
            'dt' => $deviceType,
        ]);
        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie(
                'browser_detection',
                $cookieValue,
                time() + 3600*24*30, // 30 jours
                '/',
                null,
                false,
                false,
                false,
                'lax'
            )
        );
        
        return $response;
    }
}