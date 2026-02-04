<?php

namespace App\Controller;

use App\Entity\Event;
use App\Enum\UserRoles;
use App\Repository\EventRepository;
use App\Service\NormalizerWithGroups;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'EXTRAS')]
final class ListEventsAction extends AbstractController
{
    #[Route('/events', methods: ['GET'], format: 'json')]
    #[IsGranted(
        new Expression(
            'is_granted("' . UserRoles::ADMIN . '") or is_granted("' . UserRoles::PROMOTER . '")'
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Listado de eventos',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        )
    )]
    public function __invoke(
        EventRepository $eventRepository,
        NormalizerWithGroups $normalizer,
    ): JsonResponse {
        return $this->json($normalizer->normalize(
            $eventRepository->findAll(),
            groups: ['event:summary', 'user:summary']
        ));
    }
}
