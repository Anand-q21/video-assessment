<?php

namespace App\Entity;

use App\Repository\HashtagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HashtagRepository::class)]
#[ORM\Index(columns: ['name'], name: 'idx_hashtag_name')]
#[ORM\Index(columns: ['usage_count'], name: 'idx_hashtag_usage')]
class Hashtag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private int $usageCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    #[ORM\ManyToMany(targetEntity: Video::class, mappedBy: 'hashtags')]
    private Collection $videos;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
        $this->createdAt = new \DateTime();
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
        $this->name = strtolower(trim($name, '#'));
        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount): static
    {
        $this->usageCount = $usageCount;
        return $this;
    }

    public function incrementUsage(): static
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();
        return $this;
    }

    public function decrementUsage(): static
    {
        $this->usageCount = max(0, $this->usageCount - 1);
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeInterface $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(Video $video): static
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
            $video->addHashtag($this);
        }
        return $this;
    }

    public function removeVideo(Video $video): static
    {
        if ($this->videos->removeElement($video)) {
            $video->removeHashtag($this);
        }
        return $this;
    }
}