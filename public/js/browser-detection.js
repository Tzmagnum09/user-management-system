/**
 * Script de détection du navigateur et des informations client
 * 
 * Ce script envoie les informations complètes sur le navigateur et l'appareil utilisé
 * en les ajoutant aux en-têtes des requêtes AJAX pour une détection plus précise
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Browser detection script initialized');
    
    // Obtenir et enregistrer l'User-Agent complet
    const userAgent = navigator.userAgent;
    console.log('User-Agent: ' + userAgent);
    
    // Obtenir des informations supplémentaires
    const platform = navigator.platform || '';
    const languages = navigator.languages ? navigator.languages.join(',') : (navigator.language || '');
    const screenSize = window.screen.width + 'x' + window.screen.height;
    
    // Détections avancées
    const isMobile = /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
    const deviceType = isMobile ? 'Mobile' : 'Desktop';
    
    // Détecter le navigateur
    let browserName = 'Unknown';
    let browserVersion = '';
    
    // Expressions régulières pour la détection des navigateurs
    const edgeRx = /(Edge|Edg)\/(\d+(\.\d+)?)/i;
    const firefoxRx = /Firefox\/(\d+(\.\d+)?)/i;
    const chromeRx = /Chrome\/(\d+(\.\d+)?)/i;
    const safariRx = /Safari\/(\d+(\.\d+)?)/i;
    const safariVersionRx = /Version\/(\d+(\.\d+)?)/i;
    const ieRx = /(MSIE |Trident\/.*rv:)(\d+(\.\d+)?)/i;
    
    if (edgeRx.test(userAgent)) {
        const match = userAgent.match(edgeRx);
        browserName = 'Edge';
        browserVersion = match[2];
    } else if (firefoxRx.test(userAgent)) {
        const match = userAgent.match(firefoxRx);
        browserName = 'Firefox';
        browserVersion = match[1];
    } else if (chromeRx.test(userAgent) && !/(Edg|OPR|Opera)/i.test(userAgent)) {
        const match = userAgent.match(chromeRx);
        browserName = 'Chrome';
        browserVersion = match[1];
    } else if (safariRx.test(userAgent) && !chromeRx.test(userAgent)) {
        browserName = 'Safari';
        if (safariVersionRx.test(userAgent)) {
            const match = userAgent.match(safariVersionRx);
            browserVersion = match[1];
        }
    } else if (ieRx.test(userAgent)) {
        const match = userAgent.match(ieRx);
        browserName = 'Internet Explorer';
        browserVersion = match[2];
    }
    
    // Détecter le système d'exploitation
    let osName = 'Unknown';
    let osVersion = '';
    
    if (/Windows NT (\d+\.\d+)/i.test(userAgent)) {
        osName = 'Windows';
        const winVersions = {
            '10.0': '10',
            '6.3': '8.1',
            '6.2': '8',
            '6.1': '7',
            '6.0': 'Vista',
            '5.2': 'XP',
            '5.1': 'XP'
        };
        const match = userAgent.match(/Windows NT (\d+\.\d+)/i);
        osVersion = winVersions[match[1]] || match[1];
    } else if (/Macintosh.*Mac OS X (\d+[._]\d+)/i.test(userAgent)) {
        osName = 'macOS';
        const match = userAgent.match(/Mac OS X (\d+[._]\d+[._]?\d*)/i);
        osVersion = match[1].replace(/_/g, '.');
    } else if (/Linux/i.test(userAgent)) {
        osName = 'Linux';
        if (/Ubuntu/i.test(userAgent)) {
            osName = 'Ubuntu';
        } else if (/Fedora/i.test(userAgent)) {
            osName = 'Fedora';
        } else if (/Debian/i.test(userAgent)) {
            osName = 'Debian';
        }
    } else if (/Android (\d+(\.\d+)*)/i.test(userAgent)) {
        osName = 'Android';
        const match = userAgent.match(/Android (\d+(\.\d+)*)/i);
        osVersion = match[1];
    } else if (/(iPhone|iPad|iPod).*OS (\d+[._]\d+)/i.test(userAgent)) {
        osName = 'iOS';
        const match = userAgent.match(/OS (\d+[._]\d+[._]?\d*)/i);
        osVersion = match[1].replace(/_/g, '.');
    }
    
    // Créer un objet avec les informations détectées
    const browserInfo = {
        userAgent: userAgent,
        platform: platform,
        languages: languages,
        screenSize: screenSize,
        deviceType: deviceType,
        browser: {
            name: browserName,
            version: browserVersion
        },
        os: {
            name: osName,
            version: osVersion
        }
    };
    
    // Stocker les informations dans localStorage pour les réutiliser
    localStorage.setItem('browserInfo', JSON.stringify(browserInfo));
    
    // Envoyer les informations dans un cookie
    document.cookie = 'browser_info=' + encodeURIComponent(JSON.stringify(browserInfo)) + '; path=/; SameSite=Lax';
    
    // Intercepter toutes les requêtes AJAX/fetch pour ajouter l'user-agent détaillé
    const originalFetch = window.fetch;
    window.fetch = function(resource, options) {
        options = options || {};
        options.headers = options.headers || {};
        
        // Ajouter des en-têtes personnalisés pour améliorer la détection
        options.headers['X-Browser-Name'] = browserName;
        options.headers['X-Browser-Version'] = browserVersion;
        options.headers['X-OS-Name'] = osName;
        options.headers['X-OS-Version'] = osVersion;
        options.headers['X-Device-Type'] = deviceType;
        options.headers['X-Original-User-Agent'] = userAgent;
        
        return originalFetch.call(this, resource, options);
    };
    
    // Intercepter les requêtes XMLHttpRequest
    const originalXhrOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        const result = originalXhrOpen.apply(this, arguments);
        
        // Ajouter des en-têtes personnalisés
        this.setRequestHeader('X-Browser-Name', browserName);
        this.setRequestHeader('X-Browser-Version', browserVersion);
        this.setRequestHeader('X-OS-Name', osName);
        this.setRequestHeader('X-OS-Version', osVersion);
        this.setRequestHeader('X-Device-Type', deviceType);
        this.setRequestHeader('X-Original-User-Agent', userAgent);
        
        return result;
    };
    
    // Envoyer les informations de détection au serveur immédiatement
    fetch('/browser-detection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Browser-Name': browserName,
            'X-Browser-Version': browserVersion,
            'X-OS-Name': osName,
            'X-OS-Version': osVersion,
            'X-Device-Type': deviceType,
            'X-Original-User-Agent': userAgent
        },
        body: JSON.stringify(browserInfo)
    }).catch(error => {
        // Ignorer les erreurs potentielles si l'endpoint n'existe pas
        console.log('Info: Browser detection endpoint might not be available');
    });
});