<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'resil_preuves')]
class ResilPreuve
{
    public const TYPE_DEPOT      = 'depot';
    public const TYPE_RECEPTION  = 'reception';
    public const TYPE_RETRAIT    = 'retrait';
    public const TYPE_NEGLIGENCE = 'negligence';

    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Resiliation::class, inversedBy: 'preuves')]
    #[ORM\JoinColumn(nullable: false)]
    private Resiliation $resiliation;

    #[ORM\Column(length: 30)]
    private string $type;

    #[ORM\Column]
    private \DateTimeImmutable $eventDate;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proofPdfPath = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawPayload = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id        = Uuid::v4()->toRfc4122();
        $this->eventDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getResiliation(): Resiliation { return $this->resiliation; }
    public function setResiliation(Resiliation $r): static { $this->resiliation = $r; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getTypeLabel(): string {
        return match($this->type) {
            'depot'      => 'Depot',
            'reception'  => 'Reception',
            'retrait'    => 'Retrait',
            'negligence' => 'Negligence',
            default      => $this->type,
        };
    }
    public function getEventDate(): \DateTimeImmutable { return $this->eventDate; }
    public function setEventDate(\DateTimeImmutable $d): static { $this->eventDate = $d; return $this; }
    public function getProofPdfPath(): ?string { return $this->proofPdfPath; }
    public function setProofPdfPath(?string $p): static { $this->proofPdfPath = $p; return $this; }
    public function getRawPayload(): ?array { return $this->rawPayload; }
    public function setRawPayload(?array $p): static { $this->rawPayload = $p; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
