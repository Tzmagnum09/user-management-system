/**
 * Script de débogage pour les logs d'audit
 */
document.addEventListener('DOMContentLoaded', function() {
    // Obtenir et afficher l'User-Agent actuel
    console.log('------ DEBUG INFO ------');
    console.log('Current User-Agent: ' + navigator.userAgent);
    
    // Afficher l'appareil et le navigateur
    console.log('Using device detection:');
    
    // Détection mobile/desktop
    const isMobile = /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    console.log('Device type: ' + (isMobile ? 'Mobile' : 'Desktop'));
    
    // Détection de l'OS
    let os = 'Unknown';
    if (/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
        os = 'iOS';
    } else if (/Android/i.test(navigator.userAgent)) {
        os = 'Android';
    } else if (/Mac/i.test(navigator.userAgent)) {
        os = 'macOS';
    } else if (/Windows/i.test(navigator.userAgent)) {
        os = 'Windows';
    } else if (/Linux/i.test(navigator.userAgent)) {
        os = 'Linux';
    }
    console.log('Operating System: ' + os);
    
    // Détection du navigateur
    let browser = 'Unknown';
    if (/Edg/i.test(navigator.userAgent)) {
        browser = 'Edge';
    } else if (/Chrome/i.test(navigator.userAgent)) {
        browser = 'Chrome';
    } else if (/Firefox/i.test(navigator.userAgent)) {
        browser = 'Firefox';
    } else if (/Safari/i.test(navigator.userAgent)) {
        browser = 'Safari';
    } else if (/MSIE|Trident/i.test(navigator.userAgent)) {
        browser = 'Internet Explorer';
    }
    console.log('Browser: ' + browser);
    
    // Pour le débogage du User-Agent
    const toggleDebugBtn = document.getElementById('toggleUserAgentDebug');
    if (toggleDebugBtn) {
        toggleDebugBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const currentUrl = new URL(window.location.href);
            const debugParam = currentUrl.searchParams.get('debug');
            
            if (debugParam === '1') {
                // Désactiver le mode debug
                currentUrl.searchParams.delete('debug');
            } else {
                // Activer le mode debug
                currentUrl.searchParams.set('debug', '1');
            }
            
            // Recharger la page avec/sans mode debug
            window.location.href = currentUrl.toString();
        });
    }
});