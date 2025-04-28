<?php

namespace App\Service;

use App\Entity\EmailTemplate;
use App\Entity\User;
use App\Repository\EmailTemplateRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailManager
{
    private MailerInterface $mailer;
    private EmailTemplateRepository $emailTemplateRepository;
    private Environment $twig;
    private ParameterBagInterface $params;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        EmailTemplateRepository $emailTemplateRepository,
        Environment $twig,
        ParameterBagInterface $params
    ) {
        $this->mailer = $mailer;
        $this->emailTemplateRepository = $emailTemplateRepository;
        $this->twig = $twig;
        $this->params = $params;
        $this->senderEmail = $params->get('app.email_sender_address');
        $this->senderName = $params->get('app.email_sender_name');
    }

    /**
     * Send an email using a template from the database.
     */
    public function sendTemplatedEmail(string $templateCode, string $recipientEmail, array $variables = [], ?string $locale = null): void
    {
        // Use the user's locale if provided, or the default app locale
        $locale = $locale ?? $this->params->get('app.locale');

        // Find the template
        $template = $this->emailTemplateRepository->findByCodeAndLocale($templateCode, $locale);

        // If template not found in the specified locale, fallback to default locale
        if (!$template && $locale !== $this->params->get('app.locale')) {
            $template = $this->emailTemplateRepository->findByCodeAndLocale($templateCode, $this->params->get('app.locale'));
        }

        if (!$template) {
            throw new \RuntimeException(sprintf('Email template with code "%s" and locale "%s" not found.', $templateCode, $locale));
        }

        // Replace variables in the subject and content
        $subject = $this->replaceVariables($template->getSubject(), $variables);
        $htmlContent = $this->replaceVariables($template->getHtmlContent(), $variables);
        
        // Ensure HTML content is properly formatted
        $htmlContent = $this->prepareHtmlContent($htmlContent);
        
        // Apply Outlook-compatible styling
        $htmlContent = $this->makeOutlookCompatible($htmlContent);
        
        // Create plain text version for email clients that prefer it
        $textContent = strip_tags(str_replace(['<br>', '<br />', '</p>'], "\n", $htmlContent));

        // Create and send email
        $email = new Email();
        $email
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($recipientEmail)
            ->subject($subject)
            ->html($htmlContent)
            ->text($textContent);

        $this->mailer->send($email);
    }
    
    /**
     * Apply fixes to make HTML email compatible with Outlook
     */
    private function makeOutlookCompatible(string $content): string
    {
        // 1. Remplacer les styles gradient par des couleurs solides pour Outlook
        $content = preg_replace(
            '/background:\s*linear-gradient\s*\([^)]+\);/i',
            'background: #8e44ad; mso-highlight: #8e44ad;',
            $content
        );
        
        // 2. Ajouter une balise MSO conditional pour Outlook
        if (!str_contains($content, '<!--[if mso]>')) {
            $outlookStyles = '
<!--[if mso]>
<style type="text/css">
    /* Styles spécifiques pour Outlook */
    .container { width: 600px; }
    .header { background-color: #8e44ad !important; }
    .button { background-color: #8e44ad !important; color: white !important; text-decoration: none; padding: 10px 20px; }
    .details-box { background-color: #f1f1f1 !important; }
    table { border-collapse: collapse; }
    td, th { padding: 8px; }
</style>
<![endif]-->';
            
            // Insérer les styles conditionnels MSO dans la section head
            if (preg_match('/<head>(.*?)<\/head>/is', $content, $matches)) {
                $newHead = '<head>' . $matches[1] . $outlookStyles . '</head>';
                $content = str_replace($matches[0], $newHead, $content);
            } else if (preg_match('/<html>(.*)/is', $content, $matches)) {
                // Si pas de balise head, ajouter une
                $newHtml = '<html><head>' . $outlookStyles . '</head>' . $matches[1];
                $content = str_replace($matches[0], $newHtml, $content);
            } else {
                // Si pas de structure HTML complète, ajouter au début
                $content = '<!DOCTYPE html><html><head>' . $outlookStyles . '</head><body>' . $content . '</body></html>';
            }
        }
        
        // 3. Ajouter des styles inline spécifiques pour les modifications de permission
        $content = str_replace(
            'Détails des modifications',
            '<span style="display:block;font-weight:bold;margin-bottom:10px;color:#333333;">Détails des modifications</span>',
            $content
        );
        
        // 4. Traitez spécifiquement la variable permissionChanges pour en faire un tableau HTML
        if (strpos($content, '%permissionChanges%') !== false || strpos($content, '{{ permissionChanges }}') !== false) {
            // Chercher les balises contenant les changements de permission
            $pattern = '/%permissionChanges%|{{ permissionChanges }}|{{permissionChanges}}/';
            
            if (preg_match($pattern, $content)) {
                // Voir si on peut trouver un élément parent qui pourrait être structuré comme un tableau
                if (strpos($content, '<table') === false) {
                    // S'il n'y a pas de table, préparez le terrain pour insérer un tableau
                    $content = str_replace(
                        ['%permissionChanges%', '{{ permissionChanges }}', '{{permissionChanges}}'],
                        '<table width="100%" cellpadding="5" cellspacing="0" border="0" style="border-collapse:collapse;margin:10px 0;background-color:#f8f9fa;">' .
                        '<tr style="background-color:#f0f0f0;">' .
                        '<th style="padding:8px;text-align:left;border-bottom:2px solid #ddd;width:40%;">Permission</th>' .
                        '<th style="padding:8px;text-align:left;border-bottom:2px solid #ddd;width:30%;">Ancien état</th>' .
                        '<th style="padding:8px;text-align:left;border-bottom:2px solid #ddd;width:30%;">Nouvel état</th>' .
                        '</tr>' .
                        '%permissionChanges%' .
                        '</table>',
                        $content
                    );
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Prepare HTML content for email sending.
     */
    private function prepareHtmlContent(string $content): string
    {
        // Make sure the HTML is properly formatted as a complete document
        if (!str_contains($content, '<!DOCTYPE') && !str_contains($content, '<html')) {
            $content = $this->wrapInHtmlDocument($content);
        }
        
        return $content;
    }
    
    /**
     * Wrap content in a complete HTML document structure.
     */
    private function wrapInHtmlDocument(string $content): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email</title>
    <!--[if mso]>
    <style type="text/css">
        /* Styles spécifiques pour Outlook */
        .container { width: 600px; }
        .header { background-color: #8e44ad !important; }
        .button { background-color: #8e44ad !important; color: white !important; text-decoration: none; padding: 10px 20px; }
        .content { background-color: #f9f9f9 !important; }
        table { border-collapse: collapse; }
    </style>
    <![endif]-->
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #8e44ad, #3498db);
            background-color: #8e44ad;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #8e44ad, #3498db);
            background-color: #8e44ad;
            color: white !important;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        td, th {
            padding: 8px;
            text-align: left;
        }
        .permission-table {
            border: 1px solid #ddd;
            margin: 15px 0;
        }
        .permission-table th {
            background-color: #f2f2f2;
            border-bottom: 2px solid #ddd;
        }
        .permission-table td {
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        ' . $content . '
    </div>
</body>
</html>';
    }

    /**
     * Send an email to a user using a template.
     */
    public function sendEmailToUser(User $user, string $templateCode, array $variables = []): void
    {
        // Si nous avons des changements de permission à formater pour un tableau HTML
        if (isset($variables['permissionChanges'])) {
            $permissionChangesHtml = $this->formatPermissionChangesForEmail($variables['permissionChanges']);
            $variables['permissionChanges'] = $permissionChangesHtml;
        }

        $this->sendTemplatedEmail(
            $templateCode,
            $user->getEmail(),
            array_merge($variables, [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'username' => $user->getUsername(),
                'domain' => str_replace(['http://', 'https://'], '', $this->params->get('app.url')),
                'app_name' => $this->params->get('app.name', 'Dmqode.be'),
            ]),
            $user->getLocale()
        );
    }

    /**
     * Format permission changes to be displayed in an HTML table
     */
    private function formatPermissionChangesForEmail(string $permissionChanges): string
    {
        // Diviser les changements par ligne
        $changes = explode('<br>', $permissionChanges);
        $formattedRows = [];
        
        foreach ($changes as $change) {
            // Extraire les parties du changement (nom de la permission, ancien état, nouvel état)
            if (preg_match('/^([^:]+):\s*([^→]+)→\s*(.+)$/', $change, $matches)) {
                $permissionName = trim($matches[1]);
                $oldValue = trim($matches[2]);
                $newValue = trim($matches[3]);
                
                $formattedRows[] = '<tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px; font-weight: bold;">' . $permissionName . '</td>
                    <td style="padding: 8px;"><span style="color:#999;text-decoration:line-through;">' . $oldValue . '</span></td>
                    <td style="padding: 8px;"><span style="color:#2980b9;font-weight:bold;">' . $newValue . '</span></td>
                </tr>';
            } 
            // Traiter les nouveaux ajouts qui ont un format différent
            elseif (preg_match('/^([^:]+):\s*([^(]+)\s*\(nouvel ajout\)$/', $change, $matches)) {
                $permissionName = trim($matches[1]);
                $newValue = trim($matches[2]);
                
                $formattedRows[] = '<tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 8px; font-weight: bold;">' . $permissionName . '</td>
                    <td style="padding: 8px;"><span style="color:#999;">-</span></td>
                    <td style="padding: 8px;"><span style="color:#2980b9;font-weight:bold;">' . $newValue . ' <em style="font-size:0.9em;">(Nouveau)</em></span></td>
                </tr>';
            }
            // Si le format ne correspond à aucun des modèles, conserver le texte brut
            elseif (!empty(trim($change))) {
                $formattedRows[] = '<tr>
                    <td colspan="3" style="padding: 8px;">' . $change . '</td>
                </tr>';
            }
        }
        
        // S'il n'y a pas de lignes formatées, retourner le texte d'origine
        if (empty($formattedRows)) {
            return $permissionChanges;
        }
        
        return implode("\n", $formattedRows);
    }

    /**
     * Replace variables in a string.
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Format des variables sous différentes formes pour assurer la compatibilité
            $formats = [
                '%' . $key . '%',           // Format %key%
                '{{ ' . $key . ' }}',       // Format {{ key }}
                '{{' . $key . '}}',         // Format {{key}}
                '{{ ' . $key . ' }}',       // Format {{ key }}
            ];
            
            foreach ($formats as $format) {
                $content = str_replace($format, $value, $content);
            }
        }

        return $content;
    }

    /**
     * Get all available templates grouped by code.
     */
    public function getAllTemplatesGrouped(): array
    {
        $result = [];
        $templates = $this->emailTemplateRepository->findAll();

        foreach ($templates as $template) {
            $result[$template->getCode()][$template->getLocale()] = $template;
        }

        return $result;
    }

    /**
     * Create default email templates for all supported locales.
     */
    public function createDefaultTemplates(): void
    {
        $locales = explode(',', $this->params->get('app.supported_locales'));
        $defaultTemplates = $this->getDefaultTemplates();

        foreach ($defaultTemplates as $code => $templates) {
            foreach ($locales as $locale) {
                if (!$this->emailTemplateRepository->findByCodeAndLocale($code, $locale)) {
                    if (isset($templates[$locale])) {
                        // Fournir des variables de test pour le rendu des templates
                        $sampleVariables = [
                            'firstName' => 'John',
                            'lastName' => 'Doe',
                            'signedUrl' => 'https://example.com/verify/email?token=sample-token',
                            'resetToken' => 'https://example.com/reset-password/sample-token',
                            'previousRole' => 'User',
                            'newRole' => 'Administrator',
                            'permissionChanges' => 'view_users: Denied → Granted<br>edit_users: Denied → Granted',
                            'domain' => str_replace(['http://', 'https://'], '', $this->params->get('app.url')),
                            'app_name' => $this->params->get('app.name', 'Dmqode.be'),
                        ];
                        
                        // Rendre le template avec les variables de test
                        $content = $this->twig->render(
                            $templates[$locale]['template'], 
                            $sampleVariables
                        );
                        
                        // Convertir les variables twig au format %variable%
                        $content = preg_replace('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', '%$1%', $content);
                        
                        $template = new EmailTemplate();
                        $template->setCode($code);
                        $template->setSubject($templates[$locale]['subject']);
                        $template->setHtmlContent($content);
                        $template->setLocale($locale);

                        $this->emailTemplateRepository->save($template, true);
                    }
                }
            }
        }
    }

    /**
     * Get default templates for all supported email types and locales.
     */
    private function getDefaultTemplates(): array
    {
        return [
            'registration_confirmation' => [
                'fr' => [
                    'subject' => 'Confirmation de votre inscription',
                    'template' => 'emails/default/fr/registration_confirmation.html.twig',
                ],
                'en' => [
                    'subject' => 'Registration confirmation',
                    'template' => 'emails/default/en/registration_confirmation.html.twig',
                ],
                'nl' => [
                    'subject' => 'Bevestiging van uw inschrijving',
                    'template' => 'emails/default/nl/registration_confirmation.html.twig',
                ],
                'de' => [
                    'subject' => 'Bestätigung Ihrer Anmeldung',
                    'template' => 'emails/default/de/registration_confirmation.html.twig',
                ],
            ],
            'account_approved' => [
                'fr' => [
                    'subject' => 'Votre compte a été approuvé',
                    'template' => 'emails/default/fr/account_approved.html.twig',
                ],
                'en' => [
                    'subject' => 'Your account has been approved',
                    'template' => 'emails/default/en/account_approved.html.twig',
                ],
                'nl' => [
                    'subject' => 'Uw account is goedgekeurd',
                    'template' => 'emails/default/nl/account_approved.html.twig',
                ],
                'de' => [
                    'subject' => 'Ihr Konto wurde genehmigt',
                    'template' => 'emails/default/de/account_approved.html.twig',
                ],
            ],
            'reset_password' => [
                'fr' => [
                    'subject' => 'Réinitialisation de votre mot de passe',
                    'template' => 'emails/default/fr/reset_password.html.twig',
                ],
                'en' => [
                    'subject' => 'Reset your password',
                    'template' => 'emails/default/en/reset_password.html.twig',
                ],
                'nl' => [
                    'subject' => 'Reset uw wachtwoord',
                    'template' => 'emails/default/nl/reset_password.html.twig',
                ],
                'de' => [
                    'subject' => 'Setzen Sie Ihr Passwort zurück',
                    'template' => 'emails/default/de/reset_password.html.twig',
                ],
            ],
            'role_change' => [
                'fr' => [
                    'subject' => 'Changement de votre rôle',
                    'template' => 'emails/default/fr/role_change.html.twig',
                ],
                'en' => [
                    'subject' => 'Your role has changed',
                    'template' => 'emails/default/en/role_change.html.twig',
                ],
                'nl' => [
                    'subject' => 'Wijziging van uw rol',
                    'template' => 'emails/default/nl/role_change.html.twig',
                ],
                'de' => [
                    'subject' => 'Änderung Ihrer Rolle',
                    'template' => 'emails/default/de/role_change.html.twig',
                ],
            ],
            'permission_update' => [
                'fr' => [
                    'subject' => 'Mise à jour de vos permissions',
                    'template' => 'emails/default/fr/permission_update.html.twig',
                ],
                'en' => [
                    'subject' => 'Your permissions have been updated',
                    'template' => 'emails/default/en/permission_update.html.twig',
                ],
                'nl' => [
                    'subject' => 'Uw machtigingen zijn bijgewerkt',
                    'template' => 'emails/default/nl/permission_update.html.twig',
                ],
                'de' => [
                    'subject' => 'Ihre Berechtigungen wurden aktualisiert',
                    'template' => 'emails/default/de/permission_update.html.twig',
                ],
            ],
        ];
    }
}