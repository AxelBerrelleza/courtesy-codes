<?php

namespace App\Controller;

use App\Enum\UserRoles;
use App\Repository\UserRepository;
use App\Service\NormalizerWithGroups;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'EXTRAS')]
final class ListUsersAction extends AbstractController
{
    #[Route('/users', methods: ['GET'], format: 'json')]
    #[IsGranted(
        new Expression(
            'is_granted("' . UserRoles::ADMIN . '") or is_granted("' . UserRoles::PROMOTER . '")'
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Listado simple de usuarios (id, email)',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        )
    )]
    public function __invoke(
        UserRepository $userRepository,
        NormalizerWithGroups $normalizer,
    ): JsonResponse {
        return $this->json($normalizer->normalize(
            $userRepository->findAll(),
            groups: ['user:summary']
        ));
    }
}
