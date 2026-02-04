<?php

namespace App\Controller;

use App\Entity\Code;
use App\Entity\Event;
use App\Security\Expression\IsAdminOrOwner;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Tag(name: 'Courtesy Codes')]
final class ListCodesAction extends AbstractController
{
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
    public function __invoke(Event $event_id, NormalizerInterface $normalizer): JsonResponse
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
}
