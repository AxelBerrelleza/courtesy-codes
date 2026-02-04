<?php

namespace App\Service\Code;

use App\Dto\CodeCreationDto;
use App\Entity\Code;
use App\Entity\CourtesyTicket;
use App\Entity\Event;
use App\Entity\Ticket;
use App\Enum\CodeStatus;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use Symfony\Component\Uid\Uuid;

class CourtesyCodeCreator
{
    /**
     * @throws CourtesyCodeInvalidExpirationDateException
     */
    public function create(CodeCreationDto $codeDto, Event $event)
    {
        $code = new Code();
        $code->setUuid(Uuid::v4()->toString());
        $code->setQuantity($codeDto->quantity);
        $code->setType($codeDto->type);
        if ($event->getDate() < $codeDto->expiresAt)
            throw new CourtesyCodeInvalidExpirationDateException(
                "La fecha de expiración debe ser menor a la fecha del evento {$event->getDate()->format('Y-m-d')}."
            );

        $code->setExpiresAt(
            $codeDto->expiresAt
                ?? \DateTimeImmutable::createFromMutable($event->getDate())
        );
        $code->setZoneId($codeDto->zoneId);
        $code->setEvent($event);
        $code->setStatus(CodeStatus::ACTIVE);
        $code->setCreatedAt(new \DateTimeImmutable());
        for ($iter = 0; $iter < $code->getQuantity(); $iter++) {
            $courtesyTicket = new CourtesyTicket();
            /** @important: this portion emulates a ticket assignment */
            $ticket = new Ticket();
            $ticket->setEvent($event);

            $courtesyTicket->setTicket($ticket);
            $code->addCourtesyTicket($courtesyTicket);
        }

        return $code;
    }
}
