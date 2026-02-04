<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\NormalizerWithGroups;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'EXTRAS')]
final class ListUsersAction extends AbstractController
{
    #[Route('/users', methods: ['GET'], format: 'json')]
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
