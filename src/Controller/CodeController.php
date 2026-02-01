<?php

namespace App\Controller;

use App\Dto\CodeDto;
use App\Dto\PostRedeemDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Repository\CodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Uuid;

class CodeController extends AbstractController
{
    #[Route(
        '/events/{event_id}/courtesy-codes',
        name: 'code_create',
        methods: ['POST'],
        format: 'json'
    )]
    public function create(
        Event $event_id,
        #[MapRequestPayload()] CodeDto $codeDto,
        EntityManagerInterface $entityManager,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $code = new Code();
        $code->setUuid(Uuid::v4()->toString());
        $code->setQuantity($codeDto->quantity);
        $code->setType($codeDto->type);
        /** @todo validaciones: fecha mayor a NOW */
        if ($event_id->getDate() < $codeDto->expiresAt)
            throw new BadRequestException(
                "La fecha de expiración debe ser menor a la fecha del evento {$event_id->getDate()->format('Y-m-d')}."
            );

        $code->setExpiresAt($codeDto->expiresAt ?? $event_id->getDate());
        $code->setZoneId($codeDto->zoneId);
        $code->setEvent($event_id);
        $code->setStatus(CodeStatus::ACTIVE);
        $code->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($code);
        $entityManager->flush();

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('code:detail')
            ->toArray();

        return $this->json(
            $normalizer->normalize($code, format: 'array', context: $context),
            Response::HTTP_CREATED
        );
    }

    #[IsGranted(UserRoles::PROMOTER)]
    #[Route('/courtesy-codes/{code}/redeem', methods: ['POST'], format: 'json')]
    public function redeem(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        #[MapRequestPayload()] PostRedeemDto $redeemDto,
    ): JsonResponse {
        if ($code->getStatus() <> CodeStatus::ACTIVE)
            throw new BadRequestException("The code is not available.");


        return $this->json(['todo']);
    }
}
