<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Service\AuditLogger;
use App\Service\PermissionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/audit-logs')]
class AuditLogController extends AbstractController
{
    private AuditLogger $auditLogger;
    private PermissionManager $permissionManager;

    public function __construct(
        AuditLogger $auditLogger,
        PermissionManager $permissionManager
    ) {
        $this->auditLogger = $auditLogger;
        $this->permissionManager = $permissionManager;
    }

    #[Route('/', name: 'admin_audit_logs')]
    public function index(AuditLogRepository $auditLogRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_audit_logs')) {
                throw $this->createAccessDeniedException('You do not have permission to view audit logs.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'view_audit_logs',
            'Admin viewed the audit logs.'
        );

        return $this->render('admin/audit_logs/index.html.twig', [
            'logs' => $auditLogRepository->findRecent(100),
        ]);
    }

    #[Route('/user/{id}', name: 'admin_audit_logs_user')]
    public function userLogs(User $user, AuditLogRepository $auditLogRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'view_audit_logs')) {
                throw $this->createAccessDeniedException('You do not have permission to view audit logs.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'view_user_audit_logs',
            sprintf('Admin viewed audit logs for user: %s (%s)', $user->getFullName(), $user->getEmail())
        );

        return $this->render('admin/audit_logs/user.html.twig', [
            'user' => $user,
            'logs' => $auditLogRepository->findByUser($user),
        ]);
    }
}
