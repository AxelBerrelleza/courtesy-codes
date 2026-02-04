<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CodeCreationDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?int $quantity = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public ?string $type = null;

    #[Assert\GreaterThan(value: new \DateTimeImmutable())]
    public ?\DateTimeImmutable $expiresAt = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $zoneId = null;
}
