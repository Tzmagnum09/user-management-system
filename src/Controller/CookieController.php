<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CookieController extends AbstractController
{
    /**
     * Page des préférences de cookies
     */
    #[Route('/cookie/preferences', name: 'app_cookie_preferences')]
    public function preferences(): Response
    {
        return $this->render('cookie/cookie_preferences.html.twig');
    }

    /**
     * API pour sauvegarder le consentement aux cookies
     */
    #[Route('/api/cookie/consent', name: 'app_cookie_consent', methods: ['POST'])]
    public function saveConsent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['consent'])) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
            }
            
            // Ici, vous pourriez enregistrer le consentement en base de données
            // si vous souhaitez conserver un historique des consentements
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Cookie preferences saved successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error saving cookie preferences: ' . $e->getMessage()
            ], 500);
        }
    }
}