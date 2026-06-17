<?php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ref_assureurs')]
class RefAssureur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $aliases = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lreEmail = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }
    public function getAliases(): ?array { return $this->aliases; }
    public function setAliases(?array $a): static { $this->aliases = $a; return $this; }
    public function getLreEmail(): ?string { return $this->lreEmail; }
    public function setLreEmail(?string $e): static { $this->lreEmail = $e; return $this; }
    public function isActive(): bool { return $this->active; }
}
