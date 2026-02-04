<?php

namespace App\Controller;

use App\Dto\CodeDto;
use App\Dto\PostRedeemDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Repository\UserRepository;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use App\Service\Code\CourtesyCodeCreator;
use App\Service\Code\CourtesyCodeRedeemer;
use App\Service\NormalizerWithGroups;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
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

class CodeController extends AbstractController
{
    #[IsGranted(UserRoles::ADMIN)]
    #[Route(
        '/events/{event_id}/courtesy-codes',
        name: 'code_create',
        methods: ['POST'],
        format: 'json'
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
    #[IsGranted(new Expression(
        'is_granted("' . UserRoles::ADMIN . '") or (is_granted("' . UserRoles::PROMOTER . '") and subject.getEvent().getPromoter() == user)'
    ), subject: 'code')]
    public function validate(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        NormalizerWithGroups $normalizer,
    ): JsonResponse {
        if ($code->getStatus() === CodeStatus::ACTIVE)
            return $this->json(
                $normalizer->normalize($code, groups: 'code:detail')
            );
        elseif ($code->getExpiresAt() < new \DateTimeImmutable())
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
    #[Route('/courtesy-codes/{code}/redeem', methods: ['POST'], format: 'json')]
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
    #[IsGranted(new Expression(
        'is_granted("' . UserRoles::ADMIN . '") or (is_granted("' . UserRoles::PROMOTER . '") and subject.getPromoter() == user)'
    ), subject: 'event_id')]
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
}
