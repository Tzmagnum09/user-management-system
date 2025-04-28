/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

document.addEventListener('DOMContentLoaded', function() {
    console.log('App.js loaded - welcome to AssetMapper! 🎉');
    
    // Initialisation de la coordination des langues entre les composants
    initializeLanguageCoordination();
    
    /**
     * Initialise la coordination des changements de langue entre les différents composants
     */
    function initializeLanguageCoordination() {
        // Liaison entre la bannière de cookies et la modale de cookies
        linkCookieBannerToModal();
        
        // Écouter les changements de langue depuis les sélecteurs de la bannière de cookies
        listenForCookieBannerLanguageChanges();
        
        // Écouter les changements de langue depuis la modale de cookies
        listenForCookieModalLanguageChanges();
        
        // Écouter les changements de langue depuis les liens de langue du site
        listenForSiteLanguageChanges();
    }
    
    /**
     * Lie la bannière de cookies à la modale des cookies
     */
    function linkCookieBannerToModal() {
        const cookieLanguageSelector = document.getElementById('cookie-language-selector');
        
        if (cookieLanguageSelector) {
            cookieLanguageSelector.addEventListener('change', function() {
                const selectedLocale = this.value;
                
                // Mettre à jour la langue dans localStorage
                localStorage.setItem('selectedLocale', selectedLocale);
                
                // Déclencher l'événement de changement de langue
                const event = new CustomEvent('languageChanged', {
                    detail: {
                        locale: selectedLocale,
                        source: 'cookie-banner'
                    },
                    bubbles: true
                });
                document.dispatchEvent(event);
            });
        }
    }
    
    /**
     * Écoute les changements de langue depuis la bannière de cookies
     */
    function listenForCookieBannerLanguageChanges() {
        document.addEventListener('languageChanged', function(event) {
            if (event.detail && event.detail.locale && event.detail.source === 'cookie-banner') {
                // Mettre à jour la modale des cookies si elle est initialisée
                if (window.cookieManager && typeof window.cookieManager.updateLanguage === 'function') {
                    window.cookieManager.updateLanguage(event.detail.locale);
                }
                
                // Rediriger vers la nouvelle URL avec la langue sélectionnée
                redirectToLocale(event.detail.locale);
            }
        });
    }
    
    /**
     * Écoute les changements de langue depuis la modale des cookies
     */
    function listenForCookieModalLanguageChanges() {
        document.addEventListener('cookieLanguageChanged', function(event) {
            if (event.detail && event.detail.locale) {
                // Mettre à jour le sélecteur de langue de la bannière
                const cookieLanguageSelector = document.getElementById('cookie-language-selector');
                if (cookieLanguageSelector) {
                    cookieLanguageSelector.value = event.detail.locale;
                }
                
                // Ne pas rediriger si l'événement provient déjà du site
                if (event.detail.source !== 'site-language-switcher') {
                    // Rediriger vers la nouvelle URL avec la langue sélectionnée
                    redirectToLocale(event.detail.locale);
                }
            }
        });
    }
    
    /**
     * Écoute les changements de langue depuis les liens de langue du site
     */
    function listenForSiteLanguageChanges() {
        document.addEventListener('languageChanged', function(event) {
            if (event.detail && event.detail.locale && event.detail.source === 'site-language-switcher') {
                // Mettre à jour le sélecteur de langue de la bannière
                const cookieLanguageSelector = document.getElementById('cookie-language-selector');
                if (cookieLanguageSelector && cookieLanguageSelector.value !== event.detail.locale) {
                    cookieLanguageSelector.value = event.detail.locale;
                }
                
                // Mettre à jour la modale des cookies si elle est initialisée
                if (window.cookieManager && typeof window.cookieManager.updateLanguage === 'function') {
                    window.cookieManager.updateLanguage(event.detail.locale);
                }
            }
        });
    }
    
    /**
     * Redirige l'utilisateur vers la même page avec la nouvelle langue
     */
    function redirectToLocale(locale) {
        // Vérifier si la langue courante est déjà celle demandée
        const currentLang = getCurrentLanguage();
        if (currentLang === locale) {
            // Ne pas rediriger si la langue est déjà celle demandée
            return;
        }
        
        // Construire la nouvelle URL avec la langue sélectionnée
        const currentUrl = new URL(window.location.href);
        
        // Vérifier si l'URL contient déjà un code de langue
        const localeRegex = /^\/?(fr|en|nl|de)\//;
        const localeMatch = currentUrl.pathname.match(localeRegex);
        
        let newUrl;
        if (localeMatch) {
            // Remplacer le code de langue existant
            const newPath = currentUrl.pathname.replace(localeRegex, `/${locale}/`);
            newUrl = `${currentUrl.origin}${newPath}${currentUrl.search}`;
        } else if (currentUrl.search.includes('_locale=')) {
            // Remplacer le paramètre _locale existant
            const params = new URLSearchParams(currentUrl.search);
            params.set('_locale', locale);
            newUrl = `${currentUrl.origin}${currentUrl.pathname}?${params.toString()}`;
        } else {
            // Ajouter le paramètre _locale
            const params = new URLSearchParams(currentUrl.search);
            params.append('_locale', locale);
            newUrl = `${currentUrl.origin}${currentUrl.pathname}?${params.toString()}`;
        }
        
        // Rediriger vers la nouvelle URL
        window.location.href = newUrl;
    }
    
    /**
     * Obtient la langue actuelle du site
     */
    function getCurrentLanguage() {
        // Méthode 1: Vérifier l'attribut lang de l'élément HTML
        const htmlLang = document.documentElement.lang;
        if (htmlLang && ['fr', 'en', 'nl', 'de'].includes(htmlLang.substring(0, 2))) {
            return htmlLang.substring(0, 2);
        }
        
        // Méthode 2: Vérifier l'URL pour un préfixe de langue
        const pathMatch = window.location.pathname.match(/^\/(fr|en|nl|de)\//);
        if (pathMatch) {
            return pathMatch[1];
        }
        
        // Méthode 3: Vérifier le paramètre _locale dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const localeParam = urlParams.get('_locale');
        if (localeParam && ['fr', 'en', 'nl', 'de'].includes(localeParam)) {
            return localeParam;
        }
        
        // Méthode 4: Vérifier localStorage
        const storedLocale = localStorage.getItem('selectedLocale');
        if (storedLocale && ['fr', 'en', 'nl', 'de'].includes(storedLocale)) {
            return storedLocale;
        }
        
        // Par défaut, retourner français
        return 'fr';
    }
});