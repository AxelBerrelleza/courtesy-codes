<?php

namespace App\Controller;

use App\Dto\CodeDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Repository\CodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class CodeController extends AbstractController
{
    #[Route('/events/{event_id}/courtesy-codes', name: 'code_create', methods: ['POST'], format: 'json')]
    public function create(Event $event_id, #[MapRequestPayload()] CodeDto $codeDto, EntityManagerInterface $entityManager): Response
    {
        // For now, just returning a simple response.
        // We'll add the logic to handle the Code entity later.
        $code = new Code();
        $code->setUuid(Uuid::v4()->toString());
        $code->setQuantity($codeDto->quantity);
        $code->setType($codeDto->type);
        /** @todo validaciones: fecha mayor a NOW */
        if ($event_id->getDate() < $codeDto->expiresAt)
            throw new BadRequestException("La fecha de expiración debe ser menor a la fecha del evento {$event_id->getDate()->format('Y-m-d')}.");

        $code->setExpiresAt($codeDto->expiresAt ?? $event_id->getDate());
        $code->setZoneId($codeDto->zoneId);
        $code->setEvent($event_id);

        $entityManager->persist($code);
        $entityManager->flush();

        return $this->json(['message' => 'Code created'], Response::HTTP_CREATED);
    }
}
