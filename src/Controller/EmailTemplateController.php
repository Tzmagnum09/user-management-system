<?php

namespace App\Controller;

use App\Entity\EmailTemplate;
use App\Entity\User;
use App\Form\EmailTemplateType;
use App\Repository\EmailTemplateRepository;
use App\Service\AuditLogger;
use App\Service\EmailManager;
use App\Service\PermissionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

#[Route('/admin/email-templates')]
class EmailTemplateController extends AbstractController
{
    private EmailManager $emailManager;
    private AuditLogger $auditLogger;
    private PermissionManager $permissionManager;
    private EntityManagerInterface $entityManager;
    private Environment $twig;
    private EmailTemplateRepository $emailTemplateRepository;

    public function __construct(
        EmailManager $emailManager,
        AuditLogger $auditLogger,
        PermissionManager $permissionManager,
        EntityManagerInterface $entityManager,
        Environment $twig,
        EmailTemplateRepository $emailTemplateRepository
    ) {
        $this->emailManager = $emailManager;
        $this->auditLogger = $auditLogger;
        $this->permissionManager = $permissionManager;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->emailTemplateRepository = $emailTemplateRepository;
    }

    #[Route('/', name: 'admin_email_templates')]
    public function index(EmailTemplateRepository $emailTemplateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_email_templates')) {
                throw $this->createAccessDeniedException('You do not have permission to view email templates.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'view_email_templates',
            'Consultation de la liste des templates d\'email'
        );

        // Get templates grouped by code
        $templates = $this->emailManager->getAllTemplatesGrouped();

        return $this->render('admin/email_templates/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/new', name: 'admin_email_template_new')]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'edit_email_templates')) {
                throw $this->createAccessDeniedException('You do not have permission to edit email templates.');
            }
        }

        $emailTemplate = new EmailTemplate();
        
        // Set default template based on type and locale
        $templateCode = $request->query->get('code', 'registration_confirmation');
        $locale = $request->query->get('locale', $this->getUser()->getLocale() ?? 'fr');
        
        // Check if we received a code and locale from the query parameters
        if ($templateCode && $locale) {
            $emailTemplate->setCode($templateCode);
            $emailTemplate->setLocale($locale);
            
            // Try to get a default subject based on the code and locale
            $defaultSubject = $this->getDefaultSubject($templateCode, $locale);
            if ($defaultSubject) {
                $emailTemplate->setSubject($defaultSubject);
            }
            
            // Set default HTML content
            $emailTemplate->setHtmlContent($this->getDefaultTemplate($templateCode, $locale));
        }
        
        $form = $this->createForm(EmailTemplateType::class, $emailTemplate, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le formulaire est soumis et valide
            
            // Vérifier si un code personnalisé est soumis via le formulaire
            $templateSelectionValue = $form->has('template_selection') 
                ? $form->get('template_selection')->getData() 
                : null;
                
            // Si on a sélectionné une option prédéfinie (différent de 'custom')
            // et que ce n'est pas null, on l'utilise comme code
            if ($templateSelectionValue && $templateSelectionValue !== 'custom') {
                $emailTemplate->setCode($templateSelectionValue);
            }
            // Sinon on utilise le code personnalisé saisi (déjà mappé à l'entité)

            // Check if this exact combination of code and locale already exists
            $existing = $this->emailTemplateRepository->findByCodeAndLocale(
                $emailTemplate->getCode(),
                $emailTemplate->getLocale()
            );
            
            // If it exists and this is a new template (not editing), show an error
            if ($existing) {
                $this->addFlash('error', 'Un modèle avec ce code et cette langue existe déjà. Veuillez modifier le modèle existant ou choisir une autre combinaison.');
                
                return $this->render('admin/email_templates/new.html.twig', [
                    'email_template' => $emailTemplate,
                    'form' => $form->createView(),
                    'is_new' => true
                ]);
            }
            
            $this->entityManager->persist($emailTemplate);
            $this->entityManager->flush();

            // Log the action
            $this->auditLogger->log(
                $this->getUser(),
                'create_email_template',
                sprintf('Created email template "%s" for locale "%s"', $emailTemplate->getCode(), $emailTemplate->getLocale())
            );

            $this->addFlash('success', 'Email template has been created.');

            return $this->redirectToRoute('admin_email_templates');
        }

        return $this->render('admin/email_templates/new.html.twig', [
            'email_template' => $emailTemplate,
            'form' => $form->createView(),
            'is_new' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_email_template_edit')]
    public function edit(Request $request, EmailTemplate $emailTemplate): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'edit_email_templates')) {
                throw $this->createAccessDeniedException('You do not have permission to edit email templates.');
            }
        }

        $form = $this->createForm(EmailTemplateType::class, $emailTemplate, ['is_new' => false]); // Important: définir is_new à false
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Log the action
            $this->auditLogger->log(
                $this->getUser(),
                'edit_email_template',
                sprintf('Edited email template "%s" for locale "%s" (ID: %d)', $emailTemplate->getCode(), $emailTemplate->getLocale(), $emailTemplate->getId())
            );

            $this->addFlash('success', 'Email template has been updated.');

            return $this->redirectToRoute('admin_email_templates');
        }

        // Render the enhanced editor template
        return $this->render('admin/email_templates/edit_enhanced.html.twig', [
            'email_template' => $emailTemplate,
            'form' => $form->createView(),
            'is_new' => false,
        ]);
    }

    #[Route('/{id}/preview', name: 'admin_email_template_preview')]
    public function preview(EmailTemplate $emailTemplate): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_email_templates')) {
                throw $this->createAccessDeniedException('You do not have permission to view email templates.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'preview_email_template',
            sprintf('Prévisualisation du template d\'email "%s" pour la langue "%s" (ID: %d)', $emailTemplate->getCode(), $emailTemplate->getLocale(), $emailTemplate->getId())
        );

        // Create sample variables based on the email code
        $sampleVariables = $this->getSampleVariables($emailTemplate->getCode());

        // Get the HTML content from the template
        $htmlContent = $emailTemplate->getHtmlContent();

        // Replace variables in the content
        foreach ($sampleVariables as $key => $value) {
            $htmlContent = str_replace('%' . $key . '%', $value, $htmlContent);
            $htmlContent = str_replace('{{ ' . $key . ' }}', $value, $htmlContent);
            $htmlContent = str_replace('{{' . $key . '}}', $value, $htmlContent);
        }

        // Make sure the HTML has proper styling by ensuring it's a complete HTML document
        if (!str_contains($htmlContent, '<!DOCTYPE') && !str_contains($htmlContent, '<html')) {
            $htmlContent = $this->wrapInHtmlDocument($htmlContent);
        }

        // Return the HTML directly as a response
        return new Response($htmlContent);
    }

    /**
     * Wrap content in a complete HTML document structure with embedded styles.
     */
    private function wrapInHtmlDocument(string $content): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preview</title>
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

    #[Route('/{id}/delete', name: 'admin_email_template_delete', methods: ['POST'])]
    public function delete(Request $request, EmailTemplate $emailTemplate): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'edit_email_templates')) {
                throw $this->createAccessDeniedException('You do not have permission to delete email templates.');
            }
        }
        
        if (!$this->isCsrfTokenValid('delete'.$emailTemplate->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->entityManager->remove($emailTemplate);
        $this->entityManager->flush();

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'delete_email_template',
            sprintf('Deleted email template "%s" for locale "%s" (ID: %d)', $emailTemplate->getCode(), $emailTemplate->getLocale(), $emailTemplate->getId())
        );

        $this->addFlash('success', 'Email template has been deleted.');

        return $this->redirectToRoute('admin_email_templates');
    }

    /**
     * Récupère un template email par code et locale en AJAX.
     */
    #[Route('/get-template-by-code-locale', name: 'admin_email_template_by_code_locale', methods: ['POST'])]
    public function getTemplateByCodeAndLocale(Request $request, EmailTemplateRepository $emailTemplateRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_email_templates')) {
                throw $this->createAccessDeniedException('You do not have permission to view email templates.');
            }
        }

        // Récupérer les paramètres de la requête
        $code = $request->request->get('code');
        $locale = $request->request->get('locale');

        if (!$code || !$locale) {
            return $this->json(['success' => false, 'message' => 'Code and locale are required'], 400);
        }

        // Rechercher le template dans la base de données
        $template = $emailTemplateRepository->findByCodeAndLocale($code, $locale);

        if (!$template) {
            // Si aucun template n'est trouvé, proposer un template par défaut
            $subject = $this->getDefaultSubject($code, $locale);
            $htmlContent = $this->getDefaultTemplate($code, $locale);
            
            return $this->json([
                'success' => true,
                'template' => [
                    'id' => null,
                    'code' => $code,
                    'subject' => $subject,
                    'htmlContent' => $htmlContent,
                    'locale' => $locale
                ],
                'isDefault' => true
            ]);
        }

        // Retourner les données du template au format JSON
        return $this->json([
            'success' => true,
            'template' => [
                'id' => $template->getId(),
                'code' => $template->getCode(),
                'subject' => $template->getSubject(),
                'htmlContent' => $template->getHtmlContent(),
                'locale' => $template->getLocale()
            ],
            'isDefault' => false
        ]);
    }

    /**
     * Get default subject for a template code and locale.
     */
    private function getDefaultSubject(string $code, string $locale): string
    {
        $subjects = [
            'registration_confirmation' => [
                'fr' => 'Confirmation de votre inscription',
                'en' => 'Registration confirmation',
                'nl' => 'Bevestiging van uw inschrijving',
                'de' => 'Bestätigung Ihrer Anmeldung',
            ],
            'account_approved' => [
                'fr' => 'Votre compte a été approuvé',
                'en' => 'Your account has been approved',
                'nl' => 'Uw account is goedgekeurd',
                'de' => 'Ihr Konto wurde genehmigt',
            ],
            'reset_password' => [
                'fr' => 'Réinitialisation de votre mot de passe',
                'en' => 'Reset your password',
                'nl' => 'Reset uw wachtwoord',
                'de' => 'Setzen Sie Ihr Passwort zurück',
            ],
            'role_change' => [
                'fr' => 'Changement de votre rôle',
                'en' => 'Your role has changed',
                'nl' => 'Wijziging van uw rol',
                'de' => 'Änderung Ihrer Rolle',
            ],
            'permission_update' => [
                'fr' => 'Mise à jour de vos permissions',
                'en' => 'Your permissions have been updated',
                'nl' => 'Uw machtigingen zijn bijgewerkt',
                'de' => 'Ihre Berechtigungen wurden aktualisiert',
            ],
        ];

        return $subjects[$code][$locale] ?? '';
    }

    /**
     * Get a default template content based on code and locale.
     */
    private function getDefaultTemplate(string $code, string $locale): string
    {
        // Check if we can find an existing template with the same code but different locale to use as base
        $existingTemplates = $this->emailTemplateRepository->findByCode($code);
        
        if (!empty($existingTemplates)) {
            // Use the first available template as a base
            return $existingTemplates[0]->getHtmlContent();
        }
        
        // If no existing template is available, generate a default template based on the type
        return $this->generateDefaultTemplate($code, $locale);
    }

    /**
     * Generate a default template structure based on the code.
     */
    private function generateDefaultTemplate(string $code, string $locale): string
    {
        // Get localized labels for the template
        $labels = $this->getLocalizedLabels($locale);
        
        // Base template structure
        $template = '<div class="container">
    <div class="header">
        <h1>%TITLE%</h1>
    </div>
    <div class="content">
        <p>%GREETING% %firstName%,</p>
        
        %CONTENT%
        
        <p style="text-align: center;">
            <a href="%BUTTON_URL%" class="button">%BUTTON_TEXT%</a>
        </p>
        
        %ADDITIONAL_CONTENT%
        
        <p>%SIGNATURE%<br>%TEAM%</p>
    </div>
    <div class="footer">
        <p>%FOOTER%</p>
    </div>
</div>';

        // Template-specific content
        switch ($code) {
            case 'registration_confirmation':
                $title = $labels['registration_title'];
                $greeting = $labels['greeting'];
                $content = '<p>' . $labels['registration_content'] . '</p>';
                $buttonUrl = '{{ signedUrl }}';
                $buttonText = $labels['confirm_email'];
                $additionalContent = '<p>' . $labels['button_alt_text'] . '</p>
        <p>{{ signedUrl }}</p>
        
        <p>' . $labels['link_expires'] . '</p>
        
        <p>' . $labels['if_not_registered'] . '</p>';
                break;
                
            case 'account_approved':
                $title = $labels['approved_title'];
                $greeting = $labels['greeting'];
                $content = '<p>' . $labels['approved_content'] . '</p>
        
        <p>' . $labels['login_prompt'] . '</p>';
                $buttonUrl = 'https://{{ domain }}/login';
                $buttonText = $labels['login'];
                $additionalContent = '<p>' . $labels['questions'] . '</p>';
                break;
                
            case 'reset_password':
                $title = $labels['reset_title'];
                $greeting = $labels['greeting'];
                $content = '<p>' . $labels['reset_content'] . '</p>';
                $buttonUrl = '{{ resetToken }}';
                $buttonText = $labels['reset_password'];
                $additionalContent = '<p>' . $labels['button_alt_text'] . '</p>
        <p>{{ resetToken }}</p>
        
        <p>' . $labels['reset_expires'] . '</p>
        
        <p>' . $labels['if_not_requested'] . '</p>';
                break;
                
            case 'role_change':
                $title = $labels['role_change_title'];
                $greeting = $labels['greeting'];
                $content = '<p>' . $labels['role_change_content'] . '</p>

        <p>
            ' . $labels['previous_role'] . ' {{ previousRole }}<br>
            ' . $labels['new_role'] . ' {{ newRole }}
        </p>
        
        <p>' . $labels['questions'] . '</p>';
                $buttonUrl = 'https://{{ domain }}/login';
                $buttonText = $labels['login'];
                $additionalContent = '';
                break;
                
            case 'permission_update':
                $title = $labels['permission_update_title'];
                $greeting = $labels['greeting'];
                $content = '<p>' . $labels['permission_update_content'] . '</p>

        <div class="permission-changes">
            <h3>' . $labels['changes_details'] . '</h3>
            <p>{{ permissionChanges|raw }}</p>
        </div>
        
        <p>' . $labels['questions'] . '</p>';
                $buttonUrl = 'https://{{ domain }}/login';
                $buttonText = $labels['login'];
                $additionalContent = '';
                break;
                
            default:
                // Generic template
                $title = $labels['default_title'];
                $greeting = $labels['greeting'];
                $content = '<p>' . $labels['default_content'] . '</p>';
                $buttonUrl = 'https://{{ domain }}';
                $buttonText = $labels['visit_website'];
                $additionalContent = '';
                break;
        }
        
        // Replace placeholders with actual content
        $template = str_replace(
            ['%TITLE%', '%GREETING%', '%CONTENT%', '%BUTTON_URL%', '%BUTTON_TEXT%', '%ADDITIONAL_CONTENT%', '%SIGNATURE%', '%TEAM%', '%FOOTER%'],
            [$title, $greeting, $content, $buttonUrl, $buttonText, $additionalContent, $labels['signature'], $labels['team'], $labels['footer']],
            $template
        );
        
        // Add styles
        $template = '<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . $title . '</title>
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
        color: white;
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
    .permission-changes {
        background-color: #f1f1f1;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
    }
</style>

' . $template;
        
        return $template;
    }

    /**
     * Get localized labels for template placeholders.
     */
    private function getLocalizedLabels(string $locale): array
    {
        $labels = [
            'fr' => [
                'greeting' => 'Bonjour',
                'signature' => 'Cordialement,',
                'team' => 'L\'équipe Dmqode.be',
                'footer' => 'Cet email a été envoyé automatiquement, merci de ne pas y répondre.',
                'registration_title' => 'Confirmation de votre inscription',
                'registration_content' => 'Merci de vous être inscrit sur notre site. Pour confirmer votre adresse email, veuillez cliquer sur le bouton ci-dessous.',
                'confirm_email' => 'Confirmer mon adresse email',
                'button_alt_text' => 'Si le bouton ne fonctionne pas, vous pouvez copier et coller le lien suivant dans votre navigateur :',
                'link_expires' => 'Ce lien expirera dans 24 heures.',
                'if_not_registered' => 'Si vous n\'avez pas créé de compte, veuillez ignorer cet email.',
                'approved_title' => 'Votre compte a été approuvé',
                'approved_content' => 'Nous avons le plaisir de vous informer que votre compte a été approuvé par notre équipe d\'administration.',
                'login_prompt' => 'Vous pouvez désormais vous connecter à votre compte en cliquant sur le bouton ci-dessous.',
                'login' => 'Se connecter',
                'questions' => 'Si vous avez des questions, n\'hésitez pas à nous contacter.',
                'reset_title' => 'Réinitialisation de votre mot de passe',
                'reset_content' => 'Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte. Pour définir un nouveau mot de passe, cliquez sur le bouton ci-dessous.',
                'reset_password' => 'Réinitialiser mon mot de passe',
                'reset_expires' => 'Ce lien expirera dans 1 heure.',
                'if_not_requested' => 'Si vous n\'avez pas demandé de réinitialisation de mot de passe, veuillez ignorer cet email.',
                'role_change_title' => 'Changement de votre rôle',
                'role_change_content' => 'Nous vous informons que votre rôle sur le site Dmqode.be a été modifié.',
                'previous_role' => 'Rôle précédent :',
                'new_role' => 'Nouveau rôle :',
                'permission_update_title' => 'Mise à jour de vos permissions',
                'permission_update_content' => 'Nous vous informons que vos permissions sur le site Dmqode.be ont été modifiées.',
                'changes_details' => 'Détails des modifications',
                'default_title' => 'Notification Dmqode.be',
                'default_content' => 'Voici une information importante concernant votre compte sur notre site.',
                'visit_website' => 'Visiter le site'
            ],
            'en' => [
                'greeting' => 'Dear',
                'signature' => 'Best regards,',
                'team' => 'The Dmqode.be Team',
                'footer' => 'This email was sent automatically; please do not reply to it.',
                'registration_title' => 'Registration Confirmation',
                'registration_content' => 'Thank you for registering on our website. To confirm your email address, please click the button below.',
                'confirm_email' => 'Confirm my email address',
                'button_alt_text' => 'If the button doesn\'t work, you can copy and paste the following link into your browser:',
                'link_expires' => 'This link will expire in 24 hours.',
                'if_not_registered' => 'If you did not create an account, please disregard this email.',
                'approved_title' => 'Your account has been approved',
                'approved_content' => 'We are pleased to inform you that your account has been approved by our administration team.',
                'login_prompt' => 'You can now log in to your account by clicking the button below.',
                'login' => 'Log in',
                'questions' => 'If you have any questions, feel free to contact us.',
                'reset_title' => 'Reset your password',
                'reset_content' => 'We have received a request to reset the password for your account. To set a new password, click the button below.',
                'reset_password' => 'Reset your password',
                'reset_expires' => 'This link will expire in 1 hour.',
                'if_not_requested' => 'If you did not request a password reset, please ignore this email.',
                'role_change_title' => 'Role Change Notification',
                'role_change_content' => 'We are writing to inform you that your role on the Dmqode.be website has been changed.',
                'previous_role' => 'Previous Role:',
                'new_role' => 'New Role:',
                'permission_update_title' => 'Permissions Updated',
                'permission_update_content' => 'We are writing to inform you that your permissions on the Dmqode.be website have been updated.',
                'changes_details' => 'Changes Details',
                'default_title' => 'Dmqode.be Notification',
                'default_content' => 'Here is important information regarding your account on our website.',
                'visit_website' => 'Visit website'
            ],
            'nl' => [
                'greeting' => 'Beste',
                'signature' => 'Met vriendelijke groeten,',
                'team' => 'Het Dmqode.be-team',
                'footer' => 'Deze e-mail is automatisch verzonden; gelieve er niet op te reageren.',
                'registration_title' => 'Bevestiging van uw inschrijving',
                'registration_content' => 'Bedankt voor uw inschrijving op onze website. Om uw e-mailadres te bevestigen, klik op de onderstaande knop.',
                'confirm_email' => 'Mijn e-mailadres bevestigen',
                'button_alt_text' => 'Als de knop niet werkt, kunt u de volgende link kopiëren en plakken in uw browser:',
                'link_expires' => 'Deze link verloopt binnen 24 uur.',
                'if_not_registered' => 'Als u geen account heeft aangemaakt, kunt u deze e-mail negeren.',
                'approved_title' => 'Uw account is goedgekeurd',
                'approved_content' => 'We zijn verheugd u te informeren dat uw account is goedgekeurd door ons administratieteam.',
                'login_prompt' => 'U kunt nu inloggen op uw account door op de onderstaande knop te klikken.',
                'login' => 'Inloggen',
                'questions' => 'Als u vragen heeft, aarzel dan niet om contact met ons op te nemen.',
                'reset_title' => 'Reset uw wachtwoord',
                'reset_content' => 'We hebben een verzoek ontvangen om het wachtwoord voor uw account opnieuw in te stellen. Om een nieuw wachtwoord in te stellen, klikt u op de onderstaande knop.',
                'reset_password' => 'Reset uw wachtwoord',
                'reset_expires' => 'Deze link verloopt binnen 1 uur.',
                'if_not_requested' => 'Als u geen verzoek heeft ingediend om uw wachtwoord opnieuw in te stellen, kunt u deze e-mail negeren.',
                'role_change_title' => 'Wijziging van uw rol',
                'role_change_content' => 'We informeren u dat uw rol op de Dmqode.be-website is gewijzigd.',
                'previous_role' => 'Vorige rol:',
                'new_role' => 'Nieuwe rol:',
                'permission_update_title' => 'Uw rechten zijn gewijzigd',
                'permission_update_content' => 'We informeren u dat uw rechten op de Dmqode.be-website zijn gewijzigd.',
                'changes_details' => 'Details van de wijzigingen',
                'default_title' => 'Dmqode.be Notificatie',
                'default_content' => 'Hier is belangrijke informatie over uw account op onze website.',
                'visit_website' => 'Bezoek website'
            ],
            'de' => [
                'greeting' => 'Sehr geehrte/-r',
                'signature' => 'Mit freundlichen Grüßen,',
                'team' => 'Das Dmqode.be-Team',
                'footer' => 'Diese E-Mail wurde automatisch versendet; bitte antworten Sie nicht darauf.',
                'registration_title' => 'Bestätigung Ihrer Anmeldung',
                'registration_content' => 'Vielen Dank, dass Sie sich auf unserer Website registriert haben. Um Ihre E-Mail-Adresse zu bestätigen, klicken Sie bitte auf den untenstehenden Button.',
                'confirm_email' => 'Meine E-Mail-Adresse bestätigen',
                'button_alt_text' => 'Wenn der Button nicht funktioniert, können Sie den folgenden Link kopieren und in Ihren Browser einfügen:',
                'link_expires' => 'Dieser Link wird in 24 Stunden ablaufen.',
                'if_not_registered' => 'Wenn Sie kein Konto erstellt haben, ignorieren Sie bitte diese E-Mail.',
                'approved_title' => 'Ihr Konto wurde genehmigt',
                'approved_content' => 'Wir freuen uns, Ihnen mitteilen zu können, dass Ihr Konto von unserem Verwaltungsteam genehmigt wurde.',
                'login_prompt' => 'Sie können sich jetzt in Ihr Konto einloggen, indem Sie auf die Schaltfläche unten klicken.',
                'login' => 'Einloggen',
                'questions' => 'Wenn Sie Fragen haben, zögern Sie nicht, uns zu kontaktieren.',
                'reset_title' => 'Setzen Sie Ihr Passwort zurück',
                'reset_content' => 'Wir haben eine Anfrage zur Zurücksetzung des Passworts für Ihr Konto erhalten. Um ein neues Passwort festzulegen, klicken Sie bitte auf die Schaltfläche unten.',
                'reset_password' => 'Setzen Sie Ihr Passwort zurück',
                'reset_expires' => 'Dieser Link wird in 1 Stunde ablaufen.',
                'if_not_requested' => 'Wenn Sie keine Zurücksetzung des Passworts beantragt haben, ignorieren Sie bitte diese E-Mail.',
                'role_change_title' => 'Änderung Ihrer Rolle',
                'role_change_content' => 'Wir möchten Sie darüber informieren, dass sich Ihre Rolle auf der Dmqode.be-Website geändert hat.',
                'previous_role' => 'Vorherige Rolle:',
                'new_role' => 'Neue Rolle:',
                'permission_update_title' => 'Berechtigungen aktualisiert',
                'permission_update_content' => 'Wir möchten Sie darüber informieren, dass sich Ihre Berechtigungen auf der Dmqode.be-Website geändert haben.',
                'changes_details' => 'Details der Änderungen',
                'default_title' => 'Dmqode.be Benachrichtigung',
                'default_content' => 'Hier sind wichtige Informationen zu Ihrem Konto auf unserer Website.',
                'visit_website' => 'Website besuchen'
            ]
        ];

        return $labels[$locale] ?? $labels['en'];
    }

    /**
     * Get sample variables for email template preview.
     */
    private function getSampleVariables(string $code): array
    {
        $commonVariables = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'john.doe',
            'email' => 'john.doe@example.com',
            'domain' => 'dmqode.be',
            'app_name' => 'Dmqode.be',
            'birthDate' => '01/01/1980',
            'phoneNumber' => '+32 123 456 789',
            'street' => 'Rue de l\'Exemple',
            'houseNumber' => '42',
            'boxNumber' => 'A',
            'postalCode' => '1000',
            'city' => 'Bruxelles',
            'country' => 'Belgique',
            'locale' => 'Français',
            'createdAt' => date('d/m/Y'),
            'fullName' => 'John Doe',
            'fullAddress' => 'Rue de l\'Exemple 42A, 1000 Bruxelles, Belgique',
            'age' => '43'
        ];

        switch ($code) {
            case 'registration_confirmation':
                return array_merge($commonVariables, [
                    'signedUrl' => 'https://example.com/verify/email?token=sample-token',
                ]);
            case 'reset_password':
                return array_merge($commonVariables, [
                    'resetToken' => 'https://example.com/reset-password/sample-token',
                    'tokenLifetime' => '1 heure',
                ]);
            case 'role_change':
                return array_merge($commonVariables, [
                    'previousRole' => 'Utilisateur',
                    'newRole' => 'Administrateur',
                ]);
            case 'permission_update':
                return array_merge($commonVariables, [
                    'permissionChanges' => 'view_users: Refusé → Accordé<br>edit_users: Refusé → Accordé<br>view_email_templates: Accordé → Refusé',
                ]);
            default:
                return $commonVariables;
        }
    }
}