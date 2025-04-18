<?php

namespace App\Service;

use App\Entity\EmailTemplate;
use App\Entity\User;
use App\Repository\EmailTemplateRepository;
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
    </style>
</head>
<body>
    ' . $content . '
</body>
</html>';
    }

    /**
     * Send an email to a user using a template.
     */
    public function sendEmailToUser(User $user, string $templateCode, array $variables = []): void
    {
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