```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CookieController extends AbstractController
{
    /**
     * Page des préférences de cookies
     */
    #[Route('/cookies/preferences', name: 'app_cookie_preferences')]
    public function preferences(): Response
    {
        return $this->render('cookie/preferences.html.twig');
    }

    /**
     * API pour sauvegarder le consentement aux cookies
     */
    #[Route('/api/cookies/consent', name: 'app_cookie_consent', methods: ['POST'])]
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

    /**
     * Récupérer les textes de traduction pour le JavaScript
     */
    #[Route('/api/cookies/translations/{locale}', name: 'app_cookie_translations')]
    public function getTranslations(string $locale = null): JsonResponse
    {
        $locale = $locale ?: $this->getParameter('locale');
        $supportedLocales = ['fr', 'en', 'nl', 'de'];
        
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'fr'; // Default to French
        }
        
        // Load translations based on locale
        $translations = $this->getTranslationsForLocale($locale);
        
        return new JsonResponse($translations);
    }
    
    /**
     * Récupérer les traductions pour une locale spécifique
     */
    private function getTranslationsForLocale(string $locale): array
    {
        // Translations could be loaded from database, files, or translation service
        $translations = [
            'fr' => [
                'cookieSettings' => 'Préférences de cookies',
                'cookieIntro' => 'En tant que visiteur de notre site, nous essayons de vous offrir une expérience aussi agréable que possible. Nous utilisons des cookies en premier lieu pour améliorer votre expérience utilisateur et pour améliorer le fonctionnement de nos services en ligne. En outre, nous utilisons des cookies pour rendre le contenu de nos sites web et applications (mobiles) plus intéressant pour vous. Nous utilisons également des cookies pour cartographier votre comportement de navigation.',
                // Add other translations here
            ],
            'en' => [
                'cookieSettings' => 'Cookie settings',
                'cookieIntro' => 'As a visitor to our site, we try to offer you an experience as pleasant as possible. We use cookies primarily to improve your user experience and to improve the functioning of our online services. In addition, we use cookies to make the content of our websites and (mobile) applications more interesting for you. We also use cookies to map your browsing behavior.',
                // Add other translations here
            ],
            // Add other locales here
        ];
        
        return $translations[$locale] ?? $translations['fr'];
    }
}
