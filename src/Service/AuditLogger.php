<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function log(User $user, string $action, ?string $details = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request ? $request->getClientIp() : '0.0.0.0';

        $auditLog = new AuditLog();
        $auditLog->setUser($user);
        $auditLog->setAction($action);
        $auditLog->setDetails($details);
        $auditLog->setIpAddress($ipAddress);

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }
}
