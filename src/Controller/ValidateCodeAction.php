<?php

namespace App\Controller;

use App\Entity\Code;
use App\Enum\CodeStatus;
use App\Security\Expression\IsAdminOrOwner;
use App\Service\NormalizerWithGroups;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Courtesy Codes')]
final class ValidateCodeAction extends AbstractController
{
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
    #[OA\Response(response: 403, description: 'Forbidden, user is not the owner or an admin')]
    #[OA\Response(response: 404, description: 'Code not found')]
    public function __invoke(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        NormalizerWithGroups $normalizer,
    ): JsonResponse {
        if ($code->getStatus() === CodeStatus::ACTIVE) {
            return $this->json(
                $normalizer->normalize($code, groups: 'code:detail')
            );
        }

        if ($code->hasExpired()) {
            return $this->json([
                'valid' => false,
                'reason' => 'code_expired.',
            ]);
        }

        return $this->json([
            'valid' => false,
            'reason' => $code->getStatus(),
        ]);
    }
}
