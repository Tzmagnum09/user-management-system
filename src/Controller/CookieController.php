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
    #[Route('/cookie/preferences', name: 'app_cookie_preferences')]
    public function preferences(): Response
    {
        return $this->render('cookie/preferences.html.twig');
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

    /**
     * Récupérer les textes de traduction pour le JavaScript
     */
    #[Route('/api/cookie/translations/{locale}', name: 'app_cookie_translations')]
    public function getTranslations(string $locale = null): JsonResponse
    {
        $locale = $locale ?: $this->getParameter('kernel.default_locale');
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
                'cookiePreferences' => 'Préférences de cookies',
                'cookieIntro' => 'En tant que visiteur de notre site, nous essayons de vous offrir une expérience aussi agréable que possible. Nous utilisons des cookies en premier lieu pour améliorer votre expérience utilisateur et pour améliorer le fonctionnement de nos services en ligne. En outre, nous utilisons des cookies pour rendre le contenu de nos sites web et applications (mobiles) plus intéressant pour vous. Nous utilisons également des cookies pour cartographier votre comportement de navigation.',
                'tabOverview' => 'Vue d\'ensemble',
                'tabDetails' => 'Détails',
                'tabAbout' => 'À propos',
                'necessaryCookies' => 'Cookies nécessaires',
                'preferencesCookies' => 'Cookies de préférences',
                'statisticsCookies' => 'Cookies statistiques',
                'marketingCookies' => 'Cookies marketing',
                'necessaryDescription' => 'Ces cookies sont essentiels pour le fonctionnement de base du site web et ne peuvent pas être désactivés.',
                'preferencesDescription' => 'Ces cookies permettent de mémoriser vos choix et préférences, comme la langue préférée ou la région où vous vous trouvez.',
                'statisticsDescription' => 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site web en recueillant et rapportant des informations de manière anonyme.',
                'marketingDescription' => 'Ces cookies sont utilisés pour suivre les visiteurs sur les sites web. Le but est d\'afficher des publicités qui sont pertinentes et intéressantes pour l\'utilisateur individuel.',
                'necessaryOnly' => 'Seulement nécessaires',
                'savePreferences' => 'Enregistrer les préférences',
                // Autres traductions...
            ],
            'en' => [
                'cookiePreferences' => 'Cookie settings',
                'cookieIntro' => 'As a visitor to our site, we try to offer you an experience as pleasant as possible. We use cookies primarily to improve your user experience and to improve the functioning of our online services. In addition, we use cookies to make the content of our websites and (mobile) applications more interesting for you. We also use cookies to map your browsing behavior.',
                'tabOverview' => 'Overview',
                'tabDetails' => 'Details',
                'tabAbout' => 'About',
                'necessaryCookies' => 'Necessary cookies',
                'preferencesCookies' => 'Preference cookies',
                'statisticsCookies' => 'Statistics cookies',
                'marketingCookies' => 'Marketing cookies',
                'necessaryDescription' => 'These cookies are essential for the basic functioning of the website and cannot be disabled.',
                'preferencesDescription' => 'These cookies allow us to remember your choices and preferences, such as language preference or the region where you are located.',
                'statisticsDescription' => 'These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
                'marketingDescription' => 'These cookies are used to track visitors across websites. The aim is to display ads that are relevant and engaging for the individual user.',
                'necessaryOnly' => 'Necessary only',
                'savePreferences' => 'Save preferences',
                // Autres traductions...
            ],
            'nl' => [
                'cookiePreferences' => 'Cookie-instellingen',
                'cookieIntro' => 'Als bezoeker van onze site proberen we u een zo aangenaam mogelijke ervaring te bieden. We gebruiken cookies in de eerste plaats om uw gebruikerservaring te verbeteren en om de werking van onze online diensten te verbeteren. Daarnaast gebruiken we cookies om de inhoud van onze websites en (mobiele) applicaties interessanter voor u te maken. We gebruiken ook cookies om uw surfgedrag in kaart te brengen.',
                // Autres traductions...
            ],
            'de' => [
                'cookiePreferences' => 'Cookie-Einstellungen',
                'cookieIntro' => 'Als Besucher unserer Website versuchen wir, Ihnen ein möglichst angenehmes Erlebnis zu bieten. Wir verwenden Cookies in erster Linie, um Ihre Benutzererfahrung zu verbessern und die Funktionsweise unserer Online-Dienste zu optimieren. Darüber hinaus verwenden wir Cookies, um die Inhalte unserer Websites und (mobilen) Anwendungen für Sie interessanter zu gestalten. Wir verwenden auch Cookies, um Ihr Surfverhalten zu erfassen.',
                // Autres traductions...
            ]
        ];
        
        return $translations[$locale] ?? $translations['fr'];
    }
}