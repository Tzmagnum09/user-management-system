<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\AuditLogger;
use App\Service\EmailManager;
use App\Service\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private UserManager $userManager;
    private EmailManager $emailManager;
    private AuditLogger $auditLogger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserManager $userManager,
        EmailManager $emailManager,
        AuditLogger $auditLogger,
        EntityManagerInterface $entityManager
    ) {
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->userManager = $userManager;
        $this->emailManager = $emailManager;
        $this->auditLogger = $auditLogger;
        $this->entityManager = $entityManager;
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        TokenStorageInterface $tokenStorage
    ): Response {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get plain password
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Définir explicitement le rôle ROLE_USER avant la création
            $user->setRoles(['ROLE_USER']);
            
            // Create user with the UserManager service
            $this->userManager->createUser($user, $plainPassword);

            // Generate a signed URL and send it by email
            $signedUrl = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                (string) $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            )->getSignedUrl();

            // Send confirmation email
            $this->emailManager->sendEmailToUser(
                $user,
                'registration_confirmation',
                [
                    'signedUrl' => $signedUrl,
                ]
            );

            // Log the registration
            $this->auditLogger->log(
                $user,
                'user_registered',
                'User registered an account.'
            );

            return $this->render('registration/confirmation_email_sent.html.twig', [
                'userEmail' => $user->getEmail(),
            ]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
        UserRepository $userRepository
    ): Response {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // Validate email confirmation link
        try {
            $this->verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                (string) $user->getId(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // Mark user as verified
        $this->userManager->verifyUserEmail($user);

        // Log the verification
        $this->auditLogger->log(
            $user,
            'email_verified',
            'User verified their email address.'
        );

        $this->addFlash('success', 'Your email address has been verified. Your account is now awaiting approval by an administrator.');

        return $this->redirectToRoute('app_login');
    }
}