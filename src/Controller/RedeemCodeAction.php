<?php

namespace App\Controller;

use App\Dto\PostRedeemDto;
use App\Entity\Code;
use App\Entity\CourtesyTicket;
use App\Entity\User;
use App\Enum\CodeStatus;
use App\Enum\EventStatus;
use App\Enum\UserRoles;
use App\Message\SendTicketByEmailMessage;
use App\Repository\UserRepository;
use App\Service\Code\CourtesyCodeRedeemer;
use App\Service\NormalizerWithGroups;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Courtesy Codes')]
final class RedeemCodeAction extends AbstractController
{
    #[IsGranted(
        new Expression(
            'is_granted("' . UserRoles::PROMOTER . '") and subject.getEvent().getPromoter() == user'
        ),
        subject: 'code'
    )]
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
    public function __invoke(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        #[MapRequestPayload()] PostRedeemDto $redeemDto,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?User $currentUser,
        CourtesyCodeRedeemer $courtesyCodeRedeemer,
        NormalizerWithGroups $normalizer,
        MessageBusInterface $messageBus,
    ): JsonResponse {
        $userOwner = $userRepository->find($redeemDto->userId);
        if (! $userOwner && ! $redeemDto->guestName)
            throw new NotFoundHttpException('User not found');
        if ($code->getEvent()->getStatus() !== EventStatus::ACTIVE)
            throw new BadRequestException('The event is not active.');

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
        /** @important para simular colas */
        $messageBus->dispatch(new SendTicketByEmailMessage(
            $userOwner ? $userOwner->getEmail() : $redeemDto->guestEmail
        ));

        return $this->json($normalizer->normalize(
            $code->getCourtesyTickets(),
            groups: 'courtesy_ticket:list'
        ));
    }
}
