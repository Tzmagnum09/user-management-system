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
        
        // Récupération de la langue actuelle
        const currentLang = getCurrentLanguage();
        
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h4>${getTranslation('cookiePreferences', currentLang)}</h4>
                    <button class="btn-close">&times;</button>
                </div>
                <div class="cookie-modal-body">
                    <div class="cookie-tabs">
                        <button class="cookie-tab active" data-tab="overview">${getTranslation('tabOverview', currentLang)}</button>
                        <button class="cookie-tab" data-tab="details">${getTranslation('tabDetails', currentLang)}</button>
                        <button class="cookie-tab" data-tab="terms">${getTranslation('tabTerms', currentLang)}</button>
                        <button class="cookie-tab" data-tab="about">${getTranslation('tabAbout', currentLang)}</button>
                    </div>
                    
                    <!-- Onglet Vue d'ensemble -->
                    <div class="cookie-tab-content active" id="tab-overview">
                        <div class="cookie-setting-item">
                            <div>
                                <h4 class="cookie-setting-title">${getTranslation('necessaryCookies', currentLang)}</h4>
                                <p class="cookie-setting-description">${getTranslation('necessaryDescription', currentLang)}</p>
                            </div>
                            <label class="cookie-toggle">
                                <input type="checkbox" checked disabled data-type="necessary">
                                <span class="cookie-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="cookie-setting-item">
                            <div>
                                <h4 class="cookie-setting-title">${getTranslation('preferencesCookies', currentLang)}</h4>
                                <p class="cookie-setting-description">${getTranslation('preferencesDescription', currentLang)}</p>
                            </div>
                            <label class="cookie-toggle">
                                <input type="checkbox" ${cookieConfig.preferences ? 'checked' : ''} data-type="preferences">
                                <span class="cookie-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="cookie-setting-item">
                            <div>
                                <h4 class="cookie-setting-title">${getTranslation('statisticsCookies', currentLang)}</h4>
                                <p class="cookie-setting-description">${getTranslation('statisticsDescription', currentLang)}</p>
                            </div>
                            <label class="cookie-toggle">
                                <input type="checkbox" ${cookieConfig.statistics ? 'checked' : ''} data-type="statistics">
                                <span class="cookie-toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="cookie-setting-item">
                            <div>
                                <h4 class="cookie-setting-title">${getTranslation('marketingCookies', currentLang)}</h4>
                                <p class="cookie-setting-description">${getTranslation('marketingDescription', currentLang)}</p>
                            </div>
                            <label class="cookie-toggle">
                                <input type="checkbox" ${cookieConfig.marketing ? 'checked' : ''} data-type="marketing">
                                <span class="cookie-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Onglet Détails -->
                    <div class="cookie-tab-content" id="tab-details">
                        <div class="cookie-type">
                            <div class="cookie-type-header active" data-type="necessary">
                                <h5>${getTranslation('necessaryCookies', currentLang)}</h5>
                                <i class="fas fa-chevron-down cookie-chevron up"></i>
                            </div>
                            <div class="cookie-type-content expanded" id="necessary-content">
                                <div class="cookie-type-description">
                                    ${getTranslation('necessaryDetailsDescription', currentLang)}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName', currentLang)}</th>
                                            <th>${getTranslation('cookieProvider', currentLang)}</th>
                                            <th>${getTranslation('cookiePurpose', currentLang)}</th>
                                            <th>${getTranslation('cookieExpiry', currentLang)}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>PHPSESSID</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${getTranslation('sessionPurpose', currentLang)}</td>
                                            <td>${getTranslation('sessionEnd', currentLang)}</td>
                                        </tr>
                                        <tr>
                                            <td>cookieConsent</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${getTranslation('consentPurpose', currentLang)}</td>
                                            <td>6 ${getTranslation('months', currentLang)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-type">
                            <div class="cookie-type-header" data-type="preferences">
                                <h5>${getTranslation('preferencesCookies', currentLang)}</h5>
                                <i class="fas fa-chevron-down cookie-chevron"></i>
                            </div>
                            <div class="cookie-type-content" id="preferences-content">
                                <div class="cookie-type-description">
                                    ${getTranslation('preferencesDetailsDescription', currentLang)}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName', currentLang)}</th>
                                            <th>${getTranslation('cookieProvider', currentLang)}</th>
                                            <th>${getTranslation('cookiePurpose', currentLang)}</th>
                                            <th>${getTranslation('cookieExpiry', currentLang)}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_locale</td>
                                            <td>serveur.dmqode.be</td>
                                            <td>${getTranslation('languagePurpose', currentLang)}</td>
                                            <td>1 ${getTranslation('year', currentLang)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-type">
                            <div class="cookie-type-header" data-type="statistics">
                                <h5>${getTranslation('statisticsCookies', currentLang)}</h5>
                                <i class="fas fa-chevron-down cookie-chevron"></i>
                            </div>
                            <div class="cookie-type-content" id="statistics-content">
                                <div class="cookie-type-description">
                                    ${getTranslation('statisticsDetailsDescription', currentLang)}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName', currentLang)}</th>
                                            <th>${getTranslation('cookieProvider', currentLang)}</th>
                                            <th>${getTranslation('cookiePurpose', currentLang)}</th>
                                            <th>${getTranslation('cookieExpiry', currentLang)}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_ga</td>
                                            <td>Google</td>
                                            <td>${getTranslation('analyticsPurpose', currentLang)}</td>
                                            <td>2 ${getTranslation('years', currentLang)}</td>
                                        </tr>
                                        <tr>
                                            <td>_gid</td>
                                            <td>Google</td>
                                            <td>${getTranslation('userIdPurpose', currentLang)}</td>
                                            <td>24 ${getTranslation('hours', currentLang)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="cookie-type">
                            <div class="cookie-type-header" data-type="marketing">
                                <h5>${getTranslation('marketingCookies', currentLang)}</h5>
                                <i class="fas fa-chevron-down cookie-chevron"></i>
                            </div>
                            <div class="cookie-type-content" id="marketing-content">
                                <div class="cookie-type-description">
                                    ${getTranslation('marketingDetailsDescription', currentLang)}
                                </div>
                                <table class="cookie-table">
                                    <thead>
                                        <tr>
                                            <th>${getTranslation('cookieName', currentLang)}</th>
                                            <th>${getTranslation('cookieProvider', currentLang)}</th>
                                            <th>${getTranslation('cookiePurpose', currentLang)}</th>
                                            <th>${getTranslation('cookieExpiry', currentLang)}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>_fbp</td>
                                            <td>Facebook</td>
                                            <td>${getTranslation('facebookPurpose', currentLang)}</td>
                                            <td>3 ${getTranslation('months', currentLang)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglet Conditions d'utilisation -->
                    <div class="cookie-tab-content" id="tab-terms">
                        <div class="terms-section">
                            <h4>1. Introduction</h4>
                            <p>Bienvenue sur DMQode.be. Les présentes conditions d'utilisation régissent votre utilisation de notre site web et de nos services. En vous inscrivant sur notre site, vous acceptez d'être lié par ces conditions. Veuillez les lire attentivement.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h4>2. Définitions</h4>
                            <ul>
                                <li><strong>'Service'</strong> désigne le site web DMQode.be</li>
                                <li><strong>'Utilisateur'</strong> désigne toute personne qui accède au Service et crée un compte</li>
                                <li><strong>'Compte'</strong> désigne l'accès personnalisé au Service</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h4>3. Inscription et compte</h4>
                            <p>Pour utiliser certaines fonctionnalités du Service, vous devez créer un compte. Lors de l'inscription, vous acceptez de fournir des informations exactes, à jour et complètes. Vous êtes responsable de la confidentialité de votre mot de passe et de toutes les activités qui se produisent sous votre compte.</p>
                            <p>Après votre inscription, vous devrez vérifier votre adresse e-mail. Ensuite, un administrateur devra approuver votre compte avant que vous puissiez accéder à toutes les fonctionnalités du service.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h4>4. Droits et responsabilités</h4>
                            <p>En utilisant notre Service, vous acceptez de ne pas :</p>
                            <ul>
                                <li>Violer les lois applicables</li>
                                <li>Distribuer des virus ou tout autre code malveillant</li>
                                <li>Perturber ou interférer avec la sécurité du Service</li>
                                <li>Recueillir des informations sur d'autres utilisateurs sans leur consentement</li>
                                <li>Utiliser le Service d'une manière qui pourrait endommager, désactiver, surcharger ou altérer le Service</li>
                            </ul>
                        </div>
                        
                        <div class="terms-section">
                            <h4>5. Protection des données personnelles</h4>
                            <p>Nous respectons votre vie privée et protégeons vos données personnelles conformément à notre politique de confidentialité et aux lois applicables, notamment le Règlement Général sur la Protection des Données (RGPD).</p>
                            <p>Les données personnelles que vous nous fournissez lors de l'inscription sont utilisées uniquement dans le cadre de la fourniture de nos services et ne seront pas partagées avec des tiers sans votre consentement explicite.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h4>6. Modification des conditions d'utilisation</h4>
                            <p>Nous nous réservons le droit de modifier ces conditions d'utilisation à tout moment. Les modifications entrent en vigueur dès leur publication sur le Service. Il est de votre responsabilité de consulter régulièrement ces conditions.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h4>7. Résiliation</h4>
                            <p>Nous nous réservons le droit de suspendre ou de résilier votre compte et votre accès au Service à notre seule discrétion, sans préavis, pour conduite que nous jugeons contraire à ces conditions d'utilisation ou à toute loi applicable.</p>
                        </div>
                        
                        <div class="terms-section">
                            <h4>8. Contact</h4>
                            <p>Si vous avez des questions concernant ces conditions d'utilisation, veuillez nous contacter à l'adresse suivante : <a href="mailto:contact@dmqode.be">contact@dmqode.be</a></p>
                            <p>Dernière mise à jour : 17/04/2025.</p>
                        </div>
                    </div>
                    
                    <!-- Onglet À propos -->
                    <div class="cookie-tab-content" id="tab-about">
                        <div class="about-section">
                            <h4>Que sont les cookies ?</h4>
                            <p>Les cookies sont de petits fichiers texte qui sont stockés sur votre ordinateur ou appareil mobile lorsque vous visitez un site web. Ils permettent au site de mémoriser vos actions et préférences pendant une période déterminée, afin que vous n'ayez pas à les ressaisir à chaque fois que vous visitez le site ou naviguez d'une page à une autre.</p>
                            
                            <h4>Comment utilisons-nous les cookies ?</h4>
                            <p>Nous utilisons différents types de cookies pour faire fonctionner notre site, améliorer votre expérience utilisateur, analyser comment le site est utilisé et personnaliser le contenu et les publicités que vous voyez.</p>
                            
                            <h4>Comment gérer les cookies ?</h4>
                            <p>Vous pouvez gérer vos préférences de cookies à tout moment en cliquant sur le lien 'Paramètres des cookies' en bas de notre site. Vous pouvez également modifier les paramètres de votre navigateur pour supprimer ou empêcher certains cookies d'être stockés sur votre ordinateur ou appareil mobile sans votre consentement explicite.</p>
                            
                            <h4>Plus d'informations</h4>
                            <p>Pour en savoir plus sur notre utilisation des cookies, veuillez nous contacter : <a href="mailto:contact@dmqode.be">contact@dmqode.be</a></p>
                        </div>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <button class="btn-cookie-necessary">${getTranslation('necessaryOnly', currentLang)}</button>
                    <button class="btn-cookie-accept">${getTranslation('savePreferences', currentLang)}</button>
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
        const saveButton = document.querySelector('.btn-cookie-accept');
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
        
        // Gestion des en-têtes de détails - modifié pour ajuster la logique d'inversion
        const typeHeaders = document.querySelectorAll('.cookie-type-header');
        typeHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                const content = document.getElementById(type + '-content');
                const chevron = this.querySelector('.cookie-chevron');
                
                // Fermer tous les autres contenus
                document.querySelectorAll('.cookie-type-content').forEach(c => {
                    if (c.id !== type + '-content') {
                        c.classList.remove('expanded');
                    }
                });
                
                document.querySelectorAll('.cookie-type-header').forEach(h => {
                    if (h !== this) {
                        h.classList.remove('active');
                        const otherChevron = h.querySelector('.cookie-chevron');
                        if (otherChevron) otherChevron.classList.remove('up');
                    }
                });
                
                // Toggle l'état actuel
                content.classList.toggle('expanded');
                this.classList.toggle('active');
                chevron.classList.toggle('up');
            });
        });
        
        // Synchroniser le changement de checkbox avec le config
        const checkboxes = document.querySelectorAll('.cookie-toggle input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const type = this.getAttribute('data-type');
                cookieConfig[type] = this.checked;
            });
        });
        
        // Gestion du lien dans le footer
        setupCookieSettingsLink();
        
        // Synchroniser avec le sélecteur de langue du site
        synchronizeLanguageSelector();
    }
    
    /**
     * Synchronise le sélecteur de langue du site avec celui de la modal
     */
    function synchronizeLanguageSelector() {
        // Écouteur de changement de langue pour le site
        const siteLangSelector = document.querySelector('#languageDropdown');
        if (siteLangSelector) {
            siteLangSelector.addEventListener('click', function(e) {
                if (e.target.tagName === 'A' && e.target.classList.contains('dropdown-item')) {
                    const lang = e.target.getAttribute('href').match(/\/(fr|en|nl|de)\//);
                    if (lang && lang[1]) {
                        updateModalLanguage(lang[1]);
                    }
                }
            });
        }
    }
    
    /**
     * Met à jour la langue de la modal
     */
    function updateModalLanguage(lang) {
        const modal = document.getElementById('cookieModal');
        if (!modal) return;
        
        // Mise à jour des textes d'interface
        modal.querySelector('.cookie-modal-header h4').textContent = getTranslation('cookiePreferences', lang);
        
        // Onglets
        const tabs = modal.querySelectorAll('.cookie-tab');
        tabs[0].textContent = getTranslation('tabOverview', lang);
        tabs[1].textContent = getTranslation('tabDetails', lang);
        tabs[2].textContent = getTranslation('tabTerms', lang);
        tabs[3].textContent = getTranslation('tabAbout', lang);
        
        // Vue d'ensemble
        const settingTitles = modal.querySelectorAll('.cookie-setting-title');
        settingTitles[0].textContent = getTranslation('necessaryCookies', lang);
        settingTitles[1].textContent = getTranslation('preferencesCookies', lang);
        settingTitles[2].textContent = getTranslation('statisticsCookies', lang);
        settingTitles[3].textContent = getTranslation('marketingCookies', lang);
        
        const settingDescs = modal.querySelectorAll('.cookie-setting-description');
        settingDescs[0].textContent = getTranslation('necessaryDescription', lang);
        settingDescs[1].textContent = getTranslation('preferencesDescription', lang);
        settingDescs[2].textContent = getTranslation('statisticsDescription', lang);
        settingDescs[3].textContent = getTranslation('marketingDescription', lang);
        
        // Boutons de pied de page
        modal.querySelector('.btn-cookie-necessary').textContent = getTranslation('necessaryOnly', lang);
        modal.querySelector('.btn-cookie-accept').textContent = getTranslation('savePreferences', lang);
        
        // Détails
        const typeHeaders = modal.querySelectorAll('.cookie-type-header h5');
        typeHeaders[0].textContent = getTranslation('necessaryCookies', lang);
        typeHeaders[1].textContent = getTranslation('preferencesCookies', lang);
        typeHeaders[2].textContent = getTranslation('statisticsCookies', lang);
        typeHeaders[3].textContent = getTranslation('marketingCookies', lang);
        
        // Les descriptions et les entêtes de tableau seraient aussi à mettre à jour
        // mais je les laisse en français pour simplifier
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
        // Mettre à jour consentement
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
        // Essayer d'obtenir la langue à partir de l'URL
        const path = window.location.pathname;
        const langMatch = path.match(/^\/(fr|en|nl|de)\//);
        
        if (langMatch && langMatch[1]) {
            return langMatch[1];
        }
        
        // Sinon, essayer de l'obtenir du tag HTML
        const htmlLang = document.documentElement.lang;
        if (htmlLang) {
            // Extraire les deux premiers caractères (fr-FR -> fr)
            return htmlLang.substring(0, 2);
        }
        
        // Par défaut, retourner français
        return 'fr';
    }
    
    /**
     * Obtient une traduction selon la clé fournie
     */
    function getTranslation(key, lang) {
        lang = lang || getCurrentLanguage();
        
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
                necessaryDetailsDescription: 'Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas of the website. The website cannot function properly without these cookies.',
                preferencesDetailsDescription: 'Preference cookies enable a website to remember information that changes the way the website behaves or looks, like your preferred language or the region that you are in.',
                statisticsDetailsDescription: 'Statistic cookies help website owners to understand how visitors interact with websites by collecting and reporting information anonymously.',
                marketingDetailsDescription: 'Marketing cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user and thereby more valuable for publishers and third party advertisers.',
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
                necessaryDescription: 'Deze cookies zijn essentieel voor de basisfunctionaliteit van de website en kunnen niet worden uitgeschakeld.',
                preferencesDescription: 'Deze cookies stellen ons in staat om uw keuzes en voorkeuren te onthouden, zoals taalvoorkeur of de regio waar u zich bevindt.',
                statisticsDescription: 'Deze cookies helpen ons te begrijpen hoe bezoekers met onze website interageren door informatie anoniem te verzamelen en te rapporteren.',
                marketingDescription: 'Deze cookies worden gebruikt om bezoekers op websites te volgen. Het doel is om advertenties weer te geven die relevant en aantrekkelijk zijn voor de individuele gebruiker.',
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
                necessaryDescription: 'Diese Cookies sind für die grundlegende Funktionalität der Website unerlässlich und können nicht deaktiviert werden.',
                preferencesDescription: 'Diese Cookies ermöglichen es uns, Ihre Auswahl und Präferenzen zu speichern, wie z.B. Spracheinstellungen oder die Region, in der Sie sich befinden.',
                statisticsDescription: 'Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren, indem sie Informationen anonym sammeln und melden.',
                marketingDescription: 'Diese Cookies werden verwendet, um Besucher auf Websites zu verfolgen. Ziel ist es, Anzeigen zu schalten, die für den einzelnen Nutzer relevant und ansprechend sind.',
                necessaryOnly: 'Nur notwendige',
                savePreferences: 'Einstellungen speichern'
            }
        };
        
        if (!translations[lang] || !translations[lang][key]) {
            return translations.fr[key] || key;
        }
        
        return translations[lang][key];
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