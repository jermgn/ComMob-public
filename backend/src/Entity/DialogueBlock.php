<?php

namespace App\Entity;

use App\Repository\DialogueBlockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DialogueBlockRepository::class)]
class DialogueBlock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Dialogue::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dialogue $dialogue = null;

    #[ORM\Column(type: 'text')]
    private string $text = '';

    #[ORM\Column]
    private int $blockOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDialogue(): ?Dialogue { return $this->dialogue; }
    public function setDialogue(?Dialogue $dialogue): static { $this->dialogue = $dialogue; return $this; }

    public function getText(): string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }

    public function getBlockOrder(): int { return $this->blockOrder; }
    public function setBlockOrder(int $blockOrder): static { $this->blockOrder = $blockOrder; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'order' => $this->blockOrder,
        ];
    }
}
