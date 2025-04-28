const puppeteer = require('puppeteer');
const fs = require('fs');
const yaml = require('js-yaml');

(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    // Charger les cookies de session
    const cookies = [
        {
            "name": "PHPSESSID",
            "value": "rf4i78demd35oe8a7t5j796r5u",
            "domain": "server.dmqode.be",
            "path": "/",
            "expires": -1,
            "httpOnly": true,
            "secure": true,
            "sameSite": "Lax"
        },
        {
            "name": "main_auth_profile_token",
            "value": "30e95d",
            "domain": "server.dmqode.be",
            "path": "/",
            "expires": -1,
            "httpOnly": true,
            "secure": true,
            "sameSite": "Lax"
        }
    ];
    await page.setCookie(...cookies);

    // Liste des routes à vérifier
    const routes = [
        "/admin/users",
        "/admin/users/pending-verification",
        "/admin/users/pending-approval",
        "/admin/users/{id}/verify",
        "/admin/users/{id}/approve",
        "/admin/users/{id}/edit",
        "/admin/users/{id}/roles",
        "/admin/users/{id}/permissions",
        "/admin/users/{id}/delete",
        "/admin/audit-logs/",
        "/admin/audit-logs/user/{id}",
        "/admin/audit-logs/export",
        "/admin/audit-logs/delete-bulk",
        "/admin/audit-logs/http-errors",
        "/browser-detection",
        "/cookie/preferences",
        "/api/cookie/consent",
        "/api/cookie/translations/{locale}",
        "/dashboard",
        "/admin/email-templates/",
        "/admin/email-templates/new",
        "/admin/email-templates/{id}/edit",
        "/admin/email-templates/{id}/preview",
        "/admin/email-templates/{id}/delete",
        "/admin/email-templates/get-template-by-code-locale",
        "/",
        "/profile",
        "/profile/edit",
        "/profile/change-password",
        "/register",
        "/verify/email",
        "/reset-password",
        "/reset-password/check-email",
        "/reset-password/reset/{token}",
        "/login",
        "/logout",
        "/terms",
        "/terms/modal"
    ];

    // Charger les traductions manquantes
    const missingTranslations = yaml.load(fs.readFileSync('/var/www/server/user-management-system/translations/missing_translations.fr.yaml', 'utf8'));
    const existingTranslations = yaml.load(fs.readFileSync('/var/www/server/user-management-system/translations/messages.fr.yaml', 'utf8'));

    for (const route of routes) {
        try {
            await page.goto(`https://server.dmqode.be${route}`);
            console.log(`Navigating to ${route}`);
            for (const key in missingTranslations) {
                if (!existingTranslations[key]) {
                    console.log(`Searching for key: ${key}`);
                    const text = await page.evaluate((key) => {
                        const element = Array.from(document.querySelectorAll('*')).find(el => el.innerText.includes(key));
                        return element ? element.innerText : null;
                    }, key);
                    if (text) {
                        console.log(`Found text for key ${key}: ${text}`);
                        existingTranslations[key] = text;
                    } else {
                        console.log(`Text not found for key ${key}`);
                    }
                }
            }
        } catch (error) {
            console.error(`Erreur lors de la navigation vers ${route}:`, error);
        }
    }

    fs.writeFileSync('/var/www/server/user-management-system/translations/messages.fr.yaml', yaml.dump(existingTranslations));
    await browser.close();
    console.log("Les traductions manquantes ont été pré-remplies avec succès.");
})();
