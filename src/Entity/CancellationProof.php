<?php

namespace App\Entity;

use App\Enum\CancellationProofType;
use App\Repository\CancellationProofRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CancellationProofRepository::class)]
class CancellationProof
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cancellationProofs')]
    private ?Cancellation $cancellation = null;

    #[ORM\Column(enumType: CancellationProofType::class)]
    private ?CancellationProofType $type = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Document $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCancellation(): ?Cancellation
    {
        return $this->cancellation;
    }

    public function setCancellation(?Cancellation $cancellation): static
    {
        $this->cancellation = $cancellation;

        return $this;
    }

    public function getType(): ?CancellationProofType
    {
        return $this->type;
    }

    public function setType(CancellationProofType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
}
