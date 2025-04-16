<?php

namespace App\Service;

use App\Entity\AdminPermission;
use App\Entity\User;
use App\Repository\AdminPermissionRepository;
use Doctrine\ORM\EntityManagerInterface;

class PermissionManager
{
    private EntityManagerInterface $entityManager;
    private AdminPermissionRepository $adminPermissionRepository;
    private AuditLogger $auditLogger;
    private EmailManager $emailManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdminPermissionRepository $adminPermissionRepository,
        AuditLogger $auditLogger,
        EmailManager $emailManager
    ) {
        $this->entityManager = $entityManager;
        $this->adminPermissionRepository = $adminPermissionRepository;
        $this->auditLogger = $auditLogger;
        $this->emailManager = $emailManager;
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
        }

        // Add to audit log
        $this->auditLogger->log(
            $currentUser,
            'set_permission',
            $details
        );

        // Send notification email if permissions have changed
        $this->sendPermissionUpdateEmail($admin);
    }

    /**
     * Update permissions for an admin in batch.
     */
    public function updatePermissions(User $admin, array $permissions, User $currentUser): void
    {
        $changes = [];
        $availablePermissions = $this->getAllPermissionKeys();

        foreach ($availablePermissions as $permission) {
            $isGranted = in_array($permission, $permissions);
            
            // Get existing permission
            $existingPermission = $this->adminPermissionRepository->findOneBy([
                'admin' => $admin,
                'permission' => $permission,
            ]);

            if ($existingPermission) {
                // If permission exists and value is different, update it
                if ($existingPermission->isGranted() !== $isGranted) {
                    $changes[] = sprintf(
                        '"%s": %s → %s',
                        $permission,
                        $existingPermission->isGranted() ? 'Accordé' : 'Refusé',
                        $isGranted ? 'Accordé' : 'Refusé'
                    );
                    $existingPermission->setIsGranted($isGranted);
                }
            } else {
                // If permission doesn't exist and should be granted, create it
                if ($isGranted) {
                    $adminPermission = new AdminPermission();
                    $adminPermission->setAdmin($admin);
                    $adminPermission->setPermission($permission);
                    $adminPermission->setIsGranted(true);
                    $this->entityManager->persist($adminPermission);
                    
                    $changes[] = sprintf('"%s": Accordé (nouvel ajout)', $permission);
                }
            }
        }

        if (!empty($changes)) {
            $this->entityManager->flush();
            
            // Log changes
            $this->auditLogger->log(
                $currentUser,
                'update_permissions',
                sprintf('Updated permissions for %s (%s): %s', $admin->getFullName(), $admin->getEmail(), implode(', ', $changes))
            );
            
            // Send notification email
            $this->sendPermissionUpdateEmail($admin, $changes);
        }
    }

    /**
     * Send permission update email to an admin.
     */
    private function sendPermissionUpdateEmail(User $admin, array $changes = []): void
    {
        // Only send email if there are changes
        if (!empty($changes)) {
            $this->emailManager->sendEmailToUser(
                $admin,
                'permission_update',
                [
                    'permissionChanges' => implode('<br>', $changes),
                ]
            );
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
