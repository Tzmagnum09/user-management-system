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
                'cookieIntro' => 'Nous utilisons des cookies pour améliorer votre expérience sur notre site. Vous pouvez choisir les cookies que vous acceptez.',
                'tabOverview' => 'Vue d\'ensemble',
                'tabDetails' => 'Détails',
                'tabTerms' => 'Conditions d\'utilisation',
                'tabAbout' => 'À propos',
                'necessaryCookies' => 'Cookies nécessaires',
                'preferencesCookies' => 'Cookies de préférences',
                'statisticsCookies' => 'Cookies statistiques',
                'marketingCookies' => 'Cookies marketing',
                'necessaryDescription' => 'Ces cookies sont essentiels pour le fonctionnement de base du site web et ne peuvent pas être désactivés.',
                'preferencesDescription' => 'Ces cookies permettent de mémoriser vos choix et préférences, comme la langue préférée ou la région où vous vous trouvez.',
                'statisticsDescription' => 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site web en recueillant et rapportant des informations de manière anonyme.',
                'marketingDescription' => 'Ces cookies sont utilisés pour suivre les visiteurs sur les sites web. Le but est d\'afficher des publicités qui sont pertinentes et intéressantes pour l\'utilisateur individuel.',
                'necessaryDetailsDescription' => 'Les cookies nécessaires aident à rendre un site web utilisable en activant des fonctions de base comme la navigation de page et l\'accès aux zones sécurisées du site web. Le site web ne peut pas fonctionner correctement sans ces cookies.',
                'preferencesDetailsDescription' => 'Les cookies de préférences permettent à un site web de mémoriser des informations qui modifient la manière dont le site se comporte ou s\'affiche, comme votre langue préférée ou la région dans laquelle vous vous trouvez.',
                'statisticsDetailsDescription' => 'Les cookies statistiques aident les propriétaires du site web à comprendre comment les visiteurs interagissent avec le site en recueillant et en transmettant des informations de manière anonyme.',
                'marketingDetailsDescription' => 'Les cookies marketing sont utilisés pour suivre les visiteurs sur les sites web. Le but est d\'afficher des publicités qui sont pertinentes et intéressantes pour l\'utilisateur individuel et donc plus précieuses pour les éditeurs et annonceurs tiers.',
                'cookieName' => 'Nom',
                'cookieProvider' => 'Fournisseur',
                'cookiePurpose' => 'Finalité',
                'cookieExpiry' => 'Expiration',
                'sessionPurpose' => 'Maintien de la session utilisateur',
                'consentPurpose' => 'Stockage des préférences de consentement aux cookies',
                'sessionEnd' => 'Fin de session',
                'months' => 'mois',
                'year' => 'an',
                'years' => 'ans',
                'hours' => 'heures',
                'languagePurpose' => 'Stockage de la préférence de langue',
                'analyticsPurpose' => 'Suivi du comportement des utilisateurs sur le site pour améliorer l\'expérience',
                'userIdPurpose' => 'Distinction des utilisateurs',
                'facebookPurpose' => 'Suivi des conversions publicitaires et retargeting',
                'whatAreCookies' => 'Que sont les cookies ?',
                'cookiesDefinition' => 'Les cookies sont de petits fichiers texte qui sont stockés sur votre ordinateur ou appareil mobile lorsque vous visitez un site web. Ils permettent au site de mémoriser vos actions et préférences pendant une période déterminée, afin que vous n\'ayez pas à les ressaisir à chaque fois que vous visitez le site ou naviguez d\'une page à une autre.',
                'howWeUseCookies' => 'Comment utilisons-nous les cookies ?',
                'cookiesUsage' => 'Nous utilisons différents types de cookies pour faire fonctionner notre site, améliorer votre expérience utilisateur, analyser comment le site est utilisé et personnaliser le contenu et les publicités que vous voyez.',
                'managingCookies' => 'Comment gérer les cookies ?',
                'cookiesManagement' => 'Vous pouvez gérer vos préférences de cookies à tout moment en cliquant sur le lien "Paramètres des cookies" en bas de notre site. Vous pouvez également modifier les paramètres de votre navigateur pour supprimer ou empêcher certains cookies d\'être stockés sur votre ordinateur ou appareil mobile sans votre consentement explicite.',
                'moreInfo' => 'Plus d\'informations',
                'moreInfoText' => 'Pour en savoir plus sur notre utilisation des cookies, veuillez consulter notre politique de confidentialité et nos conditions d\'utilisation sur',
                'termsLink' => 'notre page des conditions d\'utilisation',
                'necessaryOnly' => 'Refuser optionnels',
                'savePreferences' => 'Enregistrer les préférences'
            ],
            'en' => [
                'cookiePreferences' => 'Cookie settings',
                'cookieIntro' => 'We use cookies to enhance your experience on our website. You can choose which cookies you accept.',
                'tabOverview' => 'Overview',
                'tabDetails' => 'Details',
                'tabTerms' => 'Terms of Use',
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
                'savePreferences' => 'Save preferences'
                // Autres traductions anglaises
            ],
            'nl' => [
                'cookiePreferences' => 'Cookie-instellingen',
                'cookieIntro' => 'Als bezoeker van onze site proberen we u een zo aangenaam mogelijke ervaring te bieden. We gebruiken cookies in de eerste plaats om uw gebruikerservaring te verbeteren en om de werking van onze online diensten te verbeteren.',
                'tabOverview' => 'Overzicht',
                'tabDetails' => 'Details',
                'tabTerms' => 'Gebruiksvoorwaarden',
                'tabAbout' => 'Over',
                'necessaryCookies' => 'Noodzakelijke cookies',
                'preferencesCookies' => 'Voorkeurscookies',
                'statisticsCookies' => 'Statistische cookies',
                'marketingCookies' => 'Marketing cookies',
                'necessaryOnly' => 'Alleen noodzakelijke',
                'savePreferences' => 'Voorkeuren opslaan'
                // Autres traductions néerlandaises
            ],
            'de' => [
                'cookiePreferences' => 'Cookie-Einstellungen',
                'cookieIntro' => 'Als Besucher unserer Website versuchen wir, Ihnen ein möglichst angenehmes Erlebnis zu bieten. Wir verwenden Cookies in erster Linie, um Ihre Benutzererfahrung zu verbessern und die Funktionsweise unserer Online-Dienste zu optimieren.',
                'tabOverview' => 'Übersicht',
                'tabDetails' => 'Details',
                'tabTerms' => 'Nutzungsbedingungen',
                'tabAbout' => 'Über',
                'necessaryCookies' => 'Notwendige Cookies',
                'preferencesCookies' => 'Präferenz-Cookies',
                'statisticsCookies' => 'Statistik-Cookies',
                'marketingCookies' => 'Marketing-Cookies',
                'necessaryOnly' => 'Nur notwendige',
                'savePreferences' => 'Einstellungen speichern'
                // Autres traductions allemandes
            ]
        ];
        
        return $translations[$locale] ?? $translations['fr'];
    }
}