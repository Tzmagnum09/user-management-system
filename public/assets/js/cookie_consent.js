document.addEventListener('DOMContentLoaded', function() {
    // Configuration des cookies par défaut
    const defaultCookieConfig = {
        necessary: true, // Toujours true, ne peut pas être désactivé
        functional: true,
        analytics: true,
        marketing: false,
        accepted: false
    };

    // Récupération des préférences de cookies stockées ou utilisation des valeurs par défaut
    let cookieConfig = JSON.parse(localStorage.getItem('cookieConfig')) || {...defaultCookieConfig};

    // Vérifier si le bandeau de cookies a déjà été accepté
    const isCookieAccepted = cookieConfig.accepted === true;

    // Fonction pour créer le bandeau de consentement
    function createConsentBanner() {
        const banner = document.createElement('div');
        banner.className = 'cookie-consent';
        if (isCookieAccepted) {
            banner.classList.add('cookie-consent-hidden');
        }

        banner.innerHTML = `
            <div class="cookie-consent-text">
                <p>${translations.cookieText}</p>
            </div>
            <div class="cookie-consent-buttons">
                <button class="cookie-settings-button">${translations.settings}</button>
                <button class="cookie-consent-reject">${translations.reject}</button>
                <button class="cookie-consent-accept">${translations.accept}</button>
            </div>
        `;

        document.body.appendChild(banner);

        // Événements des boutons
        banner.querySelector('.cookie-consent-accept').addEventListener('click', function() {
            acceptAllCookies();
            hideBanner();
        });

        banner.querySelector('.cookie-consent-reject').addEventListener('click', function() {
            rejectOptionalCookies();
            hideBanner();
        });

        banner.querySelector('.cookie-settings-button').addEventListener('click', function() {
            showSettingsModal();
        });
    }

    // Fonction pour créer la modal des paramètres de cookies
    function createSettingsModal() {
        const modal = document.createElement('div');
        modal.className = 'cookie-settings-modal';
        
        modal.innerHTML = `
            <div class="cookie-settings-modal-content">
                <div class="cookie-settings-header">
                    <h2>${translations.cookieSettings}</h2>
                    <span class="cookie-settings-close">&times;</span>
                </div>
                <div class="cookie-settings-body">
                    <div class="cookie-option">
                        <div class="cookie-option-header">
                            <div class="cookie-option-title">${translations.necessaryCookies}</div>
                            <label class="cookie-option-toggle">
                                <input type="checkbox" data-type="necessary" checked disabled>
                                <span class="cookie-option-slider"></span>
                            </label>
                        </div>
                        <div class="cookie-option-description">
                            ${translations.necessaryDescription}
                        </div>
                    </div>
                    
                    <div class="cookie-option">
                        <div class="cookie-option-header">
                            <div class="cookie-option-title">${translations.functionalCookies}</div>
                            <label class="cookie-option-toggle">
                                <input type="checkbox" data-type="functional" ${cookieConfig.functional ? 'checked' : ''}>
                                <span class="cookie-option-slider"></span>
                            </label>
                        </div>
                        <div class="cookie-option-description">
                            ${translations.functionalDescription}
                        </div>
                    </div>
                    
                    <div class="cookie-option">
                        <div class="cookie-option-header">
                            <div class="cookie-option-title">${translations.analyticsCookies}</div>
                            <label class="cookie-option-toggle">
                                <input type="checkbox" data-type="analytics" ${cookieConfig.analytics ? 'checked' : ''}>
                                <span class="cookie-option-slider"></span>
                            </label>
                        </div>
                        <div class="cookie-option-description">
                            ${translations.analyticsDescription}
                        </div>
                    </div>
                    
                    <div class="cookie-option">
                        <div class="cookie-option-header">
                            <div class="cookie-option-title">${translations.marketingCookies}</div>
                            <label class="cookie-option-toggle">
                                <input type="checkbox" data-type="marketing" ${cookieConfig.marketing ? 'checked' : ''}>
                                <span class="cookie-option-slider"></span>
                            </label>
                        </div>
                        <div class="cookie-option-description">
                            ${translations.marketingDescription}
                        </div>
                    </div>
                </div>
                <div class="cookie-settings-footer">
                    <button class="cookie-settings-save">${translations.saveSettings}</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Événements de la modal
        modal.querySelector('.cookie-settings-close').addEventListener('click', hideSettingsModal);
        
        modal.querySelector('.cookie-settings-save').addEventListener('click', function() {
            saveCookieSettings();
            hideSettingsModal();
            hideBanner();
        });
        
        // Fermer la modal en cliquant en dehors
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                hideSettingsModal();
            }
        });
    }

    // Fonction pour accepter tous les cookies
    function acceptAllCookies() {
        cookieConfig = {
            necessary: true,
            functional: true,
            analytics: true,
            marketing: true,
            accepted: true
        };
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Déclencher un événement personnalisé pour informer que les cookies ont été acceptés
        document.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: cookieConfig }));
    }

    // Fonction pour rejeter les cookies optionnels
    function rejectOptionalCookies() {
        cookieConfig = {
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
            accepted: true
        };
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Déclencher un événement personnalisé pour informer que les cookies ont été mis à jour
        document.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: cookieConfig }));
    }

    // Fonction pour sauvegarder les paramètres de cookies
    function saveCookieSettings() {
        const modal = document.querySelector('.cookie-settings-modal');
        
        cookieConfig.functional = modal.querySelector('input[data-type="functional"]').checked;
        cookieConfig.analytics = modal.querySelector('input[data-type="analytics"]').checked;
        cookieConfig.marketing = modal.querySelector('input[data-type="marketing"]').checked;
        cookieConfig.accepted = true;
        
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Déclencher un événement personnalisé pour informer que les cookies ont été mis à jour
        document.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: cookieConfig }));
    }

    // Fonction pour afficher la modal des paramètres
    function showSettingsModal() {
        const modal = document.querySelector('.cookie-settings-modal');
        modal.style.display = 'block';
    }

    // Fonction pour masquer la modal des paramètres
    function hideSettingsModal() {
        const modal = document.querySelector('.cookie-settings-modal');
        modal.style.display = 'none';
    }

    // Fonction pour masquer le bandeau
    function hideBanner() {
        const banner = document.querySelector('.cookie-consent');
        banner.classList.add('cookie-consent-hidden');
    }

    // Traductions
    const translations = {
        // Français par défaut
        cookieText: "Nous utilisons des cookies pour améliorer votre expérience sur notre site. Vous pouvez choisir les cookies que vous acceptez.",
        accept: "Accepter tous",
        reject: "Refuser optionnels",
        settings: "Paramètres",
        cookieSettings: "Paramètres des cookies",
        saveSettings: "Enregistrer mes préférences",
        necessaryCookies: "Cookies nécessaires",
        functionalCookies: "Cookies fonctionnels",
        analyticsCookies: "Cookies d'analyse",
        marketingCookies: "Cookies marketing",
        necessaryDescription: "Les cookies nécessaires permettent le bon fonctionnement du site. Ils ne peuvent pas être désactivés.",
        functionalDescription: "Les cookies fonctionnels permettent d'améliorer l'expérience utilisateur en mémorisant vos préférences.",
        analyticsDescription: "Les cookies d'analyse nous aident à comprendre comment les visiteurs interagissent avec le site.",
        marketingDescription: "Les cookies marketing sont utilisés pour vous proposer des publicités pertinentes."
    };

    // Détection de la langue du navigateur pour adapter les traductions
    function detectLanguage() {
        const userLang = document.documentElement.lang || navigator.language || navigator.userLanguage;
        
        if (userLang.startsWith('en')) {
            return updateTranslations({
                cookieText: "We use cookies to enhance your experience on our website. You can choose which cookies you accept.",
                accept: "Accept all",
                reject: "Reject optional",
                settings: "Settings",
                cookieSettings: "Cookie Settings",
                saveSettings: "Save my preferences",
                necessaryCookies: "Necessary cookies",
                functionalCookies: "Functional cookies",
                analyticsCookies: "Analytics cookies",
                marketingCookies: "Marketing cookies",
                necessaryDescription: "Necessary cookies enable the proper functioning of the website. They cannot be disabled.",
                functionalDescription: "Functional cookies enhance the user experience by remembering your preferences.",
                analyticsDescription: "Analytics cookies help us understand how visitors interact with the site.",
                marketingDescription: "Marketing cookies are used to provide you with relevant advertising."
            });
        } else if (userLang.startsWith('nl')) {
            return updateTranslations({
                cookieText: "Wij gebruiken cookies om uw ervaring op onze website te verbeteren. U kunt kiezen welke cookies u accepteert.",
                accept: "Alle accepteren",
                reject: "Optionele weigeren",
                settings: "Instellingen",
                cookieSettings: "Cookie-instellingen",
                saveSettings: "Mijn voorkeuren opslaan",
                necessaryCookies: "Noodzakelijke cookies",
                functionalCookies: "Functionele cookies",
                analyticsCookies: "Analytische cookies",
                marketingCookies: "Marketing cookies",
                necessaryDescription: "Noodzakelijke cookies maken de goede werking van de website mogelijk. Ze kunnen niet worden uitgeschakeld.",
                functionalDescription: "Functionele cookies verbeteren de gebruikerservaring door uw voorkeuren te onthouden.",
                analyticsDescription: "Analytische cookies helpen ons te begrijpen hoe bezoekers met de site interageren.",
                marketingDescription: "Marketing cookies worden gebruikt om u relevante advertenties aan te bieden."
            });
        } else if (userLang.startsWith('de')) {
            return updateTranslations({
                cookieText: "Wir verwenden Cookies, um Ihre Erfahrung auf unserer Website zu verbessern. Sie können wählen, welche Cookies Sie akzeptieren.",
                accept: "Alle akzeptieren",
                reject: "Optionale ablehnen",
                settings: "Einstellungen",
                cookieSettings: "Cookie-Einstellungen",
                saveSettings: "Meine Präferenzen speichern",
                necessaryCookies: "Notwendige Cookies",
                functionalCookies: "Funktionale Cookies",
                analyticsCookies: "Analyse-Cookies",
                marketingCookies: "Marketing-Cookies",
                necessaryDescription: "Notwendige Cookies ermöglichen die ordnungsgemäße Funktion der Website. Sie können nicht deaktiviert werden.",
                functionalDescription: "Funktionale Cookies verbessern die Benutzererfahrung, indem sie Ihre Präferenzen speichern.",
                analyticsDescription: "Analyse-Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren.",
                marketingDescription: "Marketing-Cookies werden verwendet, um Ihnen relevante Werbung anzubieten."
            });
        }
        
        // Par défaut, on garde les traductions françaises
        return translations;
    }

    // Mettre à jour les traductions
    function updateTranslations(newTranslations) {
        Object.assign(translations, newTranslations);
        return translations;
    }

    // Initialisation
    detectLanguage();
    createConsentBanner();
    createSettingsModal();

    // Exposer les fonctions à l'extérieur
    window.cookieConsent = {
        showSettings: showSettingsModal,
        getConfig: function() {
            return {...cookieConfig};
        }
    };
});