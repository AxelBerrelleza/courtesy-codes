<?php

namespace App\Controller;

use App\Dto\CodeDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Security\Expression\IsAdminOrOwner;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use App\Service\Code\CourtesyCodeCreator;
use App\Service\NormalizerWithGroups;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
}
