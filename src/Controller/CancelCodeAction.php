<?php

namespace App\Controller;

use App\Entity\Code;
use App\Enum\CodeStatus;
use App\Security\Expression\IsAdminOrOwner;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Courtesy Codes')]
final class CancelCodeAction extends AbstractController
{
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
    public function __invoke(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if ($code->getStatus() === CodeStatus::ALREADY_REDEEMED) {
            throw new BadRequestException('The code is already redeemed.');
        } elseif ($code->getStatus() === CodeStatus::ACTIVE) {
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
