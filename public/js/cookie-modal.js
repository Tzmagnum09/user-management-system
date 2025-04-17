document.addEventListener('DOMContentLoaded', function() {
    // Configuration par défaut des cookies
    const defaultCookieConfig = {
        necessary: true, // Toujours activé et non modifiable
        preferences: true,
        statistics: true,
        marketing: false,
        consentGiven: false
    };

    // Récupération des préférences stockées ou utilisation des valeurs par défaut
    let cookieConfig = getCookieConfig() || {...defaultCookieConfig};

    // Créer la modal
    createCookieModal();
    
    // Initialiser les événements
    initializeEvents();
    
    // Afficher la modal si le consentement n'a pas encore été donné
    if (!cookieConfig.consentGiven) {
        setTimeout(showCookieModal, 1000);
    }

    // Fonction pour créer la modal
    function createCookieModal() {
        const modal = document.createElement('div');
        modal.id = 'cookieModal';
        modal.className = 'cookie-modal';
        
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h4>${getTranslation('cookiePreferences')}</h4>
                    <button type="button" class="btn-close">&times;</button>
                </div>
                <div class="cookie-modal-body">
                    <div class="cookie-tabs">
                        <div class="cookie-tab active" data-tab="overview">${getTranslation('tabOverview')}</div>
                        <div class="cookie-tab" data-tab="details">${getTranslation('tabDetails')}</div>
                        <div class="cookie-tab" data-tab="about">${getTranslation('tabAbout')}</div>
                    </div>
                    
                    <div class="cookie-tab-content active" id="tab-overview">
                        <div class="cookie-option-overview">
                            <div class="cookie-option-row">
                                <div class="cookie-option-text">
                                    <h4>${getTranslation('necessaryCookies')}</h4>
                                    <p>${getTranslation('necessaryDescription')}</p>
                                </div>
                                <div class="cookie-toggle">
                                    <label class="switch-toggle">
                                        <input type="checkbox" checked disabled data-type="necessary">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cookie-option-overview">
                            <div class="cookie-option-row">
                                <div class="cookie-option-text">
                                    <h4>${getTranslation('preferencesCookies')}</h4>
                                    <p>${getTranslation('preferencesDescription')}</p>
                                </div>
                                <div class="cookie-toggle">
                                    <label class="switch-toggle">
                                        <input type="checkbox" ${cookieConfig.preferences ? 'checked' : ''} data-type="preferences">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cookie-option-overview">
                            <div class="cookie-option-row">
                                <div class="cookie-option-text">
                                    <h4>${getTranslation('statisticsCookies')}</h4>
                                    <p>${getTranslation('statisticsDescription')}</p>
                                </div>
                                <div class="cookie-toggle">
                                    <label class="switch-toggle">
                                        <input type="checkbox" ${cookieConfig.statistics ? 'checked' : ''} data-type="statistics">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cookie-option-overview">
                            <div class="cookie-option-row">
                                <div class="cookie-option-text">
                                    <h4>${getTranslation('marketingCookies')}</h4>
                                    <p>${getTranslation('marketingDescription')}</p>
                                </div>
                                <div class="cookie-toggle">
                                    <label class="switch-toggle">
                                        <input type="checkbox" ${cookieConfig.marketing ? 'checked' : ''} data-type="marketing">
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cookie-tab-content" id="tab-details">
                        <!-- Contenu pour l'onglet détails -->
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="necessary">
                                <h4>${getTranslation('necessaryCookies')}</h4>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="necessary-content">
                                <div class="cookie-option-description">${getTranslation('necessaryDescription')}</div>
                                <table class="cookie-table">
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
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="preferences">
                                <h4>${getTranslation('preferencesCookies')}</h4>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="preferences-content">
                                <div class="cookie-option-description">${getTranslation('preferencesDescription')}</div>
                                <table class="cookie-table">
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
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="statistics">
                                <h4>${getTranslation('statisticsCookies')}</h4>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="statistics-content">
                                <div class="cookie-option-description">${getTranslation('statisticsDescription')}</div>
                                <table class="cookie-table">
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
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header" data-option="marketing">
                                <h4>${getTranslation('marketingCookies')}</h4>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </div>
                            <div class="cookie-option-content" id="marketing-content">
                                <div class="cookie-option-description">${getTranslation('marketingDescription')}</div>
                                <table class="cookie-table">
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
                    
                    <div class="cookie-tab-content" id="tab-about">
                        <div class="about-section">
                            <h3>${getTranslation('whatAreCookies')}</h3>
                            <p>${getTranslation('cookiesDefinition')}</p>
                            
                            <h3>${getTranslation('howWeUseCookies')}</h3>
                            <p>${getTranslation('cookiesUsage')}</p>
                            
                            <h3>${getTranslation('managingCookies')}</h3>
                            <p>${getTranslation('cookiesManagement')}</p>
                            
                            <h3>${getTranslation('moreInfo')}</h3>
                            <p>${getTranslation('moreInfoText')} <a href="/terms" class="terms-link">${getTranslation('termsLink')}</a>.</p>
                        </div>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <button type="button" class="cookie-button" id="savePreferences">${getTranslation('savePreferences')}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    // Initialiser les événements
    function initializeEvents() {
        const modal = document.getElementById('cookieModal');
        if (!modal) return;
        
        // Gestion des onglets
        const tabs = modal.querySelectorAll('.cookie-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Désactiver tous les onglets
                tabs.forEach(t => t.classList.remove('active'));
                // Activer l'onglet actuel
                this.classList.add('active');
                
                // Masquer tous les contenus d'onglets
                const tabContents = modal.querySelectorAll('.cookie-tab-content');
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Afficher le contenu correspondant
                const tabName = this.getAttribute('data-tab');
                document.getElementById(`tab-${tabName}`).classList.add('active');
            });
        });
        
        // Gestion du bouton de fermeture
        const closeButton = modal.querySelector('.btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                hideCookieModal();
                
                // Si aucune décision n'a été prise, sauvegarder avec les paramètres actuels
                if (!cookieConfig.consentGiven) {
                    savePreferences();
                }
            });
        }
        
        // Gestion des en-têtes d'options dans l'onglet détails
        const optionHeaders = modal.querySelectorAll('.cookie-option-header');
        optionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const option = this.getAttribute('data-option');
                
                if (option) {
                    // Dans l'onglet détails - toggle le contenu correspondant
                    const contentId = `${option}-content`;
                    const content = document.getElementById(contentId);
                    
                    if (content) {
                        // Fermer tous les contenus d'abord
                        const allContents = modal.querySelectorAll('.cookie-option-content');
                        allContents.forEach(c => {
                            c.classList.remove('active');
                        });
                        
                        // Réinitialiser tous les headers
                        optionHeaders.forEach(h => {
                            h.classList.remove('active');
                        });
                        
                        // Ouvrir le contenu sélectionné
                        content.classList.add('active');
                        this.classList.add('active');
                    }
                }
            });
        });
        
        // Gestion des toggles de préférences
        const toggleInputs = modal.querySelectorAll('input[type="checkbox"]');
        toggleInputs.forEach(input => {
            input.addEventListener('change', function() {
                const type = this.getAttribute('data-type');
                if (type && type !== 'necessary') {
                    cookieConfig[type] = this.checked;
                }
            });
        });
        
        // Gestion du bouton de sauvegarde
        const saveButton = modal.querySelector('#savePreferences');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                savePreferences();
                hideCookieModal();
            });
        }
        
        // Ajouter un écouteur pour le lien dans le footer
        const cookieLink = document.getElementById('cookie-settings-link');
        if (cookieLink) {
            cookieLink.addEventListener('click', function(e) {
                e.preventDefault();
                showCookieModal();
            });
        }
    }
    
    // Sauvegarder les préférences
    function savePreferences() {
        // Récupérer les valeurs des toggles
        const modal = document.getElementById('cookieModal');
        if (modal) {
            cookieConfig.preferences = modal.querySelector('input[data-type="preferences"]').checked;
            cookieConfig.statistics = modal.querySelector('input[data-type="statistics"]').checked;
            cookieConfig.marketing = modal.querySelector('input[data-type="marketing"]').checked;
        }
        
        cookieConfig.consentGiven = true;
        
        // Sauvegarder dans localStorage
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Sauvegarder dans un cookie pour le serveur
        setCookie('cookieConsent', JSON.stringify(cookieConfig), 180); // 180 jours
        
        // Déclencher un événement pour informer de la mise à jour
        const event = new CustomEvent('cookiePreferencesUpdated', { 
            detail: cookieConfig 
        });
        document.dispatchEvent(event);
    }
    
    // Afficher la modal
    function showCookieModal() {
        const modal = document.getElementById('cookieModal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Empêcher le défilement
        }
    }
    
    // Masquer la modal
    function hideCookieModal() {
        const modal = document.getElementById('cookieModal');
        if (modal) {
            modal.classList.remove('show');
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
    
    // Obtenir les traductions
    function getTranslation(key) {
        const translations = {
            // Éléments d'interface
            cookiePreferences: 'Préférences de cookies',
            tabOverview: 'Vue d\'ensemble',
            tabDetails: 'Détails',
            tabAbout: 'À propos',
            savePreferences: 'Enregistrer les préférences',
            
            // Types de cookies
            necessaryCookies: 'Cookies nécessaires',
            preferencesCookies: 'Cookies de préférences',
            statisticsCookies: 'Cookies statistiques',
            marketingCookies: 'Cookies marketing',
            
            // Descriptions
            necessaryDescription: 'Ces cookies sont essentiels pour le fonctionnement de base du site web et ne peuvent pas être désactivés.',
            preferencesDescription: 'Ces cookies permettent de mémoriser vos choix et préférences, comme la langue préférée ou la région où vous vous trouvez.',
            statisticsDescription: 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site web en recueillant et rapportant des informations de manière anonyme.',
            marketingDescription: 'Ces cookies sont utilisés pour suivre les visiteurs sur les sites web. Ils permettent d\'afficher des publicités pertinentes et engageantes pour l\'utilisateur.',
            
            // Informations des cookies
            cookieName: 'Nom',
            cookieProvider: 'Fournisseur',
            cookiePurpose: 'Finalité',
            cookieExpiry: 'Expiration',
            sessionPurpose: 'Maintien de la session utilisateur',
            consentPurpose: 'Stockage des préférences de consentement aux cookies',
            languagePurpose: 'Stockage de la préférence de langue',
            analyticsPurpose: 'Suivi du comportement des utilisateurs sur le site pour améliorer l\'expérience',
            userIdPurpose: 'Distinction des utilisateurs',
            facebookPurpose: 'Suivi des conversions publicitaires et retargeting',
            sessionEnd: 'Fin de session',
            months: 'mois',
            year: 'an',
            years: 'ans',
            hours: 'heures',
            
            // Page À propos
            whatAreCookies: 'Que sont les cookies ?',
            cookiesDefinition: 'Les cookies sont de petits fichiers texte qui sont stockés sur votre ordinateur ou appareil mobile lorsque vous visitez un site web. Ils permettent au site de mémoriser vos actions et préférences pendant une période déterminée, afin que vous n\'ayez pas à les ressaisir à chaque fois que vous visitez le site ou naviguez d\'une page à une autre.',
            howWeUseCookies: 'Comment utilisons-nous les cookies ?',
            cookiesUsage: 'Nous utilisons différents types de cookies pour faire fonctionner notre site, améliorer votre expérience utilisateur, analyser comment le site est utilisé et personnaliser le contenu et les publicités que vous voyez.',
            managingCookies: 'Comment gérer les cookies ?',
            cookiesManagement: 'Vous pouvez gérer vos préférences de cookies à tout moment en cliquant sur le lien \'Paramètres des cookies\' en bas de notre site. Vous pouvez également modifier les paramètres de votre navigateur pour supprimer ou empêcher certains cookies d\'être stockés sur votre ordinateur ou appareil mobile sans votre consentement explicite.',
            moreInfo: 'Plus d\'informations',
            moreInfoText: 'Pour en savoir plus sur notre utilisation des cookies, veuillez consulter notre politique de confidentialité et nos conditions d\'utilisation sur',
            termsLink: 'notre page des conditions d\'utilisation'
        };
        
        return translations[key] || key;
    }
    
    // Exposer les fonctions pour permettre l'interaction externe
    window.cookieManager = {
        showModal: showCookieModal,
        getConfig: function() {
            return {...cookieConfig};
        }
    };
});