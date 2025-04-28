<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EmailManager $emailManager;
    private AuditLogger $auditLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EmailManager $emailManager,
        AuditLogger $auditLogger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->emailManager = $emailManager;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Create a new user.
     */
    public function createUser(User $user, string $plainPassword): User
    {
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Set default role
        if (empty($user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
        }

        // Save the user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Verify a user's email address.
     */
    public function verifyUserEmail(User $user, User $adminUser = null): void
    {
        $user->setIsVerified(true);
        $user->setEmailVerifiedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Log the action if done by an admin
        if ($adminUser) {
            $this->auditLogger->log(
                $adminUser,
                'verify_user_email',
                sprintf('Verified email for user: %s (%s)', $user->getFullName(), $user->getEmail())
            );
        }
    }

    /**
     * Approve a user account.
     */
    public function approveUser(User $user, User $adminUser): void
    {
        // Marquer l'utilisateur comme approuvé
        $user->setIsApproved(true);
        $user->setApprovedAt(new \DateTimeImmutable());
        
        // S'assurer que l'utilisateur a le rôle ROLE_USER
        $roles = $user->getRoles();
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
            $user->setRoles($roles);
        }
        
        $this->entityManager->flush();

        // Send notification email
        try {
            $this->emailManager->sendEmailToUser($user, 'account_approved');
        } catch (\Exception $e) {
            // Log the error but don't stop execution
            error_log('Error sending account approval email: ' . $e->getMessage());
        }

        // Log the action
        $this->auditLogger->log(
            $adminUser,
            'approve_user',
            sprintf('Approved user account: %s (%s)', $user->getFullName(), $user->getEmail())
        );
    }

    /**
     * Change user role.
     */
    public function changeUserRole(User $user, array $roles, User $adminUser): void
    {
        $previousRoles = $user->getRoles();
        
        // Filter out duplicates and ensure array contains only unique values
        $roles = array_unique($roles);
        
        // Update roles
        $user->setRoles($roles);
        $this->entityManager->flush();

        // Format role names for email
        $formattedPreviousRoles = $this->formatRoleNames($previousRoles);
        $formattedNewRoles = $this->formatRoleNames($roles);

        // Send notification email
        try {
            $this->emailManager->sendEmailToUser($user, 'role_change', [
                'previousRole' => $formattedPreviousRoles,
                'newRole' => $formattedNewRoles,
            ]);
        } catch (\Exception $e) {
            // Log the error but don't stop execution
            error_log('Error sending role change email: ' . $e->getMessage());
        }

        // Log the action
        $this->auditLogger->log(
            $adminUser,
            'change_user_role',
            sprintf(
                'Changed roles for user %s (%s) from %s to %s',
                $user->getFullName(),
                $user->getEmail(),
                $formattedPreviousRoles,
                $formattedNewRoles
            )
        );
    }

    /**
     * Format role names for display in email.
     */
    private function formatRoleNames(array $roles): string
    {
        // Map technical role names to human-readable names
        $roleNames = [];
        foreach ($roles as $role) {
            switch ($role) {
                case 'ROLE_SUPER_ADMIN':
                    $roleNames[] = 'Super Administrateur';
                    break;
                case 'ROLE_ADMIN':
                    $roleNames[] = 'Administrateur';
                    break;
                case 'ROLE_USER':
                    $roleNames[] = 'Utilisateur';
                    break;
                default:
                    // Convert from ROLE_XXX_YYY to Xxx Yyy
                    $name = str_replace('ROLE_', '', $role);
                    $name = str_replace('_', ' ', $name);
                    $name = ucwords(strtolower($name));
                    $roleNames[] = $name;
            }
        }
        
        return implode(', ', $roleNames);
    }

    /**
     * Update user's last login time.
     */
    public function updateLastLogin(User $user): void
    {
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user, User $adminUser): void
    {
        $userDetails = sprintf('%s (%s)', $user->getFullName(), $user->getEmail());
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        // Log the action
        $this->auditLogger->log(
            $adminUser,
            'delete_user',
            sprintf('Deleted user: %s', $userDetails)
        );
    }

    /**
     * Get all available user roles.
     */
    public function getAvailableRoles(): array
    {
        return [
            'ROLE_USER' => 'User',
            'ROLE_ADMIN' => 'Administrator',
            'ROLE_SUPER_ADMIN' => 'Super Administrator',
        ];
    }

    /**
     * Get formatted role name.
     */
    public function getFormattedRoleName(string $role): string
    {
        $roles = $this->getAvailableRoles();
        return $roles[$role] ?? $role;
    }
}