document.addEventListener('DOMContentLoaded', function() {
    // Configuration par défaut des cookies
    const defaultCookieConfig = {
        necessary: true, // Toujours activé
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
                    <div>
                        <select class="cookie-language-selector">
                            <option value="fr" ${getCurrentLanguage() === 'fr' ? 'selected' : ''}>Français</option>
                            <option value="en" ${getCurrentLanguage() === 'en' ? 'selected' : ''}>English</option>
                            <option value="nl" ${getCurrentLanguage() === 'nl' ? 'selected' : ''}>Nederlands</option>
                            <option value="de" ${getCurrentLanguage() === 'de' ? 'selected' : ''}>Deutsch</option>
                        </select>
                        <button class="btn-close">&times;</button>
                    </div>
                </div>
                <div class="cookie-modal-body">
                    <p>${getTranslation('cookieIntro')}</p>
                    
                    <div class="cookie-tabs">
                        <div class="cookie-tab active" data-tab="overview">${getTranslation('tabOverview')}</div>
                        <div class="cookie-tab" data-tab="details">${getTranslation('tabDetails')}</div>
                        <div class="cookie-tab" data-tab="about">${getTranslation('tabAbout')}</div>
                    </div>
                    
                    <div class="cookie-tab-content active" id="tab-overview">
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <div>${getTranslation('necessaryCookies')}</div>
                                <label class="cookie-switch">
                                    <input type="checkbox" checked disabled data-type="necessary">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${getTranslation('necessaryDescription')}
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <div>${getTranslation('preferencesCookies')}</div>
                                <label class="cookie-switch">
                                    <input type="checkbox" ${cookieConfig.preferences ? 'checked' : ''} data-type="preferences">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${getTranslation('preferencesDescription')}
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <div>${getTranslation('statisticsCookies')}</div>
                                <label class="cookie-switch">
                                    <input type="checkbox" ${cookieConfig.statistics ? 'checked' : ''} data-type="statistics">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${getTranslation('statisticsDescription')}
                            </div>
                        </div>
                        
                        <div class="cookie-option">
                            <div class="cookie-option-header">
                                <div>${getTranslation('marketingCookies')}</div>
                                <label class="cookie-switch">
                                    <input type="checkbox" ${cookieConfig.marketing ? 'checked' : ''} data-type="marketing">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            <div class="cookie-option-description">
                                ${getTranslation('marketingDescription')}
                            </div>
                        </div>
                    </div>
                    
                    <div class="cookie-tab-content" id="tab-details">
                        <!-- Contenu pour l'onglet détails -->
                        <p>${getTranslation('detailsIntro')}</p>
                        <!-- Ici vous pouvez ajouter plus de détails sur chaque type de cookie -->
                    </div>
                    
                    <div class="cookie-tab-content" id="tab-about">
                        <!-- Contenu pour l'onglet à propos -->
                        <h5>${getTranslation('whatAreCookies')}</h5>
                        <p>${getTranslation('cookiesDefinition')}</p>
                        
                        <h5>${getTranslation('howWeUseCookies')}</h5>
                        <p>${getTranslation('cookiesUsage')}</p>
                        
                        <h5>${getTranslation('moreInfo')}</h5>
                        <p>${getTranslation('moreInfoText')}</p>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <button class="cookie-button cookie-button-necessary">${getTranslation('necessaryOnly')}</button>
                    <button class="cookie-button cookie-button-save">${getTranslation('savePreferences')}</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }
    
    // Initialiser les événements
    function initializeEvents() {
        const modal = document.getElementById('cookieModal');
        
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
        closeButton.addEventListener('click', function() {
            hideCookieModal();
            if (!cookieConfig.consentGiven) {
                // Si fermé sans choix, sauvegarder avec les paramètres actuels
                savePreferences();
            }
        });
        
        // Gestion du bouton "Seulement nécessaires"
        const necessaryButton = modal.querySelector('.cookie-button-necessary');
        necessaryButton.addEventListener('click', function() {
            cookieConfig.preferences = false;
            cookieConfig.statistics = false;
            cookieConfig.marketing = false;
            
            // Mettre à jour les cases à cocher
            updateCheckboxes();
            
            // Sauvegarder et fermer
            savePreferences();
            hideCookieModal();
        });
        
        // Gestion du bouton "Enregistrer les préférences"
        const saveButton = modal.querySelector('.cookie-button-save');
        saveButton.addEventListener('click', function() {
            // Récupérer les valeurs des cases à cocher
            cookieConfig.preferences = modal.querySelector('input[data-type="preferences"]').checked;
            cookieConfig.statistics = modal.querySelector('input[data-type="statistics"]').checked;
            cookieConfig.marketing = modal.querySelector('input[data-type="marketing"]').checked;
            
            // Sauvegarder et fermer
            savePreferences();
            hideCookieModal();
        });
        
        // Gestion du sélecteur de langue
        const langSelector = modal.querySelector('.cookie-language-selector');
        langSelector.addEventListener('change', function() {
            changeLanguage(this.value);
        });
        
        // Ajouter un écouteur pour le lien dans le footer
        const cookieLink = document.getElementById('cookie-settings-link');
        if (cookieLink) {
            cookieLink.addEventListener('click', function(e) {
                e.preventDefault();
                showCookieModal();
            });
        }
    }
    
    // Mettre à jour les cases à cocher
    function updateCheckboxes() {
        const modal = document.getElementById('cookieModal');
        modal.querySelector('input[data-type="preferences"]').checked = cookieConfig.preferences;
        modal.querySelector('input[data-type="statistics"]').checked = cookieConfig.statistics;
        modal.querySelector('input[data-type="marketing"]').checked = cookieConfig.marketing;
    }
    
    // Sauvegarder les préférences
    function savePreferences() {
        cookieConfig.consentGiven = true;
        
        // Sauvegarder dans localStorage
        localStorage.setItem('cookieConfig', JSON.stringify(cookieConfig));
        
        // Sauvegarder dans un cookie pour le serveur
        document.cookie = `cookieConsent=${JSON.stringify(cookieConfig)}; max-age=${180*24*60*60}; path=/; SameSite=Lax`;
        
        // Déclencher un événement pour informer de la mise à jour
        const event = new CustomEvent('cookiePreferencesUpdated', { 
            detail: cookieConfig 
        });
        document.dispatchEvent(event);
    }
    
    // Afficher la modal
    function showCookieModal() {
        const modal = document.getElementById('cookieModal');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Empêcher le défilement
    }
    
    // Masquer la modal
    function hideCookieModal() {
        const modal = document.getElementById('cookieModal');
        modal.classList.remove('show');
        document.body.style.overflow = ''; // Rétablir le défilement
    }
    
    // Obtenir la configuration des cookies
    function getCookieConfig() {
        const storedConfig = localStorage.getItem('cookieConfig');
        return storedConfig ? JSON.parse(storedConfig) : null;
    }
    
    // Obtenir la langue actuelle
    function getCurrentLanguage() {
        const html = document.documentElement;
        const lang = html.lang || 'fr';
        return lang.substring(0, 2);
    }
    
    // Changer la langue de la modal
    function changeLanguage(lang) {
        // Mettre à jour tous les textes
        const modal = document.getElementById('cookieModal');
        
        modal.querySelector('.cookie-modal-header h4').textContent = getTranslation('cookiePreferences', lang);
        modal.querySelector('.cookie-modal-body > p').textContent = getTranslation('cookieIntro', lang);
        
        // Onglets
        const tabs = modal.querySelectorAll('.cookie-tab');
        tabs[0].textContent = getTranslation('tabOverview', lang);
        tabs[1].textContent = getTranslation('tabDetails', lang);
        tabs[2].textContent = getTranslation('tabAbout', lang);
        
        // Options de cookies
        const options = modal.querySelectorAll('#tab-overview .cookie-option-header div');
        options[0].textContent = getTranslation('necessaryCookies', lang);
        options[1].textContent = getTranslation('preferencesCookies', lang);
        options[2].textContent = getTranslation('statisticsCookies', lang);
        options[3].textContent = getTranslation('marketingCookies', lang);
        
        // Descriptions
        const descriptions = modal.querySelectorAll('#tab-overview .cookie-option-description');
        descriptions[0].textContent = getTranslation('necessaryDescription', lang);
        descriptions[1].textContent = getTranslation('preferencesDescription', lang);
        descriptions[2].textContent = getTranslation('statisticsDescription', lang);
        descriptions[3].textContent = getTranslation('marketingDescription', lang);
        
        // À propos
        if (document.getElementById('tab-about').classList.contains('active')) {
            const aboutHeadings = document.querySelectorAll('#tab-about h5');
            aboutHeadings[0].textContent = getTranslation('whatAreCookies', lang);
            aboutHeadings[1].textContent = getTranslation('howWeUseCookies', lang);
            aboutHeadings[2].textContent = getTranslation('moreInfo', lang);
            
            const aboutParagraphs = document.querySelectorAll('#tab-about p');
            aboutParagraphs[0].textContent = getTranslation('cookiesDefinition', lang);
            aboutParagraphs[1].textContent = getTranslation('cookiesUsage', lang);
            aboutParagraphs[2].textContent = getTranslation('moreInfoText', lang);
        }
        
        // Boutons
        modal.querySelector('.cookie-button-necessary').textContent = getTranslation('necessaryOnly', lang);
        modal.querySelector('.cookie-button-save').textContent = getTranslation('savePreferences', lang);
    }
    
    // Obtenir une traduction
    function getTranslation(key, lang) {
        lang = lang || getCurrentLanguage();
        const translations = {
            fr: {
                cookiePreferences: 'Préférences de cookies',
                cookieIntro: 'En tant que visiteur de notre site, nous essayons de vous offrir une expérience aussi agréable que possible. Nous utilisons des cookies en premier lieu pour améliorer votre expérience utilisateur et pour améliorer le fonctionnement de nos services en ligne. En outre, nous utilisons des cookies pour rendre le contenu de nos sites web et applications (mobiles) plus intéressant pour vous. Nous utilisons également des cookies pour cartographier votre comportement de navigation.',
                tabOverview: 'Vue d\'ensemble',
                tabDetails: 'Détails',
                tabAbout: 'À propos',
                necessaryCookies: 'Cookies nécessaires',
                preferencesCookies: 'Cookies de préférences',
                statisticsCookies: 'Cookies statistiques',
                marketingCookies: 'Cookies marketing',
                necessaryDescription: 'Ces cookies sont essentiels pour le fonctionnement de base du site web et ne peuvent pas être désactivés.',
                preferencesDescription: 'Ces cookies permettent de mémoriser vos choix et préférences, comme la langue préférée ou la région où vous vous trouvez.',
                statisticsDescription: 'Ces cookies nous aident à comprendre comment les visiteurs interagissent avec notre site web en recueillant et rapportant des informations de manière anonyme.',
                marketingDescription: 'Ces cookies sont utilisés pour suivre les visiteurs sur les sites web. Le but est d\'afficher des publicités qui sont pertinentes et intéressantes pour l\'utilisateur individuel.',
                detailsIntro: 'Voici plus de détails sur les cookies que nous utilisons et leurs objectifs.',
                whatAreCookies: 'Que sont les cookies ?',
                cookiesDefinition: 'Les cookies sont de petits fichiers texte qui sont stockés sur votre ordinateur ou appareil mobile lorsque vous visitez un site web. Ils permettent au site de mémoriser vos actions et préférences pendant une période déterminée.',
                howWeUseCookies: 'Comment utilisons-nous les cookies ?',
                cookiesUsage: 'Nous utilisons différents types de cookies pour faire fonctionner notre site, améliorer votre expérience utilisateur, analyser comment le site est utilisé et personnaliser le contenu et les publicités que vous voyez.',
                moreInfo: 'Plus d\'informations',
                moreInfoText: 'Pour en savoir plus sur notre utilisation des cookies, veuillez consulter notre politique de confidentialité et nos conditions d\'utilisation.',
                necessaryOnly: 'Seulement nécessaires',
                savePreferences: 'Enregistrer les préférences'
            },
            en: {
                cookiePreferences: 'Cookie settings',
                cookieIntro: 'As a visitor to our site, we try to offer you an experience as pleasant as possible. We use cookies primarily to improve your user experience and to improve the functioning of our online services. In addition, we use cookies to make the content of our websites and (mobile) applications more interesting for you. We also use cookies to map your browsing behavior.',
                tabOverview: 'Overview',
                tabDetails: 'Details',
                tabAbout: 'About',
                necessaryCookies: 'Necessary cookies',
                preferencesCookies: 'Preference cookies',
                statisticsCookies: 'Statistics cookies',
                marketingCookies: 'Marketing cookies',
                necessaryDescription: 'These cookies are essential for the basic functioning of the website and cannot be disabled.',
                preferencesDescription: 'These cookies allow us to remember your choices and preferences, such as language preference or the region where you are located.',
                statisticsDescription: 'These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
                marketingDescription: 'These cookies are used to track visitors across websites. The aim is to display ads that are relevant and engaging for the individual user.',
                detailsIntro: 'Here are more details about the cookies we use and their purposes.',
                whatAreCookies: 'What are cookies?',
                cookiesDefinition: 'Cookies are small text files that are stored on your computer or mobile device when you visit a website. They allow the site to remember your actions and preferences for a certain period of time.',
                howWeUseCookies: 'How do we use cookies?',
                cookiesUsage: 'We use different types of cookies to run our website, improve your user experience, analyze how the site is used, and personalize the content and advertisements you see.',
                moreInfo: 'More information',
                moreInfoText: 'To learn more about our use of cookies, please consult our privacy policy and terms of use.',
                necessaryOnly: 'Necessary only',
                savePreferences: 'Save preferences'
            },
            nl: {
                cookiePreferences: 'Cookie-instellingen',
                cookieIntro: 'Als bezoeker van onze site proberen we u een zo aangenaam mogelijke ervaring te bieden. We gebruiken cookies in de eerste plaats om uw gebruikerservaring te verbeteren en om de werking van onze online diensten te verbeteren. Daarnaast gebruiken we cookies om de inhoud van onze websites en (mobiele) applicaties interessanter voor u te maken. We gebruiken ook cookies om uw surfgedrag in kaart te brengen.',
                tabOverview: 'Overzicht',
                tabDetails: 'Details',
                tabAbout: 'Over',
                necessaryCookies: 'Noodzakelijke cookies',
                preferencesCookies: 'Voorkeurscookies',
                statisticsCookies: 'Statistische cookies',
                marketingCookies: 'Marketing cookies',
                necessaryDescription: 'Deze cookies zijn essentieel voor de basisfunctionaliteit van de website en kunnen niet worden uitgeschakeld.',
                preferencesDescription: 'Deze cookies stellen ons in staat om uw keuzes en voorkeuren te onthouden, zoals taalvoorkeur of de regio waar u zich bevindt.',
                statisticsDescription: 'Deze cookies helpen ons te begrijpen hoe bezoekers met onze website interageren door informatie anoniem te verzamelen en te rapporteren.',
                marketingDescription: 'Deze cookies worden gebruikt om bezoekers op websites te volgen. Het doel is om advertenties weer te geven die relevant en aantrekkelijk zijn voor de individuele gebruiker.',
                detailsIntro: 'Hier volgen meer details over de cookies die we gebruiken en hun doeleinden.',
                whatAreCookies: 'Wat zijn cookies?',
                cookiesDefinition: 'Cookies zijn kleine tekstbestanden die op uw computer of mobiele apparaat worden opgeslagen wanneer u een website bezoekt. Ze stellen de site in staat om uw acties en voorkeuren gedurende een bepaalde periode te onthouden.',
                howWeUseCookies: 'Hoe gebruiken we cookies?',
                cookiesUsage: 'We gebruiken verschillende soorten cookies om onze website te laten werken, uw gebruikerservaring te verbeteren, te analyseren hoe de site wordt gebruikt en de inhoud en advertenties die u ziet te personaliseren.',
                moreInfo: 'Meer informatie',
                moreInfoText: 'Voor meer informatie over ons gebruik van cookies, raadpleeg ons privacybeleid en gebruiksvoorwaarden.',
                necessaryOnly: 'Alleen noodzakelijke',
                savePreferences: 'Voorkeuren opslaan'
            },
            de: {
                cookiePreferences: 'Cookie-Einstellungen',
                cookieIntro: 'Als Besucher unserer Website versuchen wir, Ihnen ein möglichst angenehmes Erlebnis zu bieten. Wir verwenden Cookies in erster Linie, um Ihre Benutzererfahrung zu verbessern und die Funktionsweise unserer Online-Dienste zu optimieren. Darüber hinaus verwenden wir Cookies, um die Inhalte unserer Websites und (mobilen) Anwendungen für Sie interessanter zu gestalten. Wir verwenden auch Cookies, um Ihr Surfverhalten zu erfassen.',
                tabOverview: 'Übersicht',
                tabDetails: 'Details',
                tabAbout: 'Über',
                necessaryCookies: 'Notwendige Cookies',
                preferencesCookies: 'Präferenz-Cookies',
                statisticsCookies: 'Statistik-Cookies',
                marketingCookies: 'Marketing-Cookies',
                necessaryDescription: 'Diese Cookies sind für die grundlegende Funktionalität der Website unerlässlich und können nicht deaktiviert werden.',
                preferencesDescription: 'Diese Cookies ermöglichen es uns, Ihre Auswahl und Präferenzen zu speichern, wie z.B. Spracheinstellungen oder die Region, in der Sie sich befinden.',
                statisticsDescription: 'Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren, indem sie Informationen anonym sammeln und melden.',
                marketingDescription: 'Diese Cookies werden verwendet, um Besucher auf Websites zu verfolgen. Ziel ist es, Anzeigen zu schalten, die für den einzelnen Nutzer relevant und ansprechend sind.',
                detailsIntro: 'Hier sind weitere Details zu den Cookies, die wir verwenden, und ihren Zwecken.',
                whatAreCookies: 'Was sind Cookies?',
                cookiesDefinition: 'Cookies sind kleine Textdateien, die auf Ihrem Computer oder Mobilgerät gespeichert werden, wenn Sie eine Website besuchen. Sie ermöglichen es der Website, Ihre Aktionen und Präferenzen für einen bestimmten Zeitraum zu speichern.',
                howWeUseCookies: 'Wie verwenden wir Cookies?',
                cookiesUsage: 'Wir verwenden verschiedene Arten von Cookies, um unsere Website zu betreiben, Ihre Benutzererfahrung zu verbessern, zu analysieren, wie die Website genutzt wird, und die Inhalte und Werbung, die Sie sehen, zu personalisieren.',
                moreInfo: 'Weitere Informationen',
                moreInfoText: 'Für weitere Informationen über unsere Verwendung von Cookies, konsultieren Sie bitte unsere Datenschutzrichtlinie und Nutzungsbedingungen.',
                necessaryOnly: 'Nur notwendige',
                savePreferences: 'Einstellungen speichern'
            }
        };
        
        return translations[lang]?.[key] || translations.fr[key];
    }
    
    // Exposer les fonctions pour permettre l'interaction externe
    window.cookieManager = {
        showModal: showCookieModal,
        getConfig: function() {
            return {...cookieConfig};
        }
    };
});