<?php

namespace App\Service\Code;

use App\Dto\CodeDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Enum\CodeStatus;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use Symfony\Component\Uid\Uuid;

class CourtesyCodeCreator
{
    /**
     * @throws CourtesyCodeInvalidExpirationDateException
     */
    public function create(CodeDto $codeDto, Event $event)
    {
        $code = new Code();
        $code->setUuid(Uuid::v4()->toString());
        $code->setQuantity($codeDto->quantity);
        $code->setType($codeDto->type);
        if ($event->getDate() < $codeDto->expiresAt)
            throw new CourtesyCodeInvalidExpirationDateException(
                "La fecha de expiración debe ser menor a la fecha del evento {$event->getDate()->format('Y-m-d')}."
            );

        $code->setExpiresAt($codeDto->expiresAt ?? $event->getDate());
        $code->setZoneId($codeDto->zoneId);
        $code->setEvent($event);
        $code->setStatus(CodeStatus::ACTIVE);
        $code->setCreatedAt(new \DateTimeImmutable());

        return $code;
    }
}
