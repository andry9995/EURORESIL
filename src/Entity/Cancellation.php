<?php

namespace App\Entity;

use App\Enum\CancellationStatus;
use App\Enum\ContractType;
use App\Enum\Occupation;
use App\Repository\CancellationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CancellationRepository::class)]
#[ORM\Table(name: 'cancellations')]
#[ORM\HasLifecycleCallbacks]
class Cancellation
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * Stores subscriber details (category, name, address, etc.)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $subscriber = null;

    #[ORM\Column(nullable: true, enumType: ContractType::class)]
    private ?ContractType $contractType = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contractReference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $extraText = null;

    #[ORM\Column(enumType: CancellationStatus::class)]
    private ?CancellationStatus $status = CancellationStatus::DRAFT;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $letRecoId = null;

    /**
     * @var Collection<int, CancellationProof>
     */
    #[ORM\OneToMany(targetEntity: CancellationProof::class, mappedBy: 'cancellation', cascade: ['persist', 'remove'])]
    private Collection $proofs;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, CancellationProof>
     */
    #[ORM\OneToMany(targetEntity: CancellationProof::class, mappedBy: 'cancellation')]
    private Collection $cancellationProofs;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Document $document = null;

    #[ORM\ManyToOne(inversedBy: 'cancellations')]
    private ?Insurer $insurer = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->proofs = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->cancellationProofs = new ArrayCollection();
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

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSubscriber(): ?array
    {
        return $this->subscriber;
    }

    public function setSubscriber(?array $subscriber): static
    {
        $this->subscriber = $subscriber;
        return $this;
    }

    public function getSubscriberFullName(): string
    {
        if (!$this->subscriber) {
            return '';
        }
        return trim(($this->subscriber['first_name'] ?? '') . ' ' . ($this->subscriber['last_name'] ?? ''));
    }

    public function getContractType(): ?ContractType
    {
        return $this->contractType;
    }

    public function setContractType(?ContractType $contractType): static
    {
        $this->contractType = $contractType;
        return $this;
    }

    public function getContractReference(): ?string
    {
        return $this->contractReference;
    }

    public function setContractReference(?string $contractReference): static
    {
        $this->contractReference = $contractReference;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getExtraText(): ?string
    {
        return $this->extraText;
    }

    public function setExtraText(?string $extraText): static
    {
        $this->extraText = $extraText;
        return $this;
    }

    public function getStatus(): ?CancellationStatus
    {
        return $this->status;
    }

    public function setStatus(?CancellationStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getLetRecoId(): ?string
    {
        return $this->letRecoId;
    }

    public function setLetRecoId(?string $letRecoId): static
    {
        $this->letRecoId = $letRecoId;
        return $this;
    }

    /**
     * @return Collection<int, CancellationProof>
     */
    public function getProofs(): Collection
    {
        return $this->proofs;
    }

    public function addProof(CancellationProof $proof): static
    {
        if (!$this->proofs->contains($proof)) {
            $this->proofs->add($proof);
            $proof->setCancellation($this);
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

    /**
     * @return Collection<int, CancellationProof>
     */
    public function getCancellationProofs(): Collection
    {
        return $this->cancellationProofs;
    }

    public function addCancellationProof(CancellationProof $cancellationProof): static
    {
        if (!$this->cancellationProofs->contains($cancellationProof)) {
            $this->cancellationProofs->add($cancellationProof);
            $cancellationProof->setCancellation($this);
        }

        return $this;
    }

    public function removeCancellationProof(CancellationProof $cancellationProof): static
    {
        if ($this->cancellationProofs->removeElement($cancellationProof)) {
            // set the owning side to null (unless already changed)
            if ($cancellationProof->getCancellation() === $this) {
                $cancellationProof->setCancellation(null);
            }
        }

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getInsurer(): ?Insurer
    {
        return $this->insurer;
    }

    public function setInsurer(?Insurer $insurer): static
    {
        $this->insurer = $insurer;

        return $this;
    }
}