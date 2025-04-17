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
                'moreInfoText' => 'Pour en savoir plus sur notre utilisation des cookies, veuillez nous contacter :',
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
                'necessaryDetailsDescription' => 'Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas of the website. The website cannot function properly without these cookies.',
                'preferencesDetailsDescription' => 'Preference cookies enable a website to remember information that changes the way the website behaves or looks, like your preferred language or the region that you are in.',
                'statisticsDetailsDescription' => 'Statistic cookies help website owners to understand how visitors interact with websites by collecting and reporting information anonymously.',
                'marketingDetailsDescription' => 'Marketing cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user and thereby more valuable for publishers and third party advertisers.',
                'cookieName' => 'Name',
                'cookieProvider' => 'Provider',
                'cookiePurpose' => 'Purpose',
                'cookieExpiry' => 'Expiry',
                'sessionPurpose' => 'Maintains user session',
                'consentPurpose' => 'Stores cookie consent preferences',
                'sessionEnd' => 'Session end',
                'months' => 'months',
                'year' => 'year',
                'years' => 'years',
                'hours' => 'hours',
                'languagePurpose' => 'Stores language preference',
                'analyticsPurpose' => 'Tracks user behavior on the site to improve experience',
                'userIdPurpose' => 'Distinguishes users',
                'facebookPurpose' => 'Tracks ad conversions and retargeting',
                'whatAreCookies' => 'What are cookies?',
                'cookiesDefinition' => 'Cookies are small text files that are stored on your computer or mobile device when you visit a website. They allow the site to remember your actions and preferences for a period of time, so you don\'t have to re-enter them every time you visit the site or navigate from one page to another.',
                'howWeUseCookies' => 'How do we use cookies?',
                'cookiesUsage' => 'We use different types of cookies to run our website, improve your user experience, analyze how the site is used, and personalize the content and advertisements you see.',
                'managingCookies' => 'How to manage cookies?',
                'cookiesManagement' => 'You can manage your cookie preferences at any time by clicking on the "Cookie settings" link at the bottom of our site. You can also change your browser settings to delete or prevent certain cookies from being stored on your computer or mobile device without your explicit consent.',
                'moreInfo' => 'More information',
                'moreInfoText' => 'To learn more about our use of cookies, please contact us:',
                'termsLink' => 'our terms of use page',
                'necessaryOnly' => 'Necessary only',
                'savePreferences' => 'Save preferences'
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
                'necessaryDescription' => 'Deze cookies zijn essentieel voor de basisfunctionaliteit van de website en kunnen niet worden uitgeschakeld.',
                'preferencesDescription' => 'Deze cookies stellen ons in staat om uw keuzes en voorkeuren te onthouden, zoals taalvoorkeur of de regio waar u zich bevindt.',
                'statisticsDescription' => 'Deze cookies helpen ons te begrijpen hoe bezoekers met onze website interageren door informatie anoniem te verzamelen en te rapporteren.',
                'marketingDescription' => 'Deze cookies worden gebruikt om bezoekers op websites te volgen. Het doel is om advertenties weer te geven die relevant en aantrekkelijk zijn voor de individuele gebruiker.',
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
                'necessaryDescription' => 'Diese Cookies sind für die grundlegende Funktionalität der Website unerlässlich und können nicht deaktiviert werden.',
                'preferencesDescription' => 'Diese Cookies ermöglichen es uns, Ihre Auswahl und Präferenzen zu speichern, wie z.B. Spracheinstellungen oder die Region, in der Sie sich befinden.',
                'statisticsDescription' => 'Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren, indem sie Informationen anonym sammeln und melden.',
                'marketingDescription' => 'Diese Cookies werden verwendet, um Besucher auf Websites zu verfolgen. Ziel ist es, Anzeigen zu schalten, die für den einzelnen Nutzer relevant und ansprechend sind.',
                'necessaryOnly' => 'Nur notwendige',
                'savePreferences' => 'Einstellungen speichern'
                // Autres traductions allemandes
            ]
        ];
        
        return $translations[$locale] ?? $translations['fr'];
    }
}