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

#[Route('/admin/email-templates')]
class EmailTemplateController extends AbstractController
{
    private EmailManager $emailManager;
    private AuditLogger $auditLogger;
    private PermissionManager $permissionManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EmailManager $emailManager,
        AuditLogger $auditLogger,
        PermissionManager $permissionManager,
        EntityManagerInterface $entityManager
    ) {
        $this->emailManager = $emailManager;
        $this->auditLogger = $auditLogger;
        $this->permissionManager = $permissionManager;
        $this->entityManager = $entityManager;
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
        $form = $this->createForm(EmailTemplateType::class, $emailTemplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

        return $this->render('admin/email_templates/edit_enhanced.html.twig', [
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

        $form = $this->createForm(EmailTemplateType::class, $emailTemplate);
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

        // Replace variables in the content
        $content = $emailTemplate->getHtmlContent();
        foreach ($sampleVariables as $key => $value) {
            // Modifier ici pour utiliser le format %variable%
            $content = str_replace('%' . $key . '%', $value, $content);
        }

        return new Response($content);
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
            return $this->json(['success' => false, 'message' => 'Template not found'], 404);
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
            ]
        ]);
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
            'domain' => 'example.com',
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