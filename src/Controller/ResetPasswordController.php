<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use App\Service\EmailManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    private const TOKEN_TTL = 3600; // 1 hour in seconds

    private EntityManagerInterface $entityManager;
    private AuditLogger $auditLogger;
    private EmailManager $emailManager;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager, 
        AuditLogger $auditLogger, 
        EmailManager $emailManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->auditLogger = $auditLogger;
        $this->emailManager = $emailManager;
        $this->requestStack = $requestStack;
    }

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request, UserRepository $userRepository, TranslatorInterface $translator): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $userRepository,
                $translator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('reset_password/check_email.html.twig');
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        TranslatorInterface $translator, 
        ResetPasswordRequestRepository $resetPasswordRequestRepository,
        string $token = null
    ): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // We first check if we have token in the URL
        if ($token) {
            // Validate token and retrieve the request
            $resetRequest = $resetPasswordRequestRepository->findOneBy(['token' => $token]);
            
            if (!$resetRequest) {
                $this->addFlash('reset_password_error', $translator->trans('The reset password link is invalid.'));
                return $this->redirectToRoute('app_forgot_password_request');
            }
            
            // Check if token is expired
            $expiresAt = $resetRequest->getExpiresAt();
            
            if ($expiresAt->getTimestamp() < time()) {
                $this->entityManager->remove($resetRequest);
                $this->entityManager->flush();
                
                $this->addFlash('reset_password_error', $translator->trans('The reset password link has expired.'));
                return $this->redirectToRoute('app_forgot_password_request');
            }

            // Store the token in session for validation after form submission
            $request->getSession()->set('reset_token', $token);
        } else {
            // No token provided in URL - check if we have one in session
            $token = $request->getSession()->get('reset_token');
            if (!$token) {
                $this->addFlash('reset_password_error', $translator->trans('No reset password token found.'));
                return $this->redirectToRoute('app_forgot_password_request');
            }
        }

        // At this point, we have a token (either from URL or session)
        $resetRequest = $resetPasswordRequestRepository->findOneBy(['token' => $token]);
        if (!$resetRequest) {
            $this->addFlash('reset_password_error', $translator->trans('The reset password link is invalid or has expired.'));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Create the form
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Get the user associated with the token
                $user = $resetRequest->getUser();
                
                // Hash the new password
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );
                
                // Set the new password
                $user->setPassword($hashedPassword);
                
                // Remove the reset request from database
                $this->entityManager->remove($resetRequest);
                $this->entityManager->flush();
                
                // Clear the reset token from session
                $request->getSession()->remove('reset_token');
                
                // Log the password reset
                $this->auditLogger->log(
                    $user,
                    'password_reset_completed',
                    'Password was successfully reset.'
                );
                
                // Add a success flash message
                $this->addFlash('success', $translator->trans('Your password has been reset successfully. You can now log in with your new password.'));
                
                // Redirect to login page
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('An error occurred while resetting your password. Please try again.'));
            }
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * Process the password reset request and send email
     */
    private function processSendingPasswordResetEmail(
        string $emailFormData, 
        UserRepository $userRepository, 
        TranslatorInterface $translator
    ): RedirectResponse
    {
        $user = $userRepository->findOneBy(['email' => $emailFormData]);

        // Do not reveal whether a user account was found or not
        if (!$user) {
            $this->addFlash('reset_password_sent', $translator->trans('If an account was found with this email, a password reset link has been sent.'));
            return $this->redirectToRoute('app_check_email');
        }

        // Check if the user is verified and approved
        if (!$user->isVerified()) {
            $this->addFlash('reset_password_error', $translator->trans('You need to verify your email before you can reset your password.'));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        if (!$user->isApproved()) {
            $this->addFlash('reset_password_error', $translator->trans('Your account is pending approval. Please wait for an administrator to approve your account.'));
            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Remove any existing reset requests for this user
        $existingRequests = $this->entityManager->getRepository(ResetPasswordRequest::class)
            ->findBy(['user' => $user]);
        
        foreach ($existingRequests as $request) {
            $this->entityManager->remove($request);
        }
        $this->entityManager->flush();

        // Generate a new token and create a reset request
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');
        
        $resetRequest = new ResetPasswordRequest();
        $resetRequest->setUser($user);
        $resetRequest->setToken($token);
        $resetRequest->setExpiresAt($expiresAt);
        $resetRequest->setRequestedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        // Send reset password email
        $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/');
        $resetUrl = $appUrl . $this->generateUrl('app_reset_password', ['token' => $resetRequest->getToken()]);
        
        $this->emailManager->sendEmailToUser(
            $user,
            'reset_password',
            [
                'resetToken' => $resetUrl,
                'tokenLifetime' => '1 ' . $translator->trans('hour'),
            ]
        );

        // Log the password reset request
        $this->auditLogger->log(
            $user,
            'password_reset_requested',
            'Password reset was requested for this account.'
        );

        $this->addFlash('reset_password_sent', $translator->trans('If an account was found with this email, a password reset link has been sent.'));
        return $this->redirectToRoute('app_check_email');
    }
}