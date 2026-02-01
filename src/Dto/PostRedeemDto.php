<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PostRedeemDto
{
    const ERROR_MSG_EMPTY = 'Debe proporcionar un user_id o los datos del invitado (guest_name).';

    #[Assert\Type('integer')]
    public ?int $userId = null;

    #[Assert\Type('string')]
    #[Assert\Length(max: 100)]
    #[Assert\NotBlank(allowNull: true)]
    public ?string $guestName = null;

    #[Assert\Email]
    #[Assert\NotBlank(allowNull: true)]
    public ?string $guestEmail = null;

    #[Assert\Choice(choices: ['press', 'sponsor', 'vip', 'staff', 'other'])]
    public ?string $guestType = null;

    /**
     * Validación lógica: Asegura que se envíe la información indicada en los requerimientos.
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Caso 1: No viene ni user_id ni datos de invitado
        if (null === $this->userId && null === $this->guestName)
            $context->buildViolation(self::ERROR_MSG_EMPTY)->addViolation();

        // Caso 2: Si viene guest_name, email y type deben ser obligatorios
        if (null !== $this->guestName && (null === $this->guestEmail || null === $this->guestType)) {
            $context->buildViolation('Si el canje es para un invitado, el email y el tipo son obligatorios.')
                ->atPath('guestEmail')
                ->addViolation();
        }
    }
}
