<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
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
    private AuditLogRepository $auditLogRepository;
    private UserRepository $userRepository;

    public function __construct(
        AuditLogger $auditLogger,
        PermissionManager $permissionManager,
        AuditLogRepository $auditLogRepository,
        UserRepository $userRepository
    ) {
        $this->auditLogger = $auditLogger;
        $this->permissionManager = $permissionManager;
        $this->auditLogRepository = $auditLogRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'admin_audit_logs')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_audit_logs')) {
                throw $this->createAccessDeniedException('You do not have permission to view audit logs.');
            }
        }
        
        // Récupérer les paramètres de filtrage
        $type = $request->query->get('type', '');
        $filters = [
            'user_id' => $request->query->get('user_id', ''),
            'action' => $request->query->get('action', ''),
            'ip' => $request->query->get('ip', ''),
            'browser' => $request->query->get('browser', ''),
            'device' => $request->query->get('device', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
            'details' => $request->query->get('details', ''),
        ];
        
        // Paramètres de pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 20);
        $offset = ($page - 1) * $limit;
        
        // Préparer les filtres pour la requête
        $queryFilters = [];
        if (!empty($type)) {
            $queryFilters['type'] = $type;
        }
        
        if (!empty($filters['user_id'])) {
            $user = $this->userRepository->find($filters['user_id']);
            if ($user) {
                $queryFilters['user'] = $user;
            }
        }
        
        foreach (['action', 'ip', 'browser', 'device', 'date_from', 'date_to', 'details'] as $key) {
            if (!empty($filters[$key])) {
                $queryFilters[$key] = $filters[$key];
            }
        }
        
        // Récupérer les logs avec les filtres appliqués
        $logs = $this->auditLogRepository->findWithFilters($queryFilters, $limit, $offset);
        
        // Compter le nombre total de logs pour la pagination
        $totalLogs = $this->auditLogRepository->countWithFilters($queryFilters);
        $totalPages = ceil($totalLogs / $limit);
        
        // Récupérer les statistiques
        $stats = $this->auditLogRepository->getStatistics();
        
        // Tous les utilisateurs pour le filtre
        $users = $this->userRepository->findAll();
        
        // Tous les types de logs
        $logTypes = AuditLog::getAvailableTypes();
        
        // Log the action
        $this->auditLogger->logView(
            $this->getUser(),
            'view_audit_logs',
            'Admin viewed the audit logs.'
        );

        return $this->render('admin/audit_logs/index.html.twig', [
            'logs' => $logs,
            'stats' => $stats,
            'current_type' => $type,
            'filters' => $filters,
            'log_types' => $logTypes,
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'total_items' => $totalLogs
            ]
        ]);
    }

    #[Route('/user/{id}', name: 'admin_audit_logs_user')]
    public function userLogs(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'view_audit_logs')) {
                throw $this->createAccessDeniedException('You do not have permission to view audit logs.');
            }
        }
        
        // Récupérer les paramètres de filtrage
        $type = $request->query->get('type', '');
        $action = $request->query->get('action', '');
        
        // Paramètres de pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 20);
        $offset = ($page - 1) * $limit;
        
        // Préparer les filtres pour la requête
        $filters = ['user' => $user];
        
        if (!empty($type)) {
            $filters['type'] = $type;
        }
        
        if (!empty($action)) {
            $filters['action'] = $action;
        }
        
        // Récupérer les logs avec les filtres appliqués
        $logs = $this->auditLogRepository->findWithFilters($filters, $limit, $offset);
        
        // Compter le nombre total de logs pour la pagination
        $totalLogs = $this->auditLogRepository->countWithFilters($filters);
        $totalPages = ceil($totalLogs / $limit);
        
        // Tous les types de logs
        $logTypes = AuditLog::getAvailableTypes();

        // Log the action
        $this->auditLogger->logView(
            $this->getUser(),
            'view_user_audit_logs',
            sprintf('Admin viewed audit logs for user: %s (%s)', $user->getFullName(), $user->getEmail())
        );

        return $this->render('admin/audit_logs/user.html.twig', [
            'user' => $user,
            'logs' => $logs,
            'current_type' => $type,
            'current_action' => $action,
            'log_types' => $logTypes,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'total_items' => $totalLogs
            ]
        ]);
    }

    #[Route('/export', name: 'admin_audit_logs_export')]
    public function export(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_audit_logs')) {
                throw $this->createAccessDeniedException('You do not have permission to export audit logs.');
            }
        }
        
        // Récupérer les paramètres de filtrage
        $type = $request->query->get('type', '');
        $filters = [
            'user_id' => $request->query->get('user_id', ''),
            'action' => $request->query->get('action', ''),
            'ip' => $request->query->get('ip', ''),
            'browser' => $request->query->get('browser', ''),
            'device' => $request->query->get('device', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
            'details' => $request->query->get('details', ''),
        ];
        
        // Préparer les filtres pour la requête
        $queryFilters = [];
        if (!empty($type)) {
            $queryFilters['type'] = $type;
        }
        
        if (!empty($filters['user_id'])) {
            $user = $this->userRepository->find($filters['user_id']);
            if ($user) {
                $queryFilters['user'] = $user;
            }
        }
        
        foreach (['action', 'ip', 'browser', 'device', 'date_from', 'date_to', 'details'] as $key) {
            if (!empty($filters[$key])) {
                $queryFilters[$key] = $filters[$key];
            }
        }
        
        // Récupérer tous les logs pour l'export (sans limite)
        $logs = $this->auditLogRepository->findWithFilters($queryFilters, 0, 0);
        
        // Générer le contenu CSV
        $csvContent = $this->generateCsvContent($logs);
        
        // Log the action
        $this->auditLogger->logView(
            $this->getUser(),
            'export_audit_logs',
            'Admin exported audit logs.'
        );
        
        // Préparer la réponse
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="audit_logs_export_' . date('Y-m-d_H-i-s') . '.csv"');
        
        return $response;
    }
    
    #[Route('/delete-bulk', name: 'admin_audit_logs_delete_bulk', methods: ['POST'])]
    public function deleteBulk(Request $request): Response
    {
        // Cette action n'est disponible que pour les super admins
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        
        // Vérifier le jeton CSRF
        if (!$this->isCsrfTokenValid('delete_bulk_logs', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_audit_logs');
        }
        
        // Récupérer les IDs des logs à supprimer
        $logIds = $request->request->all('logs');
        
        if (empty($logIds)) {
            $this->addFlash('warning', 'No logs selected for deletion.');
            return $this->redirectToRoute('admin_audit_logs');
        }
        
        // Récupérer les types des logs qui vont être supprimés pour le message de log
        $logsToDelete = $this->auditLogRepository->findBy(['id' => $logIds]);
        
        // Préparer un résumé des types de logs supprimés
        $typeCount = [];
        foreach ($logsToDelete as $log) {
            $type = $log->getType();
            if (!isset($typeCount[$type])) {
                $typeCount[$type] = 0;
            }
            $typeCount[$type]++;
        }
        
        // Créer un message détaillé avec les types de logs
        $typeDetails = [];
        $logTypes = AuditLog::getAvailableTypes();
        foreach ($typeCount as $type => $count) {
            $typeLabel = isset($logTypes[$type]) ? $logTypes[$type] : ucfirst($type);
            $typeDetails[] = "$count logs de type \"$typeLabel\"";
        }
        
        // Supprimer les logs sélectionnés
        $deleted = $this->auditLogRepository->deleteMultiple($logIds);
        
        // Créer le message détaillé pour le log d'audit
        $deleteDetails = "Admin deleted $deleted audit logs: " . implode(', ', $typeDetails);
        
        // Enregistrer l'action avec les détails des types supprimés
        $this->auditLogger->logDelete(
            $this->getUser(),
            'delete_audit_logs',
            $deleteDetails
        );
        
        $this->addFlash('success', sprintf('%d logs have been successfully deleted.', $deleted));
        
        // Rediriger avec les mêmes filtres qu'avant
        return $this->redirectToRoute('admin_audit_logs', [
            'type' => $request->query->get('type'),
            'user_id' => $request->query->get('user_id'),
            'action' => $request->query->get('action'),
            'ip' => $request->query->get('ip'),
            'browser' => $request->query->get('browser'),
            'device' => $request->query->get('device'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'details' => $request->query->get('details'),
            'page' => $request->query->get('page', 1),
        ]);
    }
    
    #[Route('/http-errors', name: 'admin_http_errors')]
    public function httpErrors(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_audit_logs')) {
                throw $this->createAccessDeniedException('You do not have permission to view audit logs.');
            }
        }
        
        // Récupérer les paramètres de filtrage
        $errorCode = $request->query->get('error_code', '');
        
        // Paramètres de pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 20);
        $offset = ($page - 1) * $limit;
        
        // Récupérer les logs d'erreurs HTTP
        $logs = $this->auditLogRepository->findHttpErrors($errorCode, $limit, $offset);
        
        // Compter le nombre total d'erreurs HTTP
        $totalLogs = $this->auditLogRepository->countHttpErrors($errorCode);
        $totalPages = ceil($totalLogs / $limit);
        
        // Récupérer les statistiques des erreurs HTTP
        $stats = $this->auditLogRepository->getHttpErrorStats();
        
        // Log the action
        $this->auditLogger->logView(
            $this->getUser(),
            'view_http_errors',
            'Admin viewed HTTP error logs.'
        );

        return $this->render('admin/audit_logs/http_errors.html.twig', [
            'logs' => $logs,
            'current_error_code' => $errorCode,
            'stats' => $stats,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'total_items' => $totalLogs
            ]
        ]);
    }
    
    /**
     * Génère le contenu CSV pour l'export
     */
    private function generateCsvContent(array $logs): string
    {
        $csvData = [];
        
        // En-têtes
        $headers = [
            'ID',
            'Date/Heure',
            'Utilisateur',
            'Email',
            'Type',
            'Action',
            'Adresse IP',
            'Navigateur',
            'Système d\'exploitation',
            'Appareil',
            'Détails'
        ];
        
        $csvData[] = implode(';', $headers);
        
        // Données
        foreach ($logs as $log) {
            $row = [
                $log->getId(),
                $log->getCreatedAt()->format('d/m/Y H:i:s'),
                $log->getUser()->getFullName(),
                $log->getUser()->getEmail(),
                $log->getTypeLabel(),
                $log->getAction(),
                $log->getIpAddress(),
                ($log->getBrowserName() !== 'Unknown' ? $log->getBrowserName() . ' ' . $log->getBrowserVersion() : 'Inconnu'),
                ($log->getOsName() !== 'Unknown' ? $log->getOsName() . ' ' . $log->getOsVersion() : 'Inconnu'),
                ($log->getDeviceBrand() !== 'Unknown' ? $log->getDeviceBrand() . ' ' . $log->getDeviceModel() : 'Inconnu'),
                // Nettoyer les détails pour l'export CSV (enlever les retours à la ligne, etc.)
                str_replace(["\r", "\n", ";"], [" ", " ", ","], $log->getDetails() ?: '')
            ];
            
            // Échapper les champs pour CSV
            foreach ($row as &$field) {
                // Si le champ contient un point-virgule, des guillemets ou des sauts de ligne, l'entourer de guillemets
                if (preg_match('/[;"\r\n]/', $field)) {
                    $field = '"' . str_replace('"', '""', $field) . '"';
                }
            }
            
            $csvData[] = implode(';', $row);
        }
        
        return implode("\n", $csvData);
    }
}