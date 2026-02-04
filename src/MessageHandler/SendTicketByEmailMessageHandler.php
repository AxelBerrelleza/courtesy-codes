<?php

namespace App\MessageHandler;

use App\Message\SendTicketByEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
/** @important esta clase es solo para hacer la simulación */
final class SendTicketByEmailMessageHandler
{
    public function __invoke(SendTicketByEmailMessage $message): void
    {
        // do something with your message
    }
}
