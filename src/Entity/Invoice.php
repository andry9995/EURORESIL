<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'invoices')]
class Invoice
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Pack::class)]
    private ?Pack $pack = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $invoiceNumber;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amountHt;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '20.00'])]
    private string $tvaRate = '20.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amountTtc;

    #[ORM\Column(nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column]
    private \DateTimeImmutable $issuedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    public function __construct()
    {
        $this->id       = Uuid::v4()->toRfc4122();
        $this->issuedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $u): static { $this->user = $u; return $this; }
    public function getPack(): ?Pack { return $this->pack; }
    public function setPack(?Pack $p): static { $this->pack = $p; return $this; }
    public function getInvoiceNumber(): string { return $this->invoiceNumber; }
    public function setInvoiceNumber(string $n): static { $this->invoiceNumber = $n; return $this; }
    public function getAmountHt(): string { return $this->amountHt; }
    public function setAmountHt(string $a): static { $this->amountHt = $a; return $this; }
    public function getTvaRate(): string { return $this->tvaRate; }
    public function getAmountTtc(): string { return $this->amountTtc; }
    public function setAmountTtc(string $a): static { $this->amountTtc = $a; return $this; }
    public function getPdfPath(): ?string { return $this->pdfPath; }
    public function setPdfPath(?string $p): static { $this->pdfPath = $p; return $this; }
    public function getIssuedAt(): \DateTimeImmutable { return $this->issuedAt; }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }
}
