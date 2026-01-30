<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CodeDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?int $quantity = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public ?string $type = null;

    public ?\DateTimeImmutable $expiresAt = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $zoneId = null;
}
