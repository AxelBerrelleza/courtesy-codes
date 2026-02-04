<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('sync')]
/** @important esta clase es solo para hacer la simulación */
final class SendTicketByEmailMessage
{
    public function __construct(
        public readonly string $email,
    ) {}
}
