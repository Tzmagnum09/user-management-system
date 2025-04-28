<?php

namespace App\Service;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordManager
{
    private const TOKEN_TTL = 3600; // 1 hour in seconds
    
    private EntityManagerInterface $entityManager;
    private ResetPasswordRequestRepository $resetPasswordRequestRepository;
    private EmailManager $emailManager;
    private AuditLogger $auditLogger;
    private ParameterBagInterface $params;
    private UrlGeneratorInterface $urlGenerator;
    private PasswordHashService $passwordHashService;
    
    public function __construct(
        EntityManagerInterface $entityManager,
        ResetPasswordRequestRepository $resetPasswordRequestRepository,
        EmailManager $emailManager,
        AuditLogger $auditLogger,
        ParameterBagInterface $params,
        UrlGeneratorInterface $urlGenerator,
        PasswordHashService $passwordHashService
    ) {
        $this->entityManager = $entityManager;
        $this->resetPasswordRequestRepository = $resetPasswordRequestRepository;
        $this->emailManager = $emailManager;
        $this->auditLogger = $auditLogger;
        $this->params = $params;
        $this->urlGenerator = $urlGenerator;
        $this->passwordHashService = $passwordHashService;
    }
    
    /**
     * Create a new reset password request for a user and send the email
     */
    public function sendResetPasswordEmail(User $user): void
    {
        // Remove existing reset requests for this user
        $this->resetPasswordRequestRepository->removeAllForUser($user);
        
        // Create a new reset request
        $resetRequest = $this->createResetPasswordRequest($user);
        
        // Generate reset URL
        $appUrl = $this->params->get('app.url');
        $resetUrl = $appUrl . $this->urlGenerator->generate('app_reset_password', [
            'token' => $resetRequest->getToken()
        ]);
        
        // Send email with reset link
        $this->emailManager->sendEmailToUser($user, 'reset_password', [
            'resetToken' => $resetUrl,
            'tokenLifetime' => '1 hour', // This will be translated in the template
        ]);
        
        // Log the action
        $this->auditLogger->log(
            $user,
            'password_reset_requested',
            'Password reset was requested for this account.'
        );
    }
    
    /**
     * Get a reset password request by token
     */
    public function getResetPasswordRequest(string $token): ?ResetPasswordRequest
    {
        return $this->resetPasswordRequestRepository->findActiveRequestByToken($token);
    }
    
    /**
     * Remove a reset password request
     */
    public function removeResetRequest(ResetPasswordRequest $resetRequest): void
    {
        $this->entityManager->remove($resetRequest);
        $this->entityManager->flush();
    }
    
    /**
     * Clean up expired reset password requests
     */
    public function removeExpiredResetRequests(): int
    {
        return $this->resetPasswordRequestRepository->removeExpiredRequests();
    }
    
    /**
     * Create a new reset password request
     */
    private function createResetPasswordRequest(User $user): ResetPasswordRequest
    {
        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        
        // Get token lifetime from parameters or use default
        $tokenLifetime = (int) $this->params->get('PASSWORD_RESET_TOKEN_TTL', self::TOKEN_TTL);
        
        // Create expires timestamp
        $expiresAt = new \DateTimeImmutable('@' . (time() + $tokenLifetime));
        
        // Create reset request
        $resetRequest = new ResetPasswordRequest();
        $resetRequest->setUser($user);
        $resetRequest->setToken($token);
        $resetRequest->setExpiresAt($expiresAt);
        $resetRequest->setRequestedAt(new \DateTimeImmutable());
        
        // Save to database
        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();
        
        return $resetRequest;
    }
    
    /**
     * Reset a user's password and cleanup the reset request
     */
    public function resetPassword(ResetPasswordRequest $resetRequest, string $hashedPassword, string $plainPassword): void
    {
        $user = $resetRequest->getUser();
        
        // Update the password
        $user->setPassword($hashedPassword);
        
        // Remove the reset request
        $this->entityManager->remove($resetRequest);
        
        // Flush changes to database
        $this->entityManager->flush();
        
        // Update all password hashes
        $this->passwordHashService->storeAllPasswordHashes($user, $plainPassword);
        
        // Log the action
        $this->auditLogger->log(
            $user,
            'password_reset_completed',
            'Password was successfully reset.'
        );
    }
}