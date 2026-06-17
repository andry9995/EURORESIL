<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'packs')]
class Pack
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $qtyPurchased;

    #[ORM\Column(type: 'integer')]
    private int $qtyRemaining;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $unitPrice;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amountTtc;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id        = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+12 months');
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getQtyPurchased(): int { return $this->qtyPurchased; }
    public function setQtyPurchased(int $q): static { $this->qtyPurchased = $q; $this->qtyRemaining = $q; return $this; }
    public function getQtyRemaining(): int { return $this->qtyRemaining; }
    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $p): static { $this->unitPrice = $p; return $this; }
    public function getAmountTtc(): string { return $this->amountTtc; }
    public function setAmountTtc(string $a): static { $this->amountTtc = $a; return $this; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
