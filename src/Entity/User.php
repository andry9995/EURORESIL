<?php

namespace App\Entity;

use App\Enum\Occupation;
use App\Enum\Profile;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe deja avec cet email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(enumType: Profile::class)]
    private ?Profile $profile = Profile::PARTICULAR;

    #[ORM\Column(enumType: Occupation::class)]
    private ?Occupation $profession = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $raisonSociale = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $registryType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $registryNumber = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verificationCodeExpiresAt = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $credits = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $letRecoUserId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $letRecoPassword = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $r = $this->roles;
        $r[] = 'ROLE_USER';
        return array_unique($r);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $p): static
    {
        $this->password = $p;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;
        return $this;
    }

    public function getProfession(): ?Occupation
    {
        return $this->profession;
    }

    public function setProfession(?Occupation $profession): static
    {
        $this->profession = $profession;
        return $this;
    }

    public function getRaisonSociale(): ?string
    {
        return $this->raisonSociale;
    }

    public function setRaisonSociale(?string $r): static
    {
        $this->raisonSociale = $r;
        return $this;
    }

    public function getRegistryType(): ?string
    {
        return $this->registryType;
    }

    public function setRegistryType(?string $t): static
    {
        $this->registryType = $t;
        return $this;
    }

    public function getRegistryNumber(): ?string
    {
        return $this->registryNumber;
    }

    public function setRegistryNumber(?string $n): static
    {
        $this->registryNumber = $n;
        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $s): static
    {
        $this->siret = $s;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $d): static
    {
        $this->emailVerifiedAt = $d;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $c): static
    {
        $this->verificationCode = $c;
        return $this;
    }

    public function getVerificationCodeExpiresAt(): ?\DateTimeImmutable
    {
        return $this->verificationCodeExpiresAt;
    }

    public function setVerificationCodeExpiresAt(?\DateTimeImmutable $d): static
    {
        $this->verificationCodeExpiresAt = $d;
        return $this;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $c): static
    {
        $this->credits = $c;
        return $this;
    }

    public function addCredits(int $amount): static
    {
        $this->credits += $amount;
        return $this;
    }

    public function deductCredit(): bool
    {
        if ($this->credits < 1) return false;
        $this->credits--;
        return true;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isPro(): bool
    {
        return $this->profile === Profile::PRO;
    }

    public function isCourtier(): bool
    {
        return $this->isPro() && $this->profession === Occupation::BROKER;
    }

    public function getLetRecoUserId(): ?string
    {
        return $this->letRecoUserId;
    }

    public function setLetRecoUserId(?string $letRecoUserId): static
    {
        $this->letRecoUserId = $letRecoUserId;

        return $this;
    }

    public function getLetRecoPassword(): ?string
    {
        return $this->letRecoPassword;
    }

    public function setLetRecoPassword(?string $letRecoPassword): static
    {
        $this->letRecoPassword = $letRecoPassword;

        return $this;
    }
}
