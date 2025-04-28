<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\AuditLogger;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        UserManager $userManager,
        AuditLogger $auditLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        
        // Update last login time
        if ($user instanceof \App\Entity\User) {
            $userManager->updateLastLogin($user);
            
            // Log login
            $auditLogger->log(
                $user,
                'user_logged_in',
                'User logged in to the dashboard.'
            );
        }

        // Different dashboard based on role
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->render('dashboard/super_admin.html.twig', [
                'pendingVerification' => $userRepository->findPendingEmailVerification(),
                'pendingApproval' => $userRepository->findPendingApproval(),
                'admins' => $userRepository->findAdmins(),
                'users' => $userRepository->findBy(['isVerified' => true, 'isApproved' => true]),
            ]);
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('dashboard/admin.html.twig');
        } else {
            return $this->render('dashboard/user.html.twig');
        }
    }
}
