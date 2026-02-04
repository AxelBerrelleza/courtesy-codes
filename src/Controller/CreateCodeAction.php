<?php

namespace App\Controller;

use App\Dto\CodeCreationDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Enum\EventStatus;
use App\Enum\UserRoles;
use App\Exception\Cache\EventZoneIdIsBeingProcessByOtherUserException;
use App\Repository\EventRepository;
use App\Service\Code\CourtesyCodeCreator;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use App\Service\NormalizerWithGroups;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Courtesy Codes')]
final class CreateCodeAction extends AbstractController
{
    #[IsGranted(UserRoles::ADMIN)]
    #[Route(
        '/events/{event_id}/courtesy-codes',
        name: 'code_create',
        methods: ['POST'],
        format: 'json'
    )]
    #[OA\Response(
        response: 201,
        description: 'Returns the created code',
        content: new OA\JsonContent(
            ref: new Model(type: Code::class, groups: ['code:detail'])
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input, e.g., expiration date is after the event date'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden, only admins can create codes'
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error on the request body'
    )]
    public function __invoke(
        Event $event_id,
        #[MapRequestPayload] CodeCreationDto $codeDto,
        EntityManagerInterface $entityManager,
        NormalizerWithGroups $normalizer,
        CourtesyCodeCreator $courtesyCodeCreator,
        EventRepository $eventRepository,
    ): JsonResponse {
        if ($event_id->getStatus() !== EventStatus::ACTIVE)
            throw new BadRequestException('The event is not active.');

        /** @important simulando la revision de inventario con revision
         * a la key-value db para saber si el recurso ya esta en uso
         */
        try {
            $eventRepository->hasAvailabilityForZone($codeDto->zoneId, $codeDto->quantity);
        } catch (EventZoneIdIsBeingProcessByOtherUserException $ex) {
            throw new LockedHttpException($ex->getMessage());
        }
        try {
            $code = $courtesyCodeCreator->create($codeDto, $event_id);
        } catch (CourtesyCodeInvalidExpirationDateException $ex) {
            throw new BadRequestException($ex->getMessage());
        }

        $entityManager->persist($code);
        $entityManager->flush();

        return $this->json(
            $normalizer->normalize($code, groups: 'code:detail'),
            Response::HTTP_CREATED
        );
    }
}
