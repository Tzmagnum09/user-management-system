<?php

namespace App\Entity;

use App\Repository\HashStorageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HashStorageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class HashStorage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'hashStorage', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $md5 = null;

    #[ORM\Column(length: 255)]
    private ?string $sha1 = null;

    #[ORM\Column(length: 255)]
    private ?string $sha224 = null;

    #[ORM\Column(length: 255)]
    private ?string $sha256 = null;

    #[ORM\Column(length: 255)]
    private ?string $sha384 = null;

    #[ORM\Column(length: 255)]
    private ?string $sha512 = null;

    #[ORM\Column(length: 255)]
    private ?string $sha3 = null;

    #[ORM\Column(length: 255)]
    private ?string $bcrypt = null;

    #[ORM\Column(length: 255)]
    private ?string $scrypt = null;

    #[ORM\Column(length: 255)]
    private ?string $argon2 = null;

    #[ORM\Column(length: 255)]
    private ?string $argon2i = null;

    #[ORM\Column(length: 255)]
    private ?string $argon2d = null;

    #[ORM\Column(length: 255)]
    private ?string $argon2id = null;

    #[ORM\Column(length: 255)]
    private ?string $pbkdf2 = null;

    #[ORM\Column(length: 255)]
    private ?string $whirlpool = null;

    #[ORM\Column(length: 255)]
    private ?string $ntlm = null;

    #[ORM\Column(length: 255)]
    private ?string $blowfish = null;

    #[ORM\Column(length: 255)]
    private ?string $crypt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMd5(): ?string
    {
        return $this->md5;
    }

    public function setMd5(string $md5): self
    {
        $this->md5 = $md5;

        return $this;
    }

    public function getSha1(): ?string
    {
        return $this->sha1;
    }

    public function setSha1(string $sha1): self
    {
        $this->sha1 = $sha1;

        return $this;
    }

    public function getSha224(): ?string
    {
        return $this->sha224;
    }

    public function setSha224(string $sha224): self
    {
        $this->sha224 = $sha224;

        return $this;
    }

    public function getSha256(): ?string
    {
        return $this->sha256;
    }

    public function setSha256(string $sha256): self
    {
        $this->sha256 = $sha256;

        return $this;
    }

    public function getSha384(): ?string
    {
        return $this->sha384;
    }

    public function setSha384(string $sha384): self
    {
        $this->sha384 = $sha384;

        return $this;
    }

    public function getSha512(): ?string
    {
        return $this->sha512;
    }

    public function setSha512(string $sha512): self
    {
        $this->sha512 = $sha512;

        return $this;
    }

    public function getSha3(): ?string
    {
        return $this->sha3;
    }

    public function setSha3(string $sha3): self
    {
        $this->sha3 = $sha3;

        return $this;
    }

    public function getBcrypt(): ?string
    {
        return $this->bcrypt;
    }

    public function setBcrypt(string $bcrypt): self
    {
        $this->bcrypt = $bcrypt;

        return $this;
    }

    public function getScrypt(): ?string
    {
        return $this->scrypt;
    }

    public function setScrypt(string $scrypt): self
    {
        $this->scrypt = $scrypt;

        return $this;
    }

    public function getArgon2(): ?string
    {
        return $this->argon2;
    }

    public function setArgon2(string $argon2): self
    {
        $this->argon2 = $argon2;

        return $this;
    }

    public function getArgon2i(): ?string
    {
        return $this->argon2i;
    }

    public function setArgon2i(string $argon2i): self
    {
        $this->argon2i = $argon2i;

        return $this;
    }

    public function getArgon2d(): ?string
    {
        return $this->argon2d;
    }

    public function setArgon2d(string $argon2d): self
    {
        $this->argon2d = $argon2d;

        return $this;
    }

    public function getArgon2id(): ?string
    {
        return $this->argon2id;
    }

    public function setArgon2id(string $argon2id): self
    {
        $this->argon2id = $argon2id;

        return $this;
    }

    public function getPbkdf2(): ?string
    {
        return $this->pbkdf2;
    }

    public function setPbkdf2(string $pbkdf2): self
    {
        $this->pbkdf2 = $pbkdf2;

        return $this;
    }

    public function getWhirlpool(): ?string
    {
        return $this->whirlpool;
    }

    public function setWhirlpool(string $whirlpool): self
    {
        $this->whirlpool = $whirlpool;

        return $this;
    }

    public function getNtlm(): ?string
    {
        return $this->ntlm;
    }

    public function setNtlm(string $ntlm): self
    {
        $this->ntlm = $ntlm;

        return $this;
    }

    public function getBlowfish(): ?string
    {
        return $this->blowfish;
    }

    public function setBlowfish(string $blowfish): self
    {
        $this->blowfish = $blowfish;

        return $this;
    }

    public function getCrypt(): ?string
    {
        return $this->crypt;
    }

    public function setCrypt(string $crypt): self
    {
        $this->crypt = $crypt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}