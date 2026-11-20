<?php

namespace App\Entity;

use App\Repository\DialogueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DialogueRepository::class)]
class Dialogue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $contextType = 'general';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contextLabel = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: DialogueBlock::class, mappedBy: 'dialogue', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['blockOrder' => 'ASC'])]
    private Collection $blocks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->blocks = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getContextType(): string { return $this->contextType; }
    public function setContextType(string $contextType): static { $this->contextType = $contextType; return $this; }

    public function getContextLabel(): ?string { return $this->contextLabel; }
    public function setContextLabel(?string $contextLabel): static { $this->contextLabel = $contextLabel; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }

    /** @return Collection<int, DialogueBlock> */
    public function getBlocks(): Collection { return $this->blocks; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contextType' => $this->contextType,
            'contextLabel' => $this->contextLabel,
            'createdAt' => $this->createdAt->getTimestamp() * 1000,
            'updatedAt' => $this->updatedAt->getTimestamp() * 1000,
            'blocks' => array_values(array_map(fn(DialogueBlock $b) => $b->toArray(), $this->blocks->toArray())),
        ];
    }
}
