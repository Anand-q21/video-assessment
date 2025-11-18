<?php

namespace App\Entity;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\UserPasswordProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/users/register', 
            validationContext: ['groups' => ['Default', 'user:create']],
            processor: UserPasswordProcessor::class
        )
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:create']],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Email(groups: ['user:create'])]
    #[Groups(['user:create', 'user:read'])]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = ['ROLE_USER'];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(groups: ['user:create'])]
    #[Assert\Length(min: 8, groups: ['user:create'])]
    #[Groups(['user:create'])]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Assert\Length(min: 3, max: 50, groups: ['user:create'])]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_]+$/', message: 'Username can only contain letters, numbers and underscores', groups: ['user:create'])]
    #[Groups(['user:read', 'user:create'])]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
