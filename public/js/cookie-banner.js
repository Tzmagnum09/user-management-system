/**
 * Script pour la bannière de cookies
 */
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const cookieBanner = document.getElementById('cookie-banner');
    const languageSelector = document.getElementById('cookie-language-selector');
    const customizeBtn = document.getElementById('customize-cookies-btn');
    const necessaryBtn = document.getElementById('necessary-cookies-btn');
    const acceptAllBtn = document.getElementById('accept-all-cookies-btn');
    
    // Configuration par défaut
    const cookieConfig = {
        necessary: true, // toujours obligatoire
        preferences: true,
        statistics: true,
        marketing: false,
        consentGiven: false
    };
    
    // Vérifier si le consentement a déjà été donné
    const storedConsent = localStorage.getItem('cookieConsent');
    const isConsentGiven = storedConsent ? JSON.parse(storedConsent).consentGiven : false;
    
    // Afficher la bannière si le consentement n'a pas été donné
    if (!isConsentGiven && cookieBanner) {
        cookieBanner.classList.remove('d-none');
    }
    
    // Gestionnaire d'événements pour le sélecteur de langue
    if (languageSelector) {
        languageSelector.addEventListener('change', function() {
            const selectedLang = this.value;
            
            // Mettre à jour localStorage
            localStorage.setItem('selectedLocale', selectedLang);
            
            // Déclencher l'événement de changement de langue
            const event = new CustomEvent('languageChanged', {
                detail: {
                    locale: selectedLang,
                    source: 'cookie-banner'
                },
                bubbles: true
            });
            document.dispatchEvent(event);
        });
    }
    
    // Écouter les changements de langue depuis d'autres composants
    document.addEventListener('languageChanged', function(event) {
        if (event.detail && event.detail.locale && event.detail.source !== 'cookie-banner' && languageSelector) {
            // Mettre à jour le sélecteur de langue de la bannière
            if (languageSelector.value !== event.detail.locale) {
                languageSelector.value = event.detail.locale;
            }
        }
    });
    
    // Gestionnaire d'événements pour le bouton de personnalisation
    if (customizeBtn) {
        customizeBtn.addEventListener('click', function() {
            // Ouvrir la modal des préférences de cookies
            if (window.cookieManager && typeof window.cookieManager.showModal === 'function') {
                window.cookieManager.showModal();
            }
            
            // Cacher la bannière
            if (cookieBanner) {
                cookieBanner.classList.add('d-none');
            }
        });
    }
    
    // Gestionnaire d'événements pour le bouton des cookies nécessaires uniquement
    if (necessaryBtn) {
        necessaryBtn.addEventListener('click', function() {
            // Accepter uniquement les cookies nécessaires
            saveConsent({
                necessary: true,
                preferences: false,
                statistics: false,
                marketing: false,
                consentGiven: true
            });
            
            // Cacher la bannière
            if (cookieBanner) {
                cookieBanner.classList.add('d-none');
            }
        });
    }
    
    // Gestionnaire d'événements pour le bouton d'acceptation de tous les cookies
    if (acceptAllBtn) {
        acceptAllBtn.addEventListener('click', function() {
            // Accepter tous les cookies
            saveConsent({
                necessary: true,
                preferences: true,
                statistics: true,
                marketing: true,
                consentGiven: true
            });
            
            // Cacher la bannière
            if (cookieBanner) {
                cookieBanner.classList.add('d-none');
            }
        });
    }
    
    // Écouter les modifications de préférences depuis la modal
    document.addEventListener('cookiePreferencesUpdated', function(event) {
        // Cacher la bannière
        if (cookieBanner) {
            cookieBanner.classList.add('d-none');
        }
    });
    
    /**
     * Enregistre les préférences de cookies
     */
    function saveConsent(config) {
        // Enregistrer dans localStorage
        localStorage.setItem('cookieConsent', JSON.stringify(config));
        
        // Enregistrer dans un cookie pour le serveur
        document.cookie = `cookieConsent=${JSON.stringify(config)}; max-age=${180*24*60*60}; path=/; SameSite=Lax`;
        
        // Déclencher un événement pour informer les autres scripts
        const event = new CustomEvent('cookiePreferencesUpdated', {
            detail: config
        });
        document.dispatchEvent(event);
        
        // Mettre à jour la configuration dans le gestionnaire de cookies si disponible
        if (window.cookieManager && typeof window.cookieManager.setConfig === 'function') {
            window.cookieManager.setConfig(config);
        }
    }
});