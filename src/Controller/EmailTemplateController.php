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

#[Route('/admin/email-templates')]
class EmailTemplateController extends AbstractController
{
    private EmailManager $emailManager;
    private AuditLogger $auditLogger;
    private PermissionManager $permissionManager;

    public function __construct(
        EmailManager $emailManager,
        AuditLogger $auditLogger,
        PermissionManager $permissionManager
    ) {
        $this->emailManager = $emailManager;
        $this->auditLogger = $auditLogger;
        $this->permissionManager = $permissionManager;
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
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($emailTemplate);
            $entityManager->flush();

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
            $this->getDoctrine()->getManager()->flush();

            // Log the action
            $this->auditLogger->log(
                $this->getUser(),
                'edit_email_template',
                sprintf('Edited email template "%s" for locale "%s" (ID: %d)', $emailTemplate->getCode(), $emailTemplate->getLocale(), $emailTemplate->getId())
            );

            $this->addFlash('success', 'Email template has been updated.');

            return $this->redirectToRoute('admin_email_templates');
        }

        return $this->render('admin/email_templates/edit.html.twig', [
            'email_template' => $emailTemplate,
            'form' => $form->createView(),
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
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
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

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($emailTemplate);
        $entityManager->flush();

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
     * Get sample variables for email template preview.
     */
    private function getSampleVariables(string $code): array
    {
        $commonVariables = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'john.doe',
            'domain' => 'example.com',
        ];

        switch ($code) {
            case 'registration_confirmation':
                return array_merge($commonVariables, [
                    'signedUrl' => 'https://example.com/verify/email?token=sample-token',
                ]);
            case 'reset_password':
                return array_merge($commonVariables, [
                    'resetToken' => 'https://example.com/reset-password/sample-token',
                ]);
            case 'role_change':
                return array_merge($commonVariables, [
                    'previousRole' => 'User',
                    'newRole' => 'Administrator',
                ]);
            case 'permission_update':
                return array_merge($commonVariables, [
                    'permissionChanges' => 'view_users: Denied → Granted<br>edit_users: Denied → Granted<br>view_email_templates: Granted → Denied',
                ]);
            default:
                return $commonVariables;
        }
    }
}
