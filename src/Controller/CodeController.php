<?php

namespace App\Controller;

use App\Dto\CodeDto;
use App\Dto\PostRedeemDto;
use App\Entity\CourtesyTicket;
use App\Entity\Code;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Repository\UserRepository;
use App\Security\Expression\IsAdminOrOwner;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use App\Service\Code\CourtesyCodeCreator;
use App\Service\Code\CourtesyCodeRedeemer;
use App\Service\NormalizerWithGroups;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[OA\Tag(name: 'Courtesy Codes')]
class CodeController extends AbstractController
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
    public function create(
        Event $event_id,
        #[MapRequestPayload()] CodeDto $codeDto,
        EntityManagerInterface $entityManager,
        NormalizerWithGroups $normalizer,
        CourtesyCodeCreator $courtesyCodeCreator,
    ): JsonResponse {
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

    #[Route('/courtesy-codes/{code}/validate', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'code', in: 'path', description: 'The UUID of the code to validate')]
    #[IsGranted(new IsAdminOrOwner(isCode: true), subject: 'code')]
    #[OA\Response(
        response: 200,
        description: 'Returns the code details if valid and active, or a reason if not.',
        content: new OA\JsonContent(
            oneOf: [
                new OA\Schema(ref: new Model(type: Code::class, groups: ['code:detail'])),
                new OA\Schema(properties: [
                    new OA\Property(property: 'valid', type: 'boolean', example: false),
                    new OA\Property(property: 'reason', type: 'string', example: 'code_expired.'),
                ], type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden, user is not the owner or an admin'
    )]
    #[OA\Response(
        response: 404,
        description: 'Code not found'
    )]
    public function validate(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        NormalizerWithGroups $normalizer,
    ): JsonResponse {
        if ($code->getStatus() === CodeStatus::ACTIVE)
            return $this->json(
                $normalizer->normalize($code, groups: 'code:detail')
            );
        elseif ($code->hasExpired())
            return $this->json([
                'valid' => false,
                'reason' => 'code_expired.',
            ]);
        else {
            return $this->json([
                'valid' => false,
                'reason' => $code->getStatus(),
            ]);
        }
    }

    #[IsGranted(UserRoles::PROMOTER)]
    #[OA\Parameter(name: 'code', in: 'path', description: 'The UUID of the code to redeem')]
    #[Route('/courtesy-codes/{code}/redeem', methods: ['POST'], format: 'json')]
    #[OA\Response(
        response: 200,
        description: 'Returns the generated courtesy tickets upon successful redemption',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: CourtesyTicket::class, groups: ['courtesy_ticket:list']))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request, e.g., code is not available for redemption'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden, only promoters can redeem'
    )]
    #[OA\Response(
        response: 404,
        description: 'Code or User not found'
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error on the request body'
    )]
    public function redeem(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        #[MapRequestPayload()] PostRedeemDto $redeemDto,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?User $currentUser,
        CourtesyCodeRedeemer $courtesyCodeRedeemer,
        NormalizerInterface $normalizer,
    ): JsonResponse {
        $userOwner = $userRepository->findById($redeemDto->userId);
        if (! $userOwner && ! $redeemDto->guestName)
            throw new NotFoundHttpException('User not found');

        $entityManager->beginTransaction();
        $entityManager->lock($code, LockMode::PESSIMISTIC_WRITE);
        $entityManager->refresh($code);

        if ($code->getStatus() !== CodeStatus::ACTIVE) {
            $entityManager->rollback();
            throw new BadRequestException("The code is not available.");
        }

        $courtesyCodeRedeemer->redeemAvailableCode($code, $redeemDto, $currentUser);
        $entityManager->flush();
        $entityManager->commit();

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('courtesy_ticket:list')
            ->toArray();
        return $this->json($normalizer->normalize(
            $code->getCourtesyTickets(),
            format: 'array',
            context: $context
        ));
    }

    #[Route('/events/{event_id}/courtesy-codes', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'event_id', in: 'path', description: 'The ID of the event')]
    #[IsGranted(new IsAdminOrOwner(isCode: false), subject: 'event_id')]
    #[OA\Response(
        response: 200,
        description: 'Returns the list of codes for an event',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Code::class, groups: ['code:detail']))
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden, user is not the event owner or an admin'
    )]
    #[OA\Response(
        response: 404,
        description: 'Event not found'
    )]
    public function list(Event $event_id, NormalizerInterface $normalizer): JsonResponse
    {
        /** @todo implement pagination + summary key as in specs */
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('code:detail')
            ->toArray();
        return $this->json($normalizer->normalize(
            $event_id->getCodes(),
            format: 'array',
            context: $context
        ));
    }

    #[Route('/courtesy-codes/{code}', methods: ['DELETE'], format: 'json')]
    #[OA\Parameter(name: 'code', in: 'path', description: 'The UUID of the code to cancel')]
    #[IsGranted(new IsAdminOrOwner(isCode: true), subject: 'code')]
    #[OA\Response(
        response: 200,
        description: 'Returns a success message with the code status',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'code', type: 'string', format: 'uuid'),
                new OA\Property(property: 'status', type: 'string', example: 'cancelled'),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request, e.g., code has already been redeemed'
    )]
    #[OA\Response(
        response: 403,
        description: 'Forbidden, user is not the owner or an admin'
    )]
    #[OA\Response(
        response: 404,
        description: 'Code not found'
    )]
    public function cancel(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if ($code->getStatus() === CodeStatus::ALREADY_REDEEMED)
            throw new BadRequestException('The code is already redeemed.');
        elseif ($code->getStatus() === CodeStatus::ACTIVE) {
            $code->setStatus(CodeStatus::CANCELLED);
            $entityManager->persist($code);
            $entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'code' => $code->getUuid(),
            'status' => $code->getStatus(),
        ]);
    }
}
