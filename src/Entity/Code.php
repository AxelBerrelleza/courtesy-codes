<?php

namespace App\Entity;

use App\Enum\CodeStatus;
use App\Repository\CodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CodeRepository::class)]
class Code
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('code:detail')]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    #[Groups('code:detail')]
    private ?string $uuid = null;

    #[ORM\Column]
    #[Groups('code:detail')]
    private ?int $quantity = null;

    #[ORM\Column(length: 32)]
    #[Groups('code:detail')]
    private ?string $type = null;

    #[ORM\Column]
    #[Groups('code:detail')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups('code:detail')]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 255)]
    #[Groups('code:detail')]
    private ?string $zoneId = null;

    #[ORM\ManyToOne(inversedBy: 'codes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('code:detail')]
    private ?Event $event = null;

    #[ORM\Column(enumType: CodeStatus::class)]
    #[Groups('code:detail')]
    private ?CodeStatus $status = null;

    #[ORM\OneToOne(mappedBy: 'code', cascade: ['persist', 'remove'])]
    private ?RedeemedCode $redeemedCode = null;

    /**
     * @var Collection<int, CourtesyTicket>
     */
    #[ORM\OneToMany(
        targetEntity: CourtesyTicket::class,
        mappedBy: 'code',
        cascade: ['persist', 'remove']
    )]
    private Collection $courtesyTickets;

    public function __construct()
    {
        $this->courtesyTickets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getZoneId(): ?string
    {
        return $this->zoneId;
    }

    public function setZoneId(string $zoneId): static
    {
        $this->zoneId = $zoneId;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getStatus(): ?CodeStatus
    {
        return $this->status;
    }

    public function setStatus(CodeStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRedeemedCode(): ?RedeemedCode
    {
        return $this->redeemedCode;
    }

    public function setRedeemedCode(RedeemedCode $redeemedCode): static
    {
        // set the owning side of the relation if necessary
        if ($redeemedCode->getCode() !== $this) {
            $redeemedCode->setCode($this);
        }

        $this->redeemedCode = $redeemedCode;

        return $this;
    }

    /**
     * @return Collection<int, CourtesyTicket>
     */
    public function getCourtesyTickets(): Collection
    {
        return $this->courtesyTickets;
    }

    public function addCourtesyTicket(CourtesyTicket $courtesyTicket): static
    {
        if (!$this->courtesyTickets->contains($courtesyTicket)) {
            $this->courtesyTickets->add($courtesyTicket);
            $courtesyTicket->setCode($this);
        }

        return $this;
    }

    public function removeCourtesyTicket(CourtesyTicket $courtesyTicket): static
    {
        if ($this->courtesyTickets->removeElement($courtesyTicket)) {
            // set the owning side to null (unless already changed)
            if ($courtesyTicket->getCode() === $this) {
                $courtesyTicket->setCode(null);
            }
        }

        return $this;
    }
}
