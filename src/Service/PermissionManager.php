<?php

namespace App\Service;

use App\Entity\AdminPermission;
use App\Entity\User;
use App\Repository\AdminPermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PermissionManager
{
    private EntityManagerInterface $entityManager;
    private AdminPermissionRepository $adminPermissionRepository;
    private AuditLogger $auditLogger;
    private EmailManager $emailManager;
    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdminPermissionRepository $adminPermissionRepository,
        AuditLogger $auditLogger,
        EmailManager $emailManager,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->adminPermissionRepository = $adminPermissionRepository;
        $this->auditLogger = $auditLogger;
        $this->emailManager = $emailManager;
        $this->translator = $translator;
    }

    /**
     * Get all available permissions.
     */
    public function getAvailablePermissions(): array
    {
        return [
            'user_management' => [
                'view_users' => 'Can view users',
                'edit_users' => 'Can edit users',
                'approve_users' => 'Can approve users',
                'delete_users' => 'Can delete users',
            ],
            'role_management' => [
                'assign_roles' => 'Can assign roles',
                'manage_permissions' => 'Can manage permissions',
            ],
            'email_management' => [
                'view_email_templates' => 'Can view email templates',
                'edit_email_templates' => 'Can edit email templates',
            ],
            'audit_logs' => [
                'view_audit_logs' => 'Can view audit logs',
            ],
        ];
    }

    /**
     * Get permissions for an admin.
     */
    public function getPermissionsForAdmin(User $admin): array
    {
        $result = [];
        $permissions = $this->adminPermissionRepository->findByAdmin($admin);

        foreach ($permissions as $permission) {
            $result[$permission->getPermission()] = $permission->isGranted();
        }

        return $result;
    }

    /**
     * Translate permission name based on the permission key.
     */
    private function translatePermissionName(string $permission, string $locale = 'fr'): string
    {
        // Définir la locale pour la traduction
        $userLocale = $locale;
        
        // Utiliser le service de traduction pour traduire la clé de permission
        $translatedPermission = $this->translator->trans($permission, [], 'messages', $userLocale);
        
        // Si la traduction donne la même chose que la clé, essayer avec la solution de secours
        if ($translatedPermission === $permission) {
            // Mappings des clés de permissions aux textes plus lisibles
            $permissionLabels = [
                'view_users' => 'Voir les utilisateurs',
                'edit_users' => 'Modifier les utilisateurs',
                'approve_users' => 'Approuver les utilisateurs',
                'delete_users' => 'Supprimer les utilisateurs',
                'assign_roles' => 'Attribuer des rôles',
                'manage_permissions' => 'Gérer les permissions',
                'view_email_templates' => 'Voir les modèles d\'email',
                'edit_email_templates' => 'Modifier les modèles d\'email',
                'view_audit_logs' => 'Voir les journaux d\'audit',
            ];
            
            return $permissionLabels[$permission] ?? $permission;
        }
        
        return $translatedPermission;
    }

    /**
     * Set permission for an admin.
     */
    public function setPermission(User $admin, string $permission, bool $isGranted, User $currentUser): void
    {
        // Check if permission already exists
        $existingPermission = $this->adminPermissionRepository->findOneBy([
            'admin' => $admin,
            'permission' => $permission,
        ]);

        if ($existingPermission) {
            // Update existing permission
            $oldValue = $existingPermission->isGranted();
            $existingPermission->setIsGranted($isGranted);
            $this->entityManager->flush();

            // Log the change
            $details = sprintf(
                'Permission "%s" for admin "%s" changed from %s to %s.',
                $permission,
                $admin->getEmail(),
                $oldValue ? 'granted' : 'denied',
                $isGranted ? 'granted' : 'denied'
            );
            
            // Track changes for email notification
            $changes = [];
            if ($oldValue !== $isGranted) {
                // Traduire le nom de la permission
                $translatedPermission = $this->translatePermissionName($permission, $admin->getLocale());
                
                $changes[] = sprintf(
                    '%s: %s → %s',
                    $translatedPermission,
                    $oldValue ? 'Accordé' : 'Refusé',
                    $isGranted ? 'Accordé' : 'Refusé'
                );
            }
        } else {
            // Create new permission
            $adminPermission = new AdminPermission();
            $adminPermission->setAdmin($admin);
            $adminPermission->setPermission($permission);
            $adminPermission->setIsGranted($isGranted);

            $this->entityManager->persist($adminPermission);
            $this->entityManager->flush();

            // Log the change
            $details = sprintf(
                'Permission "%s" for admin "%s" set to %s.',
                $permission,
                $admin->getEmail(),
                $isGranted ? 'granted' : 'denied'
            );
            
            // Track changes for email notification
            // Traduire le nom de la permission
            $translatedPermission = $this->translatePermissionName($permission, $admin->getLocale());
            
            $changes[] = sprintf(
                '%s: %s (nouvel ajout)',
                $translatedPermission,
                $isGranted ? 'Accordé' : 'Refusé'
            );
        }

        // Add to audit log
        $this->auditLogger->log(
            $currentUser,
            'set_permission',
            $details
        );

        // Send notification email if permissions have changed
        if (!empty($changes)) {
            $this->sendPermissionUpdateEmail($admin, $changes);
        }
    }

    /**
     * Update permissions for an admin in batch.
     */
    public function updatePermissions(User $admin, array $permissions, User $currentUser): void
    {
        $changes = [];
        $availablePermissions = $this->getAllPermissionKeys();
        $currentPermissions = $this->getPermissionsForAdmin($admin);
        
        // Debug log
        error_log('Current permissions: ' . print_r($currentPermissions, true));
        error_log('New permissions: ' . print_r($permissions, true));
        
        // Comparer et mettre à jour chaque permission
        foreach ($availablePermissions as $permission) {
            $isGranted = in_array($permission, $permissions);
            $currentlyGranted = isset($currentPermissions[$permission]) && $currentPermissions[$permission];
            
            // Si l'état de la permission a changé
            if ($isGranted !== $currentlyGranted) {
                // Obtenir ou créer la permission
                $existingPermission = $this->adminPermissionRepository->findOneBy([
                    'admin' => $admin,
                    'permission' => $permission,
                ]);
                
                if ($existingPermission) {
                    // Mettre à jour la permission existante
                    $existingPermission->setIsGranted($isGranted);
                } else {
                    // Créer une nouvelle permission
                    $adminPermission = new AdminPermission();
                    $adminPermission->setAdmin($admin);
                    $adminPermission->setPermission($permission);
                    $adminPermission->setIsGranted($isGranted);
                    $this->entityManager->persist($adminPermission);
                }
                
                // Traduire le nom de la permission
                $translatedPermission = $this->translatePermissionName($permission, $admin->getLocale());
                
                // Enregistrer le changement pour le log et l'email
                $changes[] = sprintf(
                    '%s: %s → %s',
                    $translatedPermission,
                    $currentlyGranted ? 'Accordé' : 'Refusé',
                    $isGranted ? 'Accordé' : 'Refusé'
                );
                
                // Debug log
                error_log('Permission changed: ' . $permission . ' from ' . ($currentlyGranted ? 'Accordé' : 'Refusé') . ' to ' . ($isGranted ? 'Accordé' : 'Refusé'));
            }
        }

        // S'il y a des changements, persister et envoyer l'email
        if (!empty($changes)) {
            // Debug log
            error_log('Changes to send: ' . print_r($changes, true));
            
            $this->entityManager->flush();
            
            // Log changes
            $this->auditLogger->log(
                $currentUser,
                'update_permissions',
                sprintf('Updated permissions for %s (%s): %s', $admin->getFullName(), $admin->getEmail(), implode(', ', $changes))
            );
            
            // Send notification email with the complete list of changes
            $this->sendPermissionUpdateEmail($admin, $changes);
        }
    }

    /**
     * Send permission update email to an admin.
     */
    private function sendPermissionUpdateEmail(User $admin, array $changes = []): void
    {
        // Seulement envoyer l'email s'il y a des changements
        if (!empty($changes)) {
            // Debug log
            error_log('Sending email with changes: ' . print_r($changes, true));
            
            // Formatter les changements en HTML pour que chaque changement apparaisse sur une nouvelle ligne
            $formattedChanges = implode('<br>', $changes);
            
            try {
                $this->emailManager->sendEmailToUser(
                    $admin,
                    'permission_update',
                    [
                        'permissionChanges' => $formattedChanges,
                    ]
                );
                
                // Log de réussite
                error_log('Email envoyé avec succès à : ' . $admin->getEmail());
            } catch (\Exception $e) {
                // Enregistrer l'erreur mais ne pas propager l'exception
                error_log('Erreur lors de l\'envoi de l\'email de mise à jour de permission: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get all permission keys as a flat array.
     */
    private function getAllPermissionKeys(): array
    {
        $permissions = $this->getAvailablePermissions();
        $keys = [];

        foreach ($permissions as $group) {
            $keys = array_merge($keys, array_keys($group));
        }

        return $keys;
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Super admins have all permissions
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // Admins have permissions based on their settings
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $userPermissions = $this->getPermissionsForAdmin($user);
            return isset($userPermissions[$permission]) && $userPermissions[$permission];
        }

        return false;
    }
}