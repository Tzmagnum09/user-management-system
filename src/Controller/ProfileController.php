<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\UserProfileType;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use App\Service\PasswordHashService;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AuditLogger $auditLogger;
    private TranslatorInterface $translator;
    private UserManager $userManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        AuditLogger $auditLogger,
        TranslatorInterface $translator,
        UserManager $userManager
    ) {
        $this->entityManager = $entityManager;
        $this->auditLogger = $auditLogger;
        $this->translator = $translator;
        $this->userManager = $userManager;
    }

    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('You need to be logged in to access your profile.');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('You need to be logged in to edit your profile.');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Log the action
            $this->auditLogger->log(
                $user,
                'update_profile',
                'User updated their profile information.'
            );

            $this->addFlash('success', 'Your profile has been updated successfully.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('You need to be logged in to change your password.');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Check if the current password is correct
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('error', $this->translator->trans('Current password is incorrect.'));
                return $this->redirectToRoute('app_profile_change_password');
            }
            
            try {
                // Update password via UserManager to ensure all hashes are updated
                $this->userManager->updatePassword($user, $data['newPassword']);
                
                // Log the action
                $this->auditLogger->log(
                    $user,
                    'change_password',
                    'User changed their password.'
                );
                
                // Add success flash message
                $this->addFlash('success', $this->translator->trans('Your password has been changed successfully.'));
                
                // Redirect to profile page
                return $this->redirectToRoute('app_profile');
                
            } catch (\Exception $e) {
                // Log the error
                error_log('Password change error: ' . $e->getMessage());
                
                // Add error flash message
                $this->addFlash('error', $this->translator->trans('An error occurred while changing your password. Please try again.'));
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}