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

    // Fonction pour créer la modal de consentement
    function createConsentModal() {
        const modal = document.createElement('div');
        modal.className = 'cookie-consent-modal';
        if (isCookieAccepted) {
            modal.classList.add('cookie-hidden');
        }

        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h4>${translations.cookieSettings}</h4>
                    <button type="button" class="cookie-modal-close">&times;</button>
                </div>
                <div class="cookie-modal-body">
                    <p>${translations.cookieText}</p>
                    
                    <div class="cookie-options">
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <span class="cookie-option-title">${translations.necessaryCookies}</span>
                                <label class="cookie-toggle">
                                    <input type="checkbox" data-type="necessary" checked disabled>
                                    <span class="cookie-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${translations.necessaryDescription}
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <span class="cookie-option-title">${translations.functionalCookies}</span>
                                <label class="cookie-toggle">
                                    <input type="checkbox" data-type="functional" ${cookieConfig.functional ? 'checked' : ''}>
                                    <span class="cookie-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${translations.functionalDescription}
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <span class="cookie-option-title">${translations.analyticsCookies}</span>
                                <label class="cookie-toggle">
                                    <input type="checkbox" data-type="analytics" ${cookieConfig.analytics ? 'checked' : ''}>
                                    <span class="cookie-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${translations.analyticsDescription}
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <span class="cookie-option-title">${translations.marketingCookies}</span>
                                <label class="cookie-toggle">
                                    <input type="checkbox" data-type="marketing" ${cookieConfig.marketing ? 'checked' : ''}>
                                    <span class="cookie-toggle-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${translations.marketingDescription}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <select class="cookie-language-selector">
                        <option value="fr">Français</option>
                        <option value="en">English</option>
                        <option value="nl">Nederlands</option>
                        <option value="de">Deutsch</option>
                    </select>
                    <div class="cookie-buttons">
                        <button class="cookie-button cookie-reject">${translations.reject}</button>
                        <button class="cookie-button cookie-save">${translations.saveSettings}</button>
                        <button class="cookie-button cookie-accept">${translations.accept}</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Sélectionner la langue actuelle dans le sélecteur
        const userLang = document.documentElement.lang || navigator.language || navigator.userLanguage;
        const langSelector = modal.querySelector('.cookie-language-selector');
        if (userLang.startsWith('en')) {
            langSelector.value = 'en';
        } else if (userLang.startsWith('nl')) {
            langSelector.value = 'nl';
        } else if (userLang.startsWith('de')) {
            langSelector.value = 'de';
        } else {
            langSelector.value = 'fr';
        }

        // Événements des boutons
        modal.querySelector('.cookie-accept').addEventListener('click', function() {
            acceptAllCookies();
            hideModal();
        });

        modal.querySelector('.cookie-reject').addEventListener('click', function() {
            rejectOptionalCookies();
            hideModal();
        });

        modal.querySelector('.cookie-save').addEventListener('click', function() {
            saveCookieSettings();
            hideModal();
        });
        
        modal.querySelector('.cookie-modal-close').addEventListener('click', function() {
            // Si modal fermée sans décision, sauvegarder pour ne pas la réafficher
            if (!cookieConfig.accepted) {
                saveCookieSettings();
            }
            hideModal();
        });
        
        // Événement pour le changement de langue
        langSelector.addEventListener('change', function() {
            updateLanguage(this.value);
        });
        
        // Configurer le lien dans le footer
        setupCookieSettingsLink();
        
        // Afficher la modal automatiquement si les cookies n'ont pas encore été acceptés
        if (!isCookieAccepted) {
            setTimeout(function() {
                showModal();
            }, 1000); // Délai court pour s'assurer que la page est chargée
        }
    }
    
    // Configuration du lien dans le footer
    function setupCookieSettingsLink() {
        const cookieSettingsLink = document.getElementById('cookie-settings-link');
        if (cookieSettingsLink) {
            cookieSettingsLink.addEventListener('click', function(e) {
                e.preventDefault();
                showModal();
            });
        }
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
        const modal = document.querySelector('.cookie-consent-modal');
        if (modal) {
            cookieConfig.functional = modal.querySelector('input[data-type="functional"]').checked;
            cookieConfig.analytics = modal.querySelector('input[data-type="analytics"]').checked;
            cookieConfig.marketing = modal.querySelector('input[data-type="marketing"]').checked;
            cookieConfig.accepted = true;
            
            localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
            
            // Déclencher un événement personnalisé pour informer que les cookies ont été mis à jour
            document.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: cookieConfig }));
        }
    }

    // Fonction pour afficher la modal
    function showModal() {
        const modal = document.querySelector('.cookie-consent-modal');
        if (modal) {
            modal.classList.remove('cookie-hidden');
            document.body.classList.add('cookie-modal-open');
        }
    }

    // Fonction pour masquer la modal
    function hideModal() {
        const modal = document.querySelector('.cookie-consent-modal');
        if (modal) {
            modal.classList.add('cookie-hidden');
            document.body.classList.remove('cookie-modal-open');
        }
    }

    // Traductions
    const translations = {
        // Français par défaut
        cookieText: "Nous utilisons des cookies pour améliorer votre expérience sur notre site. Vous pouvez choisir les cookies que vous acceptez.",
        accept: "Accepter tous",
        reject: "Refuser optionnels",
        saveSettings: "Enregistrer",
        cookieSettings: "Paramètres des cookies",
        necessaryCookies: "Cookies nécessaires",
        functionalCookies: "Cookies fonctionnels",
        analyticsCookies: "Cookies d'analyse",
        marketingCookies: "Cookies marketing",
        necessaryDescription: "Les cookies nécessaires permettent le bon fonctionnement du site. Ils ne peuvent pas être désactivés.",
        functionalDescription: "Les cookies fonctionnels permettent d'améliorer l'expérience utilisateur en mémorisant vos préférences.",
        analyticsDescription: "Les cookies d'analyse nous aident à comprendre comment les visiteurs interagissent avec le site.",
        marketingDescription: "Les cookies marketing sont utilisés pour vous proposer des publicités pertinentes."
    };

    // Fonction pour mettre à jour les traductions selon la langue
    function updateLanguage(langCode) {
        let newTranslations = {...translations};
        
        if (langCode === 'en') {
            newTranslations = {
                cookieText: "We use cookies to enhance your experience on our website. You can choose which cookies you accept.",
                accept: "Accept all",
                reject: "Reject optional",
                saveSettings: "Save",
                cookieSettings: "Cookie Settings",
                necessaryCookies: "Necessary cookies",
                functionalCookies: "Functional cookies",
                analyticsCookies: "Analytics cookies",
                marketingCookies: "Marketing cookies",
                necessaryDescription: "Necessary cookies enable the proper functioning of the website. They cannot be disabled.",
                functionalDescription: "Functional cookies enhance the user experience by remembering your preferences.",
                analyticsDescription: "Analytics cookies help us understand how visitors interact with the site.",
                marketingDescription: "Marketing cookies are used to provide you with relevant advertising."
            };
        } else if (langCode === 'nl') {
            newTranslations = {
                cookieText: "Wij gebruiken cookies om uw ervaring op onze website te verbeteren. U kunt kiezen welke cookies u accepteert.",
                accept: "Alle accepteren",
                reject: "Optionele weigeren",
                saveSettings: "Opslaan",
                cookieSettings: "Cookie-instellingen",
                necessaryCookies: "Noodzakelijke cookies",
                functionalCookies: "Functionele cookies",
                analyticsCookies: "Analytische cookies",
                marketingCookies: "Marketing cookies",
                necessaryDescription: "Noodzakelijke cookies maken de goede werking van de website mogelijk. Ze kunnen niet worden uitgeschakeld.",
                functionalDescription: "Functionele cookies verbeteren de gebruikerservaring door uw voorkeuren te onthouden.",
                analyticsDescription: "Analytische cookies helpen ons te begrijpen hoe bezoekers met de site interageren.",
                marketingDescription: "Marketing cookies worden gebruikt om u relevante advertenties aan te bieden."
            };
        } else if (langCode === 'de') {
            newTranslations = {
                cookieText: "Wir verwenden Cookies, um Ihre Erfahrung auf unserer Website zu verbessern. Sie können wählen, welche Cookies Sie akzeptieren.",
                accept: "Alle akzeptieren",
                reject: "Optionale ablehnen",
                saveSettings: "Speichern",
                cookieSettings: "Cookie-Einstellungen",
                necessaryCookies: "Notwendige Cookies",
                functionalCookies: "Funktionale Cookies",
                analyticsCookies: "Analyse-Cookies",
                marketingCookies: "Marketing-Cookies",
                necessaryDescription: "Notwendige Cookies ermöglichen die ordnungsgemäße Funktion der Website. Sie können nicht deaktiviert werden.",
                functionalDescription: "Funktionale Cookies verbessern die Benutzererfahrung, indem sie Ihre Präferenzen speichern.",
                analyticsDescription: "Analyse-Cookies helfen uns zu verstehen, wie Besucher mit der Website interagieren.",
                marketingDescription: "Marketing-Cookies werden verwendet, um Ihnen relevante Werbung anzubieten."
            };
        }
        
        // Mettre à jour les textes dans la modal
        Object.assign(translations, newTranslations);
        updateModalTexts();
    }
    
    // Mettre à jour les textes de la modal
    function updateModalTexts() {
        const modal = document.querySelector('.cookie-consent-modal');
        if (!modal) return;
        
        modal.querySelector('.cookie-modal-header h4').textContent = translations.cookieSettings;
        modal.querySelector('.cookie-modal-body p').textContent = translations.cookieText;
        
        // Options
        const options = modal.querySelectorAll('.cookie-option');
        options[0].querySelector('.cookie-option-title').textContent = translations.necessaryCookies;
        options[0].querySelector('.cookie-option-description').textContent = translations.necessaryDescription;
        
        options[1].querySelector('.cookie-option-title').textContent = translations.functionalCookies;
        options[1].querySelector('.cookie-option-description').textContent = translations.functionalDescription;
        
        options[2].querySelector('.cookie-option-title').textContent = translations.analyticsCookies;
        options[2].querySelector('.cookie-option-description').textContent = translations.analyticsDescription;
        
        options[3].querySelector('.cookie-option-title').textContent = translations.marketingCookies;
        options[3].querySelector('.cookie-option-description').textContent = translations.marketingDescription;
        
        // Boutons
        modal.querySelector('.cookie-accept').textContent = translations.accept;
        modal.querySelector('.cookie-reject').textContent = translations.reject;
        modal.querySelector('.cookie-save').textContent = translations.saveSettings;
    }

    // Initialisation
    createConsentModal();

    // Exposer les fonctions à l'extérieur
    window.cookieConsent = {
        showSettings: showModal,
        getConfig: function() {
            return {...cookieConfig};
        }
    };
});