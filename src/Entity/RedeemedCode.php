<?php

namespace App\Entity;

use App\Enum\GuestType;
use App\Repository\RedeemedCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RedeemedCodeRepository::class)]
class RedeemedCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'redeemedCode', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Code $code = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $redeemedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $redeemedBy = null;

    #[ORM\ManyToOne]
    private ?User $userOwner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $guestName = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $guestEmail = null;

    #[ORM\Column(nullable: true, enumType: GuestType::class)]
    private ?GuestType $guestType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?Code
    {
        return $this->code;
    }

    public function setCode(Code $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getRedeemedAt(): ?\DateTimeImmutable
    {
        return $this->redeemedAt;
    }

    public function setRedeemedAt(\DateTimeImmutable $redeemedAt): static
    {
        $this->redeemedAt = $redeemedAt;

        return $this;
    }

    public function getRedeemedBy(): ?User
    {
        return $this->redeemedBy;
    }

    public function setRedeemedBy(?User $redeemedBy): static
    {
        $this->redeemedBy = $redeemedBy;

        return $this;
    }

    public function getUserOwner(): ?User
    {
        return $this->userOwner;
    }

    public function setUserOwner(?User $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    public function getGuestName(): ?string
    {
        return $this->guestName;
    }

    public function setGuestName(?string $guestName): static
    {
        $this->guestName = $guestName;

        return $this;
    }

    public function getGuestEmail(): ?string
    {
        return $this->guestEmail;
    }

    public function setGuestEmail(?string $guestEmail): static
    {
        $this->guestEmail = $guestEmail;

        return $this;
    }

    public function getGuestType(): ?GuestType
    {
        return $this->guestType;
    }

    public function setGuestType(?GuestType $guestType): static
    {
        $this->guestType = $guestType;

        return $this;
    }
}
