```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Configuration par défaut des cookies
    const defaultCookieConfig = {
        necessary: true, // Toujours obligatoire
        preferences: true,
        statistics: true,
        marketing: false,
        consentGiven: false
    };

    // Récupération des préférences stockées ou utilisation des valeurs par défaut
    let cookieConfig = getCookieConfig() || {...defaultCookieConfig};

    // Vérifier si le consentement a déjà été donné
    const isConsentGiven = cookieConfig.consentGiven === true;

    // Création de la modale
    createCookieModal();

    // Initialiser les événements de la modale
    initializeModalEvents();

    // Afficher la modale si le consentement n'a pas été donné
    if (!isConsentGiven) {
        setTimeout(function() {
            showCookieModal();
        }, 1000);
    }

    // Fonctions principales

    // Création de la structure HTML de la modale
    function createCookieModal() {
        const modal = document.createElement('div');
        modal.className = 'cookie-modal';
        modal.id = 'cookieModal';
        modal.style.display = 'none';

        // Contenu HTML de la modale
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h3>${translations.cookieSettings}</h3>
                    <div class="cookie-modal-header-controls">
                        <select class="cookie-language-selector">
                            <option value="fr" ${getCurrentLanguage() === 'fr' ? 'selected' : ''}>Français</option>
                            <option value="en" ${getCurrentLanguage() === 'en' ? 'selected' : ''}>English</option>
                            <option value="nl" ${getCurrentLanguage() === 'nl' ? 'selected' : ''}>Nederlands</option>
                            <option value="de" ${getCurrentLanguage() === 'de' ? 'selected' : ''}>Deutsch</option>
                        </select>
                        <button type="button" class="cookie-modal-close">&times;</button>
                    </div>
                </div>
                <div class="cookie-modal-body">
                    <p>${translations.cookieIntro}</p>
                    
                    <div class="cookie-tabs">
                        <div class="cookie-tab active" data-tab="overview">${translations.tabOverview}</div>
                        <div class="cookie-tab" data-tab="details">${translations.tabDetails}</div>
                        <div class="cookie-tab" data-tab="about">${translations.tabAbout}</div>
                    </div>
                    
                    <div class="cookie-tab-content active" id="tab-overview">
                        <!-- Vue d'ensemble -->
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="necessary">
                                <h4>${translations.necessaryCookies}</h4>
                                <label class="cookie-option-toggle">
                                    <input type="checkbox" data-type="necessary" checked disabled>
                                    <span class="cookie-option-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="preferences">
                                <h4>${translations.preferenceCookies}</h4>
                                <label class="cookie-option-toggle">
                                    <input type="checkbox" data-type="preferences" ${cookieConfig.preferences ? 'checked' : ''}>
                                    <span class="cookie-option-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="statistics">
                                <h4>${translations.statisticsCookies}</h4>
                                <label class="cookie-option-toggle">
                                    <input type="checkbox" data-type="statistics" ${cookieConfig.statistics ? 'checked' : ''}>
                                    <span class="cookie-option-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="marketing">
                                <h4>${translations.marketingCookies}</h4>
                                <label class="cookie-option-toggle">
                                    <input type="checkbox" data-type="marketing" ${cookieConfig.marketing ? 'checked' : ''}>
                                    <span class="cookie-option-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cookie-tab-content" id="tab-details">
                        <!-- Détails -->
                        <div class="cookie-option">
                            <div class="cookie-option-header expandable" data-option="necessary-details">
                                <h4>${translations.necessaryCookies}</h4>
                                <i class="fas fa-chevron-down cookie-expand-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="necessary-details-content">
                                <div class="cookie-option-description">
                                    ${translations.necessaryDescription}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${translations.cookieName}</th>
                                            <th>${translations.cookieProvider}</th>
                                            <th>${translations.cookiePurpose}</th>
                                            <th>${translations.cookieExpiry}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>PHPSESSID</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${translations.sessionPurpose}</td>
                                            <td>${translations.sessionEnd}</td>
                                        </tr>
                                        <tr>
                                            <td>cookieConsent</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${translations.consentPurpose}</td>
                                            <td>6 ${translations.months}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header expandable" data-option="preferences-details">
                                <h4>${translations.preferenceCookies}</h4>
                                <i class="fas fa-chevron-down cookie-expand-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="preferences-details-content">
                                <div class="cookie-option-description">
                                    ${translations.preferencesDescription}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${translations.cookieName}</th>
                                            <th>${translations.cookieProvider}</th>
                                            <th>${translations.cookiePurpose}</th>
                                            <th>${translations.cookieExpiry}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_locale</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${translations.languagePurpose}</td>
                                            <td>1 ${translations.year}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header expandable" data-option="statistics-details">
                                <h4>${translations.statisticsCookies}</h4>
                                <i class="fas fa-chevron-down cookie-expand-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="statistics-details-content">
                                <div class="cookie-option-description">
                                    ${translations.statisticsDescription}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${translations.cookieName}</th>
                                            <th>${translations.cookieProvider}</th>
                                            <th>${translations.cookiePurpose}</th>
                                            <th>${translations.cookieExpiry}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_ga</td>
                                            <td>Google</td>
                                            <td>${translations.analyticsPurpose}</td>
                                            <td>2 ${translations.years}</td>
                                        </tr>
                                        <tr>
                                            <td>_gid</td>
                                            <td>Google</td>
                                            <td>${translations.userIdPurpose}</td>
                                            <td>24 ${translations.hours}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header expandable" data-option="marketing-details">
                                <h4>${translations.marketingCookies}</h4>
                                <i class="fas fa-chevron-down cookie-expand-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="marketing-details-content">
                                <div class="cookie-option-description">
                                    ${translations.marketingDescription}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${translations.cookieName}</th>
                                            <th>${translations.cookieProvider}</th>
                                            <th>${translations.cookiePurpose}</th>
                                            <th>${translations.cookieExpiry}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_fbp</td>
                                            <td>Facebook</td>
                                            <td>${translations.facebookPurpose}</td>
                                            <td>3 ${translations.months}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cookie-tab-content" id="tab-about">
                        <!-- À propos -->
                        <h4>${translations.whatAreCookies}</h4>
                        <p>${translations.cookiesDefinition}</p>
                        
                        <h4>${translations.howWeUseCookies}</h4>
                        <p>${translations.cookiesUsage}</p>
                        
                        <h4>${translations.managingCookies}</h4>
                        <p>${translations.cookiesManagement}</p>
                        
                        <h4>${translations.moreInfo}</h4>
                        <p>${translations.moreInfoText} <a href="/terms" class="cookie-terms-link">${translations.termsLink}</a>.</p>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <button type="button" class="cookie-button cookie-button-necessary">${translations.necessaryOnly}</button>
                    <button type="button" class="cookie-button cookie-button-save">${translations.savePreferences}</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Ajouter le lien de paramètres des cookies dans le footer
        addCookieSettingsLink();
    }

    // Initialiser les événements de la modale
    function initializeModalEvents() {
        const modal = document.getElementById('cookieModal');
        if (!modal) return;

        // Événements pour les onglets
        const tabs = modal.querySelectorAll('.cookie-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Désactiver tous les onglets
                tabs.forEach(t => t.classList.remove('active'));
                
                // Activer l'onglet cliqué
                this.classList.add('active');
                
                // Masquer tous les contenus d'onglets
                const tabContents = modal.querySelectorAll('.cookie-tab-content');
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Afficher le contenu correspondant
                const tabId = 'tab-' + this.dataset.tab;
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Événements pour les sections expansibles
        const expandableHeaders = modal.querySelectorAll('.cookie-option-header.expandable');
        expandableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const contentId = this.dataset.option + '-content';
                const content = document.getElementById(contentId);
                const icon = this.querySelector('.cookie-expand-icon');
                
                // Toggle l'expansion
                content.classList.toggle('expanded');
                icon.classList.toggle('expanded');
            });
        });

        // Événement pour le bouton de fermeture
        const closeButton = modal.querySelector('.cookie-modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                hideCookieModal();
                
                // Si aucune décision n'a été prise, enregistrer les préférences par défaut
                if (!cookieConfig.consentGiven) {
                    savePreferences();
                }
            });
        }

        // Événements pour les boutons d'action
        const necessaryButton = modal.querySelector('.cookie-button-necessary');
        if (necessaryButton) {
            necessaryButton.addEventListener('click', function() {
                // Activer uniquement les cookies nécessaires
                cookieConfig.preferences = false;
                cookieConfig.statistics = false;
                cookieConfig.marketing = false;
                
                // Mettre à jour l'interface
                updateCheckboxes();
                
                // Sauvegarder et fermer
                savePreferences();
                hideCookieModal();
            });
        }

        const saveButton = modal.querySelector('.cookie-button-save');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                // Récupérer les valeurs des cases à cocher
                cookieConfig.preferences = modal.querySelector('input[data-type="preferences"]').checked;
                cookieConfig.statistics = modal.querySelector('input[data-type="statistics"]').checked;
                cookieConfig.marketing = modal.querySelector('input[data-type="marketing"]').checked;
                
                // Sauvegarder et fermer
                savePreferences();
                hideCookieModal();
            });
        }

        // Événement pour le sélecteur de langue
        const langSelector = modal.querySelector('.cookie-language-selector');
        if (langSelector) {
            langSelector.addEventListener('change', function() {
                changeLanguage(this.value);
            });
        }
    }

    // Ajouter un lien vers les paramètres des cookies dans le footer
    function addCookieSettingsLink() {
        const footerLink = document.getElementById('cookie-settings-link');
        if (footerLink) {
            footerLink.addEventListener('click', function(e) {
                e.preventDefault();
                showCookieModal();
            });
        }
    }

    // Sauvegarder les préférences
    function savePreferences() {
        cookieConfig.consentGiven = true;
        
        // Enregistrer dans le localStorage
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Enregistrer dans un cookie pour le serveur
        setCookie('cookieConsent', JSON.stringify(cookieConfig), 180); // 180 jours
        
        // Déclencher l'événement de mise à jour des cookies
        triggerCookieUpdateEvent();
    }

    // Mettre à jour les cases à cocher selon la configuration
    function updateCheckboxes() {
        const modal = document.getElementById('cookieModal');
        if (!modal) return;
        
        modal.querySelector('input[data-type="preferences"]').checked = cookieConfig.preferences;
        modal.querySelector('input[data-type="statistics"]').checked = cookieConfig.statistics;
        modal.querySelector('input[data-type="marketing"]').checked = cookieConfig.marketing;
    }

    // Afficher la modale
    function showCookieModal() {
        const modal = document.getElementById('cookieModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Empêcher le défilement
        }
    }

    // Masquer la modale
    function hideCookieModal() {
        const modal = document.getElementById('cookieModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = ''; // Rétablir le défilement
        }
    }

    // Obtenir la configuration des cookies
    function getCookieConfig() {
        const storedConfig = localStorage.getItem('cookieConfig');
        return storedConfig ? JSON.parse(storedConfig) : null;
    }

    // Définir un cookie
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    // Déclencher un événement personnalisé pour informer de la mise à jour des cookies
    function triggerCookieUpdateEvent() {
        const event = new CustomEvent('cookiePreferencesUpdated', {
            detail: cookieConfig
        });
        document.dispatchEvent(event);
    }

    // Obtenir la langue actuelle
    function getCurrentLanguage() {
        // Essayer d'obtenir la langue à partir de l'attribut lang de l'HTML
        let lang = document.documentElement.lang;
        
        // Si non défini, essayer d'obtenir à partir de l'URL ou du navigateur
        if (!lang) {
            // Rechercher un pattern comme /fr/ ou /en/ dans l'URL
            const match = window.location.pathname.match(/\/(fr|en|nl|de)\//);
            if (match) {
                lang = match[1];
            } else {
                // Utiliser la langue du navigateur
                lang = navigator.language || navigator.userLanguage;
                lang = lang.substring(0, 2); // Prendre uniquement les deux premiers caractères
            }
        }
        
        // Simplifier la langue (fr-FR -> fr)
        if (lang.includes('-')) {
            lang = lang.split('-')[0];
        }
        
        // Vérifier si la langue est supportée, sinon utiliser le français
        const supportedLangs = ['fr', 'en', 'nl', 'de'];
        return supportedLangs.includes(lang) ? lang : 'fr';
    }

    // Changer la langue de la modale
    function changeLanguage(langCode) {
        const currentLang = translations._currentLang;
        
        // Si la langue est la même, ne rien faire
        if (currentLang === langCode) return;
        
        // Mettre à jour les traductions
        translations._currentLang = langCode;
        
        // Mettre à jour le contenu de la modale
        updateModalTexts();
    }

    // Mettre à jour les textes de la modale
    function updateModalTexts() {
        const modal = document.getElementById('cookieModal');
        if (!modal) return;
        
        // Titre et introduction
        modal.querySelector('.cookie-modal-header h3').textContent = translations.cookieSettings;
        modal.querySelector('.cookie-modal-body > p').textContent = translations.cookieIntro;
        
        // Onglets
        const tabs = modal.querySelectorAll('.cookie-tab');
        tabs[0].textContent = translations.tabOverview;
        tabs[1].textContent = translations.tabDetails;
        tabs[2].textContent = translations.tabAbout;
        
        // Vue d'ensemble
        const optionHeaders = modal.querySelectorAll('#tab-overview .cookie-option-header h4');
        optionHeaders[0].textContent = translations.necessaryCookies;
        optionHeaders[1].textContent = translations.preferenceCookies;
        optionHeaders[2].textContent = translations.statisticsCookies;
        optionHeaders[3].textContent = translations.marketingCookies;
        
        // Détails
        const detailHeaders = modal.querySelectorAll('#tab-details .cookie-option-header h4');
        detailHeaders[0].textContent = translations.necessaryCookies;
        detailHeaders[1].textContent = translations.preferenceCookies;
        detailHeaders[2].textContent = translations.statisticsCookies;
        detailHeaders[3].textContent = translations.marketingCookies;
        
        // Descriptions
        modal.querySelector('#necessary-details-content .cookie-option-description').textContent = translations.necessaryDescription;
        modal.querySelector('#preferences-details-content .cookie-option-description').textContent = translations.preferencesDescription;
        modal.querySelector('#statistics-details-content .cookie-option-description').textContent = translations.statisticsDescription;
        modal.querySelector('#marketing-details-content .cookie-option-description').textContent = translations.marketingDescription;
        
        // En-têtes de tableaux
        const tableHeaders = modal.querySelectorAll('.cookie-table thead tr');
        tableHeaders.forEach(header => {
            const cells = header.querySelectorAll('th');
            cells[0].textContent = translations.cookieName;
            cells[1].textContent = translations.cookieProvider;
            cells[2].textContent = translations.cookiePurpose;
            cells[3].textContent = translations.cookieExpiry;
        });
        
        // À propos
        const aboutContent = modal.querySelector('#tab-about');
        const aboutHeadings = aboutContent.querySelectorAll('h4');
        aboutHeadings[0].textContent = translations.whatAreCookies;
        aboutHeadings[1].textContent = translations.howWeUseCookies;
        aboutHeadings[2].textContent = translations.managingCookies;
        aboutHeadings[3].textContent = translations.moreInfo;
        
        const aboutParagraphs = aboutContent.querySelectorAll('p');
        aboutParagraphs[0].textContent = translations.cookiesDefinition;
        aboutParagraphs[1].textContent = translations.cookiesUsage;
        aboutParagraphs[2].textContent = translations.cookiesManagement;
        
        // Le dernier paragraphe contient un lien
        const linkText = aboutParagraphs[3].querySelector('a');
        aboutParagraphs[3].textContent = translations.moreInfoText + ' ';
        aboutParagraphs[3].appendChild(linkText);
        linkText.textContent = translations.termsLink;
        
        // Boutons
        modal.querySelector('.cookie-button-necessary').textContent = translations.necessaryOnly;
        modal.querySelector('.cookie-button-save').textContent = translations.savePreferences;
    }

    // Traductions
    const translations = {
        _currentLang: getCurrentLanguage(),
        
        // Français
        fr: {
            cookieSettings: 'Préférences de cookies',
            cookieIntro: 'En tant que visiteur de notre site, nous essayons de vous offrir une expérience aussi agréable que possible. Nous utilisons des cookies en premier lieu pour améliorer votre expérience utilisateur et pour améliorer le fonctionnement de nos services en ligne. En outre, nous utilisons des cookies pour rendre le contenu de nos sites web et applications (mobiles) plus intéressant pour vous. Nous utilisons également des cookies pour cartographier votre comportement de navigation.',
            tabOverview: 'Vue d\'ensemble',
            tabDetails: 'Détails',
            tabAbout: 'À propos',
            necessaryCookies: 'Cookies nécessaires',
            preferenceCookies: 'Cookies de préférences',
            statisticsCookies: 'Cookies statistiques',
            marketingCookies: 'Cookies marketing',
            necessaryDescription: 'Ces cookies sont essentiels pour le fonctionnement de base du site web et ne peuvent pas être désactivés.',
            preferencesDescription: 'Ces cookies permettent de mémoriser vos choix et préférences, comme la langue préférée ou la région où vous vous trouvez.',
            statisticsDescription: 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site web en recueillant et rapportant des informations de manière anonyme.',
            marketingDescription: 'Ces cookies sont utilisés pour suivre les visiteurs sur les sites web. Le but est d\'afficher des publicités qui sont pertinentes et intéressantes pour l\'utilisateur individuel et donc plus précieuses pour les éditeurs et annonceurs tiers.',
            cookieName: 'Nom',
            cookieProvider: 'Fournisseur',
            cookiePurpose: 'Finalité',
            cookieExpiry: 'Expiration',
            sessionPurpose: 'Maintien de la session utilisateur',
            consentPurpose: 'Stockage des préférences de consentement aux cookies',
            sessionEnd: 'Fin de session',
            months: 'mois',
            year: 'an',
            years: 'ans',
            hours: 'heures',
            languagePurpose: 'Stockage de la préférence de langue',
            analyticsPurpose: 'Suivi du comportement des utilisateurs sur le site pour améliorer l\'expérience',
            userIdPurpose: 'Distinction des utilisateurs',
            facebookPurpose: 'Suivi des conversions publicitaires et retargeting',
            whatAreCookies: 'Que sont les cookies ?',
            cookiesDefinition: 'Les cookies sont de petits fichiers texte qui sont stockés sur votre ordinateur ou appareil mobile lorsque vous visitez un site web. Ils permettent au site de mémoriser vos actions et préférences pendant une période déterminée, afin que vous n\'ayez pas à les ressaisir à chaque fois que vous visitez le site ou naviguez d\'une page à une autre.',
            howWeUseCookies: 'Comment utilisons-nous les cookies ?',
            cookiesUsage: 'Nous utilisons différents types de cookies pour faire fonctionner notre site, améliorer votre expérience utilisateur, analyser comment le site est utilisé et personnaliser le contenu et les publicités que vous voyez.',
            managingCookies: 'Comment gérer les cookies ?',
            cookiesManagement: 'Vous pouvez gérer vos préférences de cookies à tout moment en cliquant sur le lien "Paramètres des cookies" en bas de notre site. Vous pouvez également modifier les paramètres de votre navigateur pour supprimer ou empêcher certains cookies d\'être stockés sur votre ordinateur ou appareil mobile sans votre consentement explicite.',
            moreInfo: 'Plus d\'informations',
            moreInfoText: 'Pour en savoir plus sur notre utilisation des cookies, veuillez consulter notre politique de confidentialité et nos conditions d\'utilisation sur',
            termsLink: 'notre page des conditions d\'utilisation',
            necessaryOnly: 'Seulement nécessaires',
            savePreferences: 'Enregistrer les préférences'
        },
        
        // Anglais
        en: {
            cookieSettings: 'Cookie settings',
            cookieIntro: 'As a visitor to our site, we try to offer you an experience as pleasant as possible. We use cookies primarily to improve your user experience and to improve the functioning of our online services. In addition, we use cookies to make the content of our websites and (mobile) applications more interesting for you. We also use cookies to map your browsing behavior.',
            tabOverview: 'Overview',
            tabDetails: 'Details',
            tabAbout: 'About',
            necessaryCookies: 'Necessary cookies',
            preferenceCookies: 'Preference cookies',
            statisticsCookies: 'Statistics cookies',
            marketingCookies: 'Marketing cookies',
            necessaryDescription: 'These cookies are essential for the basic functioning of the website and cannot be disabled.',
            preferencesDescription: 'These cookies allow us to remember your choices and preferences, such as language preference or the region where you are located.',
            statisticsDescription: 'These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
            marketingDescription: 'These cookies are used to track visitors across websites. The aim is to display ads that are relevant and engaging for the individual user and thereby more valuable for publishers and third party advertisers.',
            cookieName: 'Name',
            cookieProvider: 'Provider',
            cookiePurpose: 'Purpose',
            cookieExpiry: 'Expiry',
            sessionPurpose: 'Maintains user session',
            consentPurpose: 'Stores cookie consent preferences',
            sessionEnd: 'Session end',
            months: 'months',
            year: 'year',
            years: 'years',
            hours: 'hours',
            languagePurpose: 'Stores language preference',
            analyticsPurpose: 'Tracks user behavior on the site to improve experience',
            userIdPurpose: 'Distinguishes users',
            facebookPurpose: 'Tracks ad conversions and retargeting',
            whatAreCookies: 'What are cookies?',
            cookiesDefinition: 'Cookies are small text files that are stored on your computer or mobile device when you visit a website. They allow the site to remember your actions and preferences for a period of time, so you don\'t have to re-enter them every time you visit the site or navigate from one page to another.',
            howWeUseCookies: 'How do we use cookies?',
            cookiesUsage: 'We use different types of cookies to run our website, improve your user experience, analyze how the site is used, and personalize the content and advertisements you see.',
            managingCookies: 'How to manage cookies?',
            cookiesManagement: 'You can manage your cookie preferences at any time by clicking on the "Cookie settings" link at the bottom of our site. You can also change your browser settings to delete or prevent certain cookies from being stored on your computer or mobile device without your explicit consent.',
            moreInfo: 'More information',
            moreInfoText: 'To learn more about our use of cookies, please consult our privacy policy and terms of use on',
            termsLink: 'our terms of use page',
            necessaryOnly: 'Necessary only',
            savePreferences: 'Save preferences'
        },
        
        // Néerlandais
        nl: {
            cookieSettings: 'Cookie-instellingen',
            cookieIntro: 'Als bezoeker van onze site proberen we u een zo aangenaam mogelijke ervaring te bieden. We gebruiken cookies in de eerste plaats om uw gebruikerservaring te verbeteren en om de werking van onze online diensten te verbeteren. Daarnaast gebruiken we cookies om de inhoud van onze websites en (mobiele) applicaties interessanter voor u te maken. We gebruiken ook cookies om uw surfgedrag in kaart te brengen.',
            tabOverview: 'Overzicht',
            tabDetails: 'Details',
            tabAbout: 'Over',
            necessaryCookies: 'Noodzakelijke cookies',
            preferenceCookies: 'Voorkeurscookies',
            statisticsCookies: 'Statistische cookies',
            marketingCookies: 'Marketing cookies',
            necessaryDescription: 'Deze cookies zijn essentieel voor de basisfunctionaliteit van de website en kunnen niet worden uitgeschakeld.',
            preferencesDescription: 'Deze cookies stellen ons in staat om uw keuzes en voorkeuren te onthouden, zoals taalvoorkeur of de regio waar u zich bevindt.',
            statisticsDescription: 'Deze cookies helpen ons te begrijpen hoe bezoekers met onze website interageren door informatie anoniem te verzamelen en te rapporteren.',
            marketingDescription: 'Deze cookies worden gebruikt om bezoekers op websites te volgen. Het doel is om advertenties weer te geven die relevant en aantrekkelijk zijn voor de individuele gebruiker en daardoor waardevoller voor uitgevers en externe adverteerders.',
            cookieName: 'Naam',
            cookieProvider: 'Aanbieder',
            cookiePurpose: 'Doel',
            cookieExpiry: 'Vervaldatum',
            sessionPurpose: 'Behoudt gebruikerssessie',
            consentPurpose: 'Slaat cookie-toestemmingsvoorkeuren op',
            sessionEnd: 'Einde sessie',
            months: 'maanden',
            year: 'jaar',
            years: 'jaar',
            hours: 'uur',
            languagePurpose: 'Slaat taalvoorkeur op',
            analyticsPurpose: 'Volgt gebruikersgedrag op de site om de ervaring te verbeteren',
            userIdPurpose: 'Onderscheidt gebruikers',
            facebookPurpose: 'Volgt advertentieconversies en retargeting',
            whatAreCookies: 'Wat zijn cookies?',
            cookiesDefinition: 'Cookies zijn kleine tekstbestanden die op uw computer of mobiele apparaat worden opgeslagen wanneer u een website bezoekt. Ze stellen de site in staat om uw acties en voorkeuren gedurende een bepaalde periode te onthouden, zodat u ze niet opnieuw hoeft in te voeren elke keer dat u de site bezoekt of van de ene pagina naar de andere navigeert.',
            howWeUseCookies: 'Hoe gebruiken we cookies?',
            cookiesUsage: 'We gebruiken verschillende soorten cookies om onze website te laten werken, uw gebruikerservaring te verbeteren, te analyseren hoe de site wordt gebruikt en de inhoud en advertenties die u ziet te personaliseren.',
            managingCookies: 'Hoe cookies beheren?',
            cookiesManagement: 'U kunt uw cookievoorkeuren op elk moment beheren door op de link "Cookie-instellingen" onderaan onze site te klikken. U kunt ook de instellingen van uw browser wijzigen om bepaalde cookies te verwijderen of te voorkomen dat ze op uw computer of mobiele apparaat worden opgeslagen zonder uw uitdrukkelijke toestemming.',
            moreInfo: 'Meer informatie',
            moreInfoText: 'Voor meer informatie over ons gebruik van cookies, raadpleeg ons privacybeleid en gebruiksvoorwaarden op',
            termsLink: 'onze pagina met gebruiksvoorwaarden',
            necessaryOnly: 'Alleen noodzakelijke',
            savePreferences: 'Voorkeuren opslaan'
        },
        
        // Allemand
        de: {
            cookieSettings: 'Cookie-Einstellungen',
            cookieIntro: 'Als Besucher unserer Website versuchen wir, Ihnen ein möglichst angenehmes Erlebnis zu bieten. Wir verwenden Cookies in erster Linie, um Ihre Benutzererfahrung zu verbessern und die Funktionsweise unserer Online-Dienste zu optimieren. Darüber hinaus verwenden wir Cookies, um die Inhalte unserer Websites und (mobilen) Anwendungen für Sie interessanter zu gestalten. Wir verwenden auch Cookies, um Ihr Surfverhalten zu erfassen.',
            tabOverview: 'Übersicht',
            tabDetails: 'Details',
            tabAbout: 'Über',
            necessaryCookies: 'Notwendige Cookies',
            preferenceCookies: 'Präferenz-Cookies',
            statisticsCookies: 'Statistik-Cookies',
            marketingCookies: 'Marketing-Cookies',
            necessaryDescription: 'Diese Cookies sind für die grundlegende Funktionalität der Website unerlässlich und können nicht deaktiviert werden.',
            preferencesDescription: 'Diese Cookies ermöglichen es uns, Ihre Auswahl und Präferenzen zu speichern, wie z.B. Spracheinstellungen oder die Region, in der Sie sich befinden.',
            statisticsDescription: 'Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren, indem sie Informationen anonym sammeln und melden.',
            marketingDescription: 'Diese Cookies werden verwendet, um Besucher auf Websites zu verfolgen. Ziel ist es, Anzeigen zu schalten, die für den einzelnen Nutzer relevant und ansprechend sind und damit für Verlage und Drittanbieter von Werbung wertvoller sind.',
            cookieName: 'Name',
            cookieProvider: 'Anbieter',
            cookiePurpose: 'Zweck',
            cookieExpiry: 'Ablauf',
            sessionPurpose: 'Aufrechterhaltung der Benutzersitzung',
            consentPurpose: 'Speichert Cookie-Einwilligungspräferenzen',
            sessionEnd: 'Sitzungsende',
            months: 'Monate',
            year: 'Jahr',
            years: 'Jahre',
            hours: 'Stunden',
            languagePurpose: 'Speichert Sprachpräferenz',
            analyticsPurpose: 'Verfolgt das Nutzerverhalten auf der Website, um das Erlebnis zu verbessern',
            userIdPurpose: 'Unterscheidet Benutzer',
            facebookPurpose: 'Verfolgt Werbekonversionen und Retargeting',
            whatAreCookies: 'Was sind Cookies?',
            cookiesDefinition: 'Cookies sind kleine Textdateien, die auf Ihrem Computer oder Mobilgerät gespeichert werden, wenn Sie eine Website besuchen. Sie ermöglichen es der Website, Ihre Aktionen und Präferenzen für einen bestimmten Zeitraum zu speichern, so dass Sie diese nicht jedes Mal erneut eingeben müssen, wenn Sie die Website besuchen oder von einer Seite zur anderen navigieren.',
            howWeUseCookies: 'Wie verwenden wir Cookies?',
            cookiesUsage: 'Wir verwenden verschiedene Arten von Cookies, um unsere Website zu betreiben, Ihre Benutzererfahrung zu verbessern, zu analysieren, wie die Website genutzt wird, und die Inhalte und Werbung, die Sie sehen, zu personalisieren.',
            managingCookies: 'Wie verwaltet man Cookies?',
            cookiesManagement: 'Sie können Ihre Cookie-Einstellungen jederzeit verwalten, indem Sie auf den Link "Cookie-Einstellungen" am unteren Rand unserer Website klicken. Sie können auch die Einstellungen Ihres Browsers ändern, um bestimmte Cookies zu löschen oder zu verhindern, dass sie ohne Ihre ausdrückliche Zustimmung auf Ihrem Computer oder Mobilgerät gespeichert werden.',
            moreInfo: 'Weitere Informationen',
            moreInfoText: 'Für weitere Informationen über unsere Verwendung von Cookies, konsultieren Sie bitte unsere Datenschutzrichtlinie und Nutzungsbedingungen auf',
            termsLink: 'unserer Seite mit Nutzungsbedingungen',
            necessaryOnly: 'Nur notwendige',
            savePreferences: 'Einstellungen speichern'
        },
        
        // Fonction pour obtenir une traduction
        get cookieSettings() { return this[this._currentLang].cookieSettings; },
        get cookieIntro() { return this[this._currentLang].cookieIntro; },
        get tabOverview() { return this[this._currentLang].tabOverview; },
        get tabDetails() { return this[this._currentLang].tabDetails; },
        get tabAbout() { return this[this._currentLang].tabAbout; },
        get necessaryCookies() { return this[this._currentLang].necessaryCookies; },
        get preferenceCookies() { return this[this._currentLang].preferenceCookies; },
        get statisticsCookies() { return this[this._currentLang].statisticsCookies; },
        get marketingCookies() { return this[this._currentLang].marketingCookies; },
        get necessaryDescription() { return this[this._currentLang].necessaryDescription; },
        get preferencesDescription() { return this[this._currentLang].preferencesDescription; },
        get statisticsDescription() { return this[this._currentLang].statisticsDescription; },
        get marketingDescription() { return this[this._currentLang].marketingDescription; },
        get cookieName() { return this[this._currentLang].cookieName; },
        get cookieProvider() { return this[this._currentLang].cookieProvider; },
        get cookiePurpose() { return this[this._currentLang].cookiePurpose; },
        get cookieExpiry() { return this[this._currentLang].cookieExpiry; },
        get sessionPurpose() { return this[this._currentLang].sessionPurpose; },
        get consentPurpose() { return this[this._currentLang].consentPurpose; },
        get sessionEnd() { return this[this._currentLang].sessionEnd; },
        get months() { return this[this._currentLang].months; },
        get year() { return this[this._currentLang].year; },
        get years() { return this[this._currentLang].years; },
        get hours() { return this[this._currentLang].hours; },
        get languagePurpose() { return this[this._currentLang].languagePurpose; },
        get analyticsPurpose() { return this[this._currentLang].analyticsPurpose; },
        get userIdPurpose() { return this[this._currentLang].userIdPurpose; },
        get facebookPurpose() { return this[this._currentLang].facebookPurpose; },
        get whatAreCookies() { return this[this._currentLang].whatAreCookies; },
        get cookiesDefinition() { return this[this._currentLang].cookiesDefinition; },
        get howWeUseCookies() { return this[this._currentLang].howWeUseCookies; },
        get cookiesUsage() { return this[this._currentLang].cookiesUsage; },
        get managingCookies() { return this[this._currentLang].managingCookies; },
        get cookiesManagement() { return this[this._currentLang].cookiesManagement; },
        get moreInfo() { return this[this._currentLang].moreInfo; },
        get moreInfoText() { return this[this._currentLang].moreInfoText; },
        get termsLink() { return this[this._currentLang].termsLink; },
        get necessaryOnly() { return this[this._currentLang].necessaryOnly; },
        get savePreferences() { return this[this._currentLang].savePreferences; }
    };

    // Exposer l'API pour d'autres scripts
    window.cookieConsent = {
        showModal: showCookieModal,
        getConfig: function() {
            return {...cookieConfig};
        },
        updateConfig: function(newConfig) {
            cookieConfig = {...cookieConfig, ...newConfig};
            updateCheckboxes();
        }
    };
});
