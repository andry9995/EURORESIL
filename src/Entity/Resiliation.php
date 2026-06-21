<?php

namespace App\Entity;

use App\Repository\ResilationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ResilationRepository::class)]
#[ORM\Table(name: 'resiliations')]
#[ORM\HasLifecycleCallbacks]
class Resiliation
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SIGNING = 'signing';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $souscripteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assureurNom = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $typeContrat = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contratRef = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $extraText = null;

    #[ORM\Column(length: 30, options: ['default' => 'draft'])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $letterPdfPath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mandatPdfPath = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $universignTxId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $letrecoEnvoiId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $letrecoPliNumber = null;

    #[ORM\OneToMany(mappedBy: 'resiliation', targetEntity: ResilPreuve::class, cascade: ['persist', 'remove'])]
    private Collection $preuves;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->preuves = new ArrayCollection();
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $u): static
    {
        $this->user = $u;
        return $this;
    }

    public function getSouscripteur(): ?array
    {
        return $this->souscripteur;
    }

    public function setSouscripteur(?array $s): static
    {
        $this->souscripteur = $s;
        return $this;
    }

    public function getSouscripteurNomComplet(): string
    {
        if (!$this->souscripteur) return '';
        return trim(($this->souscripteur['prenom'] ?? '') . ' ' . ($this->souscripteur['nom'] ?? ''));
    }

    public function getAssureurNom(): ?string
    {
        return $this->assureurNom;
    }

    public function setAssureurNom(?string $a): static
    {
        $this->assureurNom = $a;
        return $this;
    }

    public function getTypeContrat(): ?string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?string $t): static
    {
        $this->typeContrat = $t;
        return $this;
    }

    public function getContratRef(): ?string
    {
        return $this->contratRef;
    }

    public function setContratRef(?string $r): static
    {
        $this->contratRef = $r;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $m): static
    {
        $this->motif = $m;
        return $this;
    }

    public function getExtraText(): ?string
    {
        return $this->extraText;
    }

    public function setExtraText(?string $t): static
    {
        $this->extraText = $t;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $s): static
    {
        $this->status = $s;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'signing' => 'Signature en cours',
            'sending' => 'En envoi',
            'sent' => 'Envoyee',
            'received' => 'Recue',
            'failed' => 'Echouee',
            default => $this->status,
        };
    }

    public function getLetterPdfPath(): ?string
    {
        return $this->letterPdfPath;
    }

    public function setLetterPdfPath(?string $p): static
    {
        $this->letterPdfPath = $p;
        return $this;
    }

    public function getMandatPdfPath(): ?string
    {
        return $this->mandatPdfPath;
    }

    public function setMandatPdfPath(?string $p): static
    {
        $this->mandatPdfPath = $p;
        return $this;
    }

    public function getUniversignTxId(): ?string
    {
        return $this->universignTxId;
    }

    public function setUniversignTxId(?string $i): static
    {
        $this->universignTxId = $i;
        return $this;
    }

    public function getLetrecoEnvoiId(): ?string
    {
        return $this->letrecoEnvoiId;
    }

    public function setLetrecoEnvoiId(?string $i): static
    {
        $this->letrecoEnvoiId = $i;
        return $this;
    }

    public function getLetrecoPliNumber(): ?string
    {
        return $this->letrecoPliNumber;
    }

    public function setLetrecoPliNumber(?string $n): static
    {
        $this->letrecoPliNumber = $n;
        return $this;
    }

    public function getPreuves(): Collection
    {
        return $this->preuves;
    }

    public function addPreuve(ResilPreuve $p): static
    {
        if (!$this->preuves->contains($p)) {
            $this->preuves->add($p);
            $p->setResiliation($this);
        }
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
