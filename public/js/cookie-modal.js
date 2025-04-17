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

    // Créer la modal si elle n'existe pas déjà
    if (!document.getElementById('cookieModal')) {
        createCookieModal();
    }
    
    // Initialiser les événements
    initializeEvents();
    
    // Afficher automatiquement la modal si le consentement n'a pas encore été donné
    if (!cookieConfig.consentGiven) {
        setTimeout(function() {
            showCookieModal();
        }, 1000); // Délai court pour s'assurer que la page est chargée
    }

    /**
     * Crée la structure HTML de la modal de cookies
     */
    function createCookieModal() {
        const modal = document.createElement('div');
        modal.id = 'cookieModal';
        modal.className = 'cookie-modal';
        
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h4>${getTranslation('cookiePreferences')}</h4>
                    <button class="btn-close">&times;</button>
                </div>
                <div class="cookie-modal-body">
                    <div class="cookie-tabs">
                        <button class="cookie-tab active" data-tab="overview">${getTranslation('tabOverview')}</button>
                        <button class="cookie-tab" data-tab="details">${getTranslation('tabDetails')}</button>
                        <button class="cookie-tab" data-tab="terms">${getTranslation('tabTerms')}</button>
                        <button class="cookie-tab" data-tab="about">${getTranslation('tabAbout')}</button>
                    </div>
                    
                    <!-- Onglet Vue d'ensemble -->
                    <div class="cookie-tab-content active" id="tab-overview">
                        <div class="form-check form-switch">
                            <label class="form-check-label" for="cookie-necessary">
                                ${getTranslation('necessaryCookies')}
                                <i class="fas fa-info-circle cookie-info-icon" title="${getTranslation('necessaryDescription')}"></i>
                            </label>
                            <input class="form-check-input" type="checkbox" id="cookie-necessary" checked disabled data-type="necessary">
                        </div>
                        
                        <div class="form-check form-switch">
                            <label class="form-check-label" for="cookie-preferences">
                                ${getTranslation('preferencesCookies')}
                                <i class="fas fa-info-circle cookie-info-icon" title="${getTranslation('preferencesDescription')}"></i>
                            </label>
                            <input class="form-check-input" type="checkbox" id="cookie-preferences" ${cookieConfig.preferences ? 'checked' : ''} data-type="preferences">
                        </div>
                        
                        <div class="form-check form-switch">
                            <label class="form-check-label" for="cookie-statistics">
                                ${getTranslation('statisticsCookies')}
                                <i class="fas fa-info-circle cookie-info-icon" title="${getTranslation('statisticsDescription')}"></i>
                            </label>
                            <input class="form-check-input" type="checkbox" id="cookie-statistics" ${cookieConfig.statistics ? 'checked' : ''} data-type="statistics">
                        </div>
                        
                        <div class="form-check form-switch">
                            <label class="form-check-label" for="cookie-marketing">
                                ${getTranslation('marketingCookies')}
                                <i class="fas fa-info-circle cookie-info-icon" title="${getTranslation('marketingDescription')}"></i>
                            </label>
                            <input class="form-check-input" type="checkbox" id="cookie-marketing" ${cookieConfig.marketing ? 'checked' : ''} data-type="marketing">
                        </div>
                    </div>
                    
                    <!-- Onglet Détails -->
                    <div class="cookie-tab-content" id="tab-details">
                        <div class="cookie-option cookie-option-expandable" data-option="necessary">
                            <div class="cookie-option-header">
                                <h5>${getTranslation('necessaryCookies')}</h5>
                                <i class="fas fa-chevron-down cookie-chevron up"></i>
                            </div>
                            <div class="cookie-details-content expanded">
                                <p>${getTranslation('necessaryDetailsDescription')}</p>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName')}</th>
                                            <th>${getTranslation('cookieProvider')}</th>
                                            <th>${getTranslation('cookiePurpose')}</th>
                                            <th>${getTranslation('cookieExpiry')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>PHPSESSID</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${getTranslation('sessionPurpose')}</td>
                                            <td>${getTranslation('sessionEnd')}</td>
                                        </tr>
                                        <tr>
                                            <td>cookieConsent</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${getTranslation('consentPurpose')}</td>
                                            <td>6 ${getTranslation('months')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-option cookie-option-expandable" data-option="preferences">
                            <div class="cookie-option-header">
                                <h5>${getTranslation('preferencesCookies')}</h5>
                                <i class="fas fa-chevron-down cookie-chevron"></i>
                            </div>
                            <div class="cookie-details-content">
                                <p>${getTranslation('preferencesDetailsDescription')}</p>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName')}</th>
                                            <th>${getTranslation('cookieProvider')}</th>
                                            <th>${getTranslation('cookiePurpose')}</th>
                                            <th>${getTranslation('cookieExpiry')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_locale</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${getTranslation('languagePurpose')}</td>
                                            <td>1 ${getTranslation('year')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-option cookie-option-expandable" data-option="statistics">
                            <div class="cookie-option-header">
                                <h5>${getTranslation('statisticsCookies')}</h5>
                                <i class="fas fa-chevron-down cookie-chevron"></i>
                            </div>
                            <div class="cookie-details-content">
                                <p>${getTranslation('statisticsDetailsDescription')}</p>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName')}</th>
                                            <th>${getTranslation('cookieProvider')}</th>
                                            <th>${getTranslation('cookiePurpose')}</th>
                                            <th>${getTranslation('cookieExpiry')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_ga</td>
                                            <td>Google</td>
                                            <td>${getTranslation('analyticsPurpose')}</td>
                                            <td>2 ${getTranslation('years')}</td>
                                        </tr>
                                        <tr>
                                            <td>_gid</td>
                                            <td>Google</td>
                                            <td>${getTranslation('userIdPurpose')}</td>
                                            <td>24 ${getTranslation('hours')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-option cookie-option-expandable" data-option="marketing">
                            <div class="cookie-option-header">
                                <h5>${getTranslation('marketingCookies')}</h5>
                                <i class="fas fa-chevron-down cookie-chevron"></i>
                            </div>
                            <div class="cookie-details-content">
                                <p>${getTranslation('marketingDetailsDescription')}</p>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName')}</th>
                                            <th>${getTranslation('cookieProvider')}</th>
                                            <th>${getTranslation('cookiePurpose')}</th>
                                            <th>${getTranslation('cookieExpiry')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_fbp</td>
                                            <td>Facebook</td>
                                            <td>${getTranslation('facebookPurpose')}</td>
                                            <td>3 ${getTranslation('months')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Conditions d'utilisation -->
                    <div class="cookie-tab-content" id="tab-terms">
                        <h4>1. Introduction</h4>
                        <p>Bienvenue sur DMQode.be. Les présentes conditions d'utilisation régissent votre utilisation de notre site web et de nos services. En vous inscrivant sur notre site, vous acceptez d'être lié par ces conditions. Veuillez les lire attentivement.</p>
                        
                        <h4>2. Définitions</h4>
                        <ul>
                            <li><strong>'Service'</strong> désigne le site web DMQode.be</li>
                            <li><strong>'Utilisateur'</strong> désigne toute personne qui accède au Service et crée un compte</li>
                            <li><strong>'Compte'</strong> désigne l'accès personnalisé au Service</li>
                        </ul>
                        
                        <h4>3. Inscription et compte</h4>
                        <p>Pour utiliser certaines fonctionnalités du Service, vous devez créer un compte. Lors de l'inscription, vous acceptez de fournir des informations exactes, à jour et complètes. Vous êtes responsable de la confidentialité de votre mot de passe et de toutes les activités qui se produisent sous votre compte.</p>
                        <p>Après votre inscription, vous devrez vérifier votre adresse e-mail. Ensuite, un administrateur devra approuver votre compte avant que vous puissiez accéder à toutes les fonctionnalités du service.</p>
                        
                        <h4>4. Droits et responsabilités</h4>
                        <p>En utilisant notre Service, vous acceptez de ne pas :</p>
                        <ul>
                            <li>Violer les lois applicables</li>
                            <li>Distribuer des virus ou tout autre code malveillant</li>
                            <li>Perturber ou interférer avec la sécurité du Service</li>
                            <li>Recueillir des informations sur d'autres utilisateurs sans leur consentement</li>
                            <li>Utiliser le Service d'une manière qui pourrait endommager, désactiver, surcharger ou altérer le Service</li>
                        </ul>
                        
                        <h4>5. Protection des données personnelles</h4>
                        <p>Nous respectons votre vie privée et protégeons vos données personnelles conformément à notre politique de confidentialité et aux lois applicables, notamment le Règlement Général sur la Protection des Données (RGPD).</p>
                    </div>
                    
                    <!-- Onglet À propos -->
                    <div class="cookie-tab-content" id="tab-about">
                        <h4>${getTranslation('whatAreCookies')}</h4>
                        <p>${getTranslation('cookiesDefinition')}</p>
                        
                        <h4>${getTranslation('howWeUseCookies')}</h4>
                        <p>${getTranslation('cookiesUsage')}</p>
                        
                        <h4>${getTranslation('managingCookies')}</h4>
                        <p>${getTranslation('cookiesManagement')}</p>
                        
                        <h4>${getTranslation('moreInfo')}</h4>
                        <p>${getTranslation('moreInfoText')} <a href="/terms" class="terms-link">${getTranslation('termsLink')}</a>.</p>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <button class="btn-cookie-necessary">${getTranslation('necessaryOnly')}</button>
                    <button class="btn-cookie-accept" id="save-preferences-btn">${getTranslation('savePreferences')}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    /**
     * Initialise tous les événements liés aux cookies
     */
    function initializeEvents() {
        // Gestion des onglets dans la modal
        const tabs = document.querySelectorAll('.cookie-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                activateTab(tabName);
            });
        });

        // Gestion du bouton de fermeture
        const closeButton = document.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                // Si fermé sans choix, sauvegarder avec les paramètres actuels
                if (!cookieConfig.consentGiven) {
                    savePreferences();
                }
                hideCookieModal();
            });
        }

        // Gestion du bouton d'enregistrement
        const saveButton = document.querySelector('#save-preferences-btn');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                savePreferences();
                hideCookieModal();
            });
        }
        
        // Gestion du bouton "Seulement nécessaires"
        const necessaryButton = document.querySelector('.btn-cookie-necessary');
        if (necessaryButton) {
            necessaryButton.addEventListener('click', function() {
                // Sélectionner uniquement les cookies nécessaires
                cookieConfig.preferences = false;
                cookieConfig.statistics = false;
                cookieConfig.marketing = false;
                
                updateCheckboxes();
                savePreferences();
                hideCookieModal();
            });
        }
        
        // Gestion des options expandables dans l'onglet détails
        const expandableOptions = document.querySelectorAll('.cookie-option-expandable');
        expandableOptions.forEach(option => {
            const header = option.querySelector('.cookie-option-header');
            const content = option.querySelector('.cookie-details-content');
            const chevron = option.querySelector('.cookie-chevron');
            
            header.addEventListener('click', function() {
                content.classList.toggle('expanded');
                chevron.classList.toggle('up');
            });
        });
        
        // Gestion du lien dans le footer
        setupCookieSettingsLink();
    }
    
    /**
     * Configuration du lien vers les paramètres des cookies dans le footer
     */
    function setupCookieSettingsLink() {
        const cookieSettingsLink = document.getElementById('cookie-settings-link');
        if (cookieSettingsLink) {
            cookieSettingsLink.addEventListener('click', function(e) {
                e.preventDefault();
                showCookieModal();
            });
        }
    }
    
    /**
     * Active un onglet spécifique
     */
    function activateTab(tabName) {
        // Désactiver tous les onglets
        document.querySelectorAll('.cookie-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.cookie-tab-content').forEach(c => c.classList.remove('active'));
        
        // Activer l'onglet spécifié
        document.querySelector(`.cookie-tab[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`tab-${tabName}`).classList.add('active');
    }

    /**
     * Met à jour les cases à cocher dans la modal
     */
    function updateCheckboxes() {
        document.querySelector('input[data-type="preferences"]').checked = cookieConfig.preferences;
        document.querySelector('input[data-type="statistics"]').checked = cookieConfig.statistics;
        document.querySelector('input[data-type="marketing"]').checked = cookieConfig.marketing;
    }
    
    /**
     * Enregistre les préférences de cookies
     */
    function savePreferences() {
        // Récupérer les valeurs des cases à cocher
        cookieConfig.preferences = document.querySelector('input[data-type="preferences"]').checked;
        cookieConfig.statistics = document.querySelector('input[data-type="statistics"]').checked;
        cookieConfig.marketing = document.querySelector('input[data-type="marketing"]').checked;
        cookieConfig.consentGiven = true;
        
        // Enregistrer dans localStorage
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Enregistrer dans un cookie pour le serveur
        document.cookie = `cookieConsent=${JSON.stringify(cookieConfig)}; max-age=${180*24*60*60}; path=/; SameSite=Lax`;
        
        // Déclencher un événement personnalisé pour informer de la mise à jour
        const event = new CustomEvent('cookiePreferencesUpdated', { 
            detail: cookieConfig 
        });
        document.dispatchEvent(event);
    }
    
    /**
     * Affiche la modal de cookies
     */
    function showCookieModal() {
        const modal = document.getElementById('cookieModal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Empêcher le défilement
        }
    }
    
    /**
     * Cache la modal de cookies
     */
    function hideCookieModal() {
        const modal = document.getElementById('cookieModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = ''; // Rétablir le défilement
        }
    }
    
    /**
     * Récupère la configuration des cookies depuis le localStorage
     */
    function getCookieConfig() {
        try {
            const storedConfig = localStorage.getItem('cookieConfig');
            return storedConfig ? JSON.parse(storedConfig) : null;
        } catch (e) {
            console.error('Error reading cookie config:', e);
            return null;
        }
    }
    
    /**
     * Obtient la langue actuelle
     */
    function getCurrentLanguage() {
        const htmlLang = document.documentElement.lang;
        return htmlLang ? htmlLang.substring(0, 2) : 'fr';
    }
    
    /**
     * Obtient une traduction selon la clé fournie
     */
    function getTranslation(key, lang) {
        const currentLang = lang || getCurrentLanguage();
        
        const translations = {
            fr: {
                cookiePreferences: 'Préférences de cookies',
                tabOverview: 'Vue d\'ensemble',
                tabDetails: 'Détails',
                tabTerms: 'Conditions d\'utilisation',
                tabAbout: 'À propos',
                necessaryCookies: 'Cookies nécessaires',
                preferencesCookies: 'Cookies de préférences',
                statisticsCookies: 'Cookies statistiques',
                marketingCookies: 'Cookies marketing',
                necessaryDescription: 'Ces cookies sont essentiels pour le fonctionnement de base du site web et ne peuvent pas être désactivés.',
                preferencesDescription: 'Ces cookies permettent de mémoriser vos choix et préférences, comme la langue préférée ou la région où vous vous trouvez.',
                statisticsDescription: 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site web en recueillant et rapportant des informations de manière anonyme.',
                marketingDescription: 'Ces cookies sont utilisés pour suivre les visiteurs sur les sites web. Ils permettent d\'afficher des publicités pertinentes et engageantes pour l\'utilisateur.',
                necessaryDetailsDescription: 'Les cookies nécessaires aident à rendre un site web utilisable en activant des fonctions de base comme la navigation de page et l\'accès aux zones sécurisées du site web. Le site web ne peut pas fonctionner correctement sans ces cookies.',
                preferencesDetailsDescription: 'Les cookies de préférences permettent à un site web de mémoriser des informations qui modifient la manière dont le site se comporte ou s\'affiche, comme votre langue préférée ou la région dans laquelle vous vous trouvez.',
                statisticsDetailsDescription: 'Les cookies statistiques aident les propriétaires du site web à comprendre comment les visiteurs interagissent avec le site en recueillant et en transmettant des informations de manière anonyme.',
                marketingDetailsDescription: 'Les cookies marketing sont utilisés pour suivre les visiteurs sur les sites web. Le but est d\'afficher des publicités qui sont pertinentes et intéressantes pour l\'utilisateur individuel et donc plus précieuses pour les éditeurs et annonceurs tiers.',
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
                necessaryOnly: 'Refuser optionnels',
                savePreferences: 'Enregistrer les préférences'
            },
            en: {
                cookiePreferences: 'Cookie settings',
                tabOverview: 'Overview',
                tabDetails: 'Details',
                tabTerms: 'Terms of Use',
                tabAbout: 'About',
                necessaryCookies: 'Necessary cookies',
                preferencesCookies: 'Preference cookies',
                statisticsCookies: 'Statistics cookies',
                marketingCookies: 'Marketing cookies',
                necessaryDescription: 'These cookies are essential for the basic functioning of the website and cannot be disabled.',
                preferencesDescription: 'These cookies allow us to remember your choices and preferences, such as language preference or the region where you are located.',
                statisticsDescription: 'These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
                marketingDescription: 'These cookies are used to track visitors across websites. The aim is to display ads that are relevant and engaging for the individual user.',
                // autres traductions...
                necessaryOnly: 'Necessary only',
                savePreferences: 'Save preferences'
            },
            nl: {
                cookiePreferences: 'Cookie-instellingen',
                tabOverview: 'Overzicht',
                tabDetails: 'Details',
                tabTerms: 'Gebruiksvoorwaarden',
                tabAbout: 'Over',
                necessaryCookies: 'Noodzakelijke cookies',
                preferencesCookies: 'Voorkeurscookies',
                statisticsCookies: 'Statistische cookies',
                marketingCookies: 'Marketing cookies',
                // autres traductions...
                necessaryOnly: 'Alleen noodzakelijke',
                savePreferences: 'Voorkeuren opslaan'
            },
            de: {
                cookiePreferences: 'Cookie-Einstellungen',
                tabOverview: 'Übersicht',
                tabDetails: 'Details',
                tabTerms: 'Nutzungsbedingungen',
                tabAbout: 'Über',
                necessaryCookies: 'Notwendige Cookies',
                preferencesCookies: 'Präferenz-Cookies',
                statisticsCookies: 'Statistik-Cookies',
                marketingCookies: 'Marketing-Cookies',
                // autres traductions...
                necessaryOnly: 'Nur notwendige',
                savePreferences: 'Einstellungen speichern'
            }
        };
        
        // Si la langue n'existe pas ou la clé n'existe pas, utiliser le français
        if (!translations[currentLang] || !translations[currentLang][key]) {
            return translations.fr[key] || key;
        }
        
        return translations[currentLang][key];
    }
    
    // Exposer quelques fonctions pour permettre l'interaction depuis l'extérieur
    window.cookieManager = {
        showModal: showCookieModal,
        getConfig: function() {
            return {...cookieConfig};
        },
        setConfig: function(newConfig) {
            cookieConfig = {...cookieConfig, ...newConfig};
            updateCheckboxes();
        }
    };
});