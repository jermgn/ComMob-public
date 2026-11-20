<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiDescription = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $savedPhrases = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $customContexts = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $otpCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $otpExpiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getAge(): ?int { return $this->age; }
    public function setAge(?int $age): static { $this->age = $age; return $this; }

    public function getGender(): ?string { return $this->gender; }
    public function setGender(?string $gender): static { $this->gender = $gender; return $this; }

    public function getAiDescription(): ?string { return $this->aiDescription; }
    public function setAiDescription(?string $aiDescription): static { $this->aiDescription = $aiDescription; return $this; }

    public function getSavedPhrases(): array { return $this->savedPhrases ?? []; }
    public function setSavedPhrases(array $savedPhrases): static { $this->savedPhrases = $savedPhrases; return $this; }

    public function getCustomContexts(): array { return $this->customContexts ?? []; }
    public function setCustomContexts(array $customContexts): static { $this->customContexts = $customContexts; return $this; }

    public function getOtpCode(): ?string { return $this->otpCode; }
    public function setOtpCode(?string $otpCode): static { $this->otpCode = $otpCode; return $this; }

    public function getOtpExpiresAt(): ?\DateTimeImmutable { return $this->otpExpiresAt; }
    public function setOtpExpiresAt(?\DateTimeImmutable $otpExpiresAt): static { $this->otpExpiresAt = $otpExpiresAt; return $this; }

    public function toProfileArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'age' => $this->age,
            'gender' => $this->gender,
            'aiDescription' => $this->aiDescription,
            'savedPhrases' => $this->getSavedPhrases(),
            'customContexts' => $this->getCustomContexts(),
        ];
    }
}
