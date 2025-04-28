/**
 * Script de gestion du changement de langue
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les écouteurs d'événements pour les liens de langue
    initLanguageSwitcher();

    function initLanguageSwitcher() {
        // Sélecteurs pour les liens de langue
        const languageLinks = document.querySelectorAll('.language-switcher a, #languageDropdown + .dropdown-menu a');
        
        languageLinks.forEach(link => {
            link.addEventListener('click', function(event) {
                // Les liens de langue sont gérés côté serveur, mais on peut ajouter une logique supplémentaire ici
                
                // Déclencher un événement personnalisé que d'autres scripts peuvent écouter
                const localeMatch = this.getAttribute('href').match(/\/change-language\/([a-z]{2})/);
                if (localeMatch && localeMatch[1]) {
                    const locale = localeMatch[1];
                    
                    const event = new CustomEvent('languageChanged', {
                        detail: {
                            locale: locale,
                            source: 'site-language-switcher'
                        },
                        bubbles: true
                    });
                    
                    // Diffuser l'événement
                    document.dispatchEvent(event);
                    
                    // Stocker la préférence dans localStorage
                    localStorage.setItem('selectedLocale', locale);
                    
                    // La navigation se fait naturellement par le lien
                }
            });
        });
        
        // Écouter les changements depuis les modales de cookies
        document.addEventListener('cookieLanguageChanged', function(event) {
            if (event.detail && event.detail.locale) {
                // Stocker la préférence dans localStorage
                localStorage.setItem('selectedLocale', event.detail.locale);
            }
        });
    }
});