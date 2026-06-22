<?php

namespace App\Entity;

use App\Repository\InsurerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InsurerRepository::class)]
class Insurer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var Collection<int, Cancellation>
     */
    #[ORM\OneToMany(targetEntity: Cancellation::class, mappedBy: 'insurer')]
    private Collection $cancellations;

    public function __construct()
    {
        $this->cancellations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Cancellation>
     */
    public function getCancellations(): Collection
    {
        return $this->cancellations;
    }

    public function addCancellation(Cancellation $cancellation): static
    {
        if (!$this->cancellations->contains($cancellation)) {
            $this->cancellations->add($cancellation);
            $cancellation->setInsurer($this);
        }

        return $this;
    }

    public function removeCancellation(Cancellation $cancellation): static
    {
        if ($this->cancellations->removeElement($cancellation)) {
            // set the owning side to null (unless already changed)
            if ($cancellation->getInsurer() === $this) {
                $cancellation->setInsurer(null);
            }
        }

        return $this;
    }
}
