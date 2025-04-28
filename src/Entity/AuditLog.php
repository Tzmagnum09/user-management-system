<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AuditLog
{
    // Types de logs
    public const TYPE_VIEW = 'view';
    public const TYPE_CREATE = 'create';
    public const TYPE_UPDATE = 'update';
    public const TYPE_DELETE = 'delete';
    public const TYPE_LOGIN = 'login';
    public const TYPE_SECURITY = 'security';
    public const TYPE_ERROR = 'error';
    public const TYPE_SYSTEM = 'system';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'auditLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceBrand = 'Unknown';
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceModel = 'Unknown';
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $osName = 'Unknown';
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $osVersion = 'Unknown';
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $browserName = 'Unknown';
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $browserVersion = 'Unknown';

    public function __construct()
    {
        // Initialiser les valeurs par défaut
        $this->deviceBrand = 'Unknown';
        $this->deviceModel = 'Unknown';
        $this->osName = 'Unknown';
        $this->osVersion = 'Unknown';
        $this->browserName = 'Unknown';
        $this->browserVersion = 'Unknown';
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
    
    public function getDeviceBrand(): ?string
    {
        return $this->deviceBrand ?? 'Unknown';
    }

    public function setDeviceBrand(?string $deviceBrand): static
    {
        $this->deviceBrand = $deviceBrand ?: 'Unknown';

        return $this;
    }

    public function getDeviceModel(): ?string
    {
        return $this->deviceModel ?? 'Unknown';
    }

    public function setDeviceModel(?string $deviceModel): static
    {
        $this->deviceModel = $deviceModel ?: 'Unknown';

        return $this;
    }

    public function getOsName(): ?string
    {
        return $this->osName ?? 'Unknown';
    }

    public function setOsName(?string $osName): static
    {
        $this->osName = $osName ?: 'Unknown';

        return $this;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion ?? 'Unknown';
    }

    public function setOsVersion(?string $osVersion): static
    {
        $this->osVersion = $osVersion ?: 'Unknown';

        return $this;
    }

    public function getBrowserName(): ?string
    {
        return $this->browserName ?? 'Unknown';
    }

    public function setBrowserName(?string $browserName): static
    {
        $this->browserName = $browserName ?: 'Unknown';

        return $this;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion ?? 'Unknown';
    }

    public function setBrowserVersion(?string $browserVersion): static
    {
        $this->browserVersion = $browserVersion ?: 'Unknown';

        return $this;
    }
    
    /**
     * Retourne le label du type de log (pour l'affichage)
     */
    public function getTypeLabel(): string
    {
        $labels = self::getAvailableTypes();
        return $labels[$this->type] ?? ucfirst($this->type);
    }
    
    /**
     * Retourne les types de logs disponibles avec leurs labels
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_VIEW => 'Consultation',
            self::TYPE_CREATE => 'Création',
            self::TYPE_UPDATE => 'Modification',
            self::TYPE_DELETE => 'Suppression',
            self::TYPE_LOGIN => 'Connexion',
            self::TYPE_SECURITY => 'Sécurité',
            self::TYPE_ERROR => 'Erreur',
            self::TYPE_SYSTEM => 'Système',
        ];
    }
}