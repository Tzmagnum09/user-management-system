<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Form\UserRolesType;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use App\Service\PermissionManager;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

#[Route('/admin')]
class AdminController extends AbstractController
{
    private AuditLogger $auditLogger;
    private UserManager $userManager;
    private PermissionManager $permissionManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AuditLogger $auditLogger,
        UserManager $userManager,
        PermissionManager $permissionManager,
        EntityManagerInterface $entityManager
    ) {
        $this->auditLogger = $auditLogger;
        $this->userManager = $userManager;
        $this->permissionManager = $permissionManager;
        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'admin_users')]
    public function usersList(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Check specific permission for non-super-admins
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_users')) {
                throw $this->createAccessDeniedException('You do not have permission to view users.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'view_users',
            'Admin viewed the list of users.'
        );

        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepository->findBy([], ['lastName' => 'ASC']),
        ]);
    }

    #[Route('/users/pending-verification', name: 'admin_users_pending_verification')]
    public function pendingVerification(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_users')) {
                throw $this->createAccessDeniedException('You do not have permission to view users.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'view_pending_verification',
            'Admin viewed users pending email verification.'
        );

        return $this->render('admin/users/pending_verification.html.twig', [
            'users' => $userRepository->findPendingEmailVerification(),
        ]);
    }

    #[Route('/users/pending-approval', name: 'admin_users_pending_approval')]
    public function pendingApproval(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $user = $this->getUser();
            if ($user instanceof User && !$this->permissionManager->hasPermission($user, 'view_users')) {
                throw $this->createAccessDeniedException('You do not have permission to view users.');
            }
        }

        // Log the action
        $this->auditLogger->log(
            $this->getUser(),
            'view_pending_approval',
            'Admin viewed users pending account approval.'
        );

        return $this->render('admin/users/pending_approval.html.twig', [
            'users' => $userRepository->findPendingApproval(),
        ]);
    }

    #[Route('/users/{id}/verify', name: 'admin_user_verify', methods: ['POST'])]
    public function verifyUser(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if (!$this->isCsrfTokenValid('verify'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'approve_users')) {
                throw $this->createAccessDeniedException('You do not have permission to verify users.');
            }
        }

        // Verify the user
        $this->userManager->verifyUserEmail($user, $this->getUser());

        $this->addFlash('success', 'User email has been verified.');

        return $this->redirectToRoute('admin_users_pending_verification');
    }

    #[Route('/users/{id}/approve', name: 'admin_user_approve', methods: ['POST'])]
    public function approveUser(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if (!$this->isCsrfTokenValid('approve'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'approve_users')) {
                throw $this->createAccessDeniedException('You do not have permission to approve users.');
            }
        }

        // Approve the user
        $this->userManager->approveUser($user, $this->getUser());

        $this->addFlash('success', 'User account has been approved.');

        return $this->redirectToRoute('admin_users_pending_approval');
    }

    #[Route('/users/{id}/edit', name: 'admin_user_edit')]
    public function editUser(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'edit_users')) {
                throw $this->createAccessDeniedException('You do not have permission to edit users.');
            }
        }

        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Log the action
            $this->auditLogger->log(
                $this->getUser(),
                'edit_user',
                sprintf('Edited user: %s (%s)', $user->getFullName(), $user->getEmail())
            );

            $this->addFlash('success', 'User has been updated.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/roles', name: 'admin_user_roles')]
    public function editUserRoles(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'assign_roles')) {
                throw $this->createAccessDeniedException('You do not have permission to assign roles.');
            }
        }

        // Détecter si le formulaire est soumis depuis le template personnalisé
        if ($request->isMethod('POST') && $request->request->has('form')) {
            $selectedRoles = $request->request->all('form')['roles'] ?? [];
            
            // Log pour debug
            error_log('Roles from form: ' . print_r($selectedRoles, true));
            
            // S'assurer que le tableau n'est pas vide
            if (empty($selectedRoles)) {
                $selectedRoles = ['ROLE_USER']; // Toujours inclure au moins ROLE_USER
            }
            
            // Mettre à jour les rôles
            if ($this->isCsrfTokenValid('roles_' . $user->getId(), $request->request->get('_token'))) {
                $this->userManager->changeUserRole($user, $selectedRoles, $this->getUser());
                $this->addFlash('success', 'User roles have been updated.');
                return $this->redirectToRoute('admin_users');
            } else {
                $this->addFlash('error', 'Invalid CSRF token.');
            }
        }

        // Créer un formulaire Symfony pour validation (mais nous utilisons le template personnalisé)
        $form = $this->createForm(UserRolesType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des rôles du formulaire standard
            $roles = $user->getRoles();
            
            // S'assurer que ROLE_USER est toujours présent
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
                $user->setRoles($roles);
            }
            
            // Enregistrer les changements
            $this->userManager->changeUserRole($user, $roles, $this->getUser());
            $this->addFlash('success', 'User roles have been updated.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/roles.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/users/{id}/permissions', name: 'admin_user_permissions')]
    public function editUserPermissions(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Only admins can have permissions
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Only administrators can have permissions.');
            return $this->redirectToRoute('admin_users');
        }

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'manage_permissions')) {
                throw $this->createAccessDeniedException('You do not have permission to manage permissions.');
            }
        }

        // Get current permissions
        $userPermissions = $this->permissionManager->getPermissionsForAdmin($user);
        
        // Handle form submission
        if ($request->isMethod('POST')) {
            $selectedPermissions = $request->request->all('permissions') ?? [];
            
            // Update permissions
            $this->permissionManager->updatePermissions($user, $selectedPermissions, $this->getUser());
            
            $this->addFlash('success', 'User permissions have been updated.');
            
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/permissions.html.twig', [
            'user' => $user,
            'permissions' => $this->permissionManager->getAvailablePermissions(),
            'userPermissions' => $userPermissions,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Check permissions
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $currentUser = $this->getUser();
            if ($currentUser instanceof User && !$this->permissionManager->hasPermission($currentUser, 'delete_users')) {
                throw $this->createAccessDeniedException('You do not have permission to delete users.');
            }
        }

        // Prevent self-deletion
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_users');
        }

        // Delete the user
        $this->userManager->deleteUser($user, $this->getUser());

        $this->addFlash('success', 'User has been deleted.');

        return $this->redirectToRoute('admin_users');
    }
}