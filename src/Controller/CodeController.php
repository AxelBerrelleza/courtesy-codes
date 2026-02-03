<?php

namespace App\Controller;

use App\Dto\CodeDto;
use App\Dto\PostRedeemDto;
use App\Entity\Code;
use App\Entity\Event;
use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Repository\UserRepository;
use App\Service\Code\CourtesyCodeInvalidExpirationDateException;
use App\Service\Code\CourtesyCodeCreator;
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
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CodeController extends AbstractController
{
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
        NormalizerInterface $normalizer,
        CourtesyCodeCreator $courtesyCodeCreator,
    ): JsonResponse {
        try {
            $code = $courtesyCodeCreator->create($codeDto, $event_id);
        } catch (CourtesyCodeInvalidExpirationDateException $ex) {
            throw new BadRequestException($ex->getMessage());
        }

        $entityManager->persist($code);
        $entityManager->flush();

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('code:detail')
            ->toArray();

        return $this->json(
            $normalizer->normalize($code, format: 'array', context: $context),
            Response::HTTP_CREATED
        );
    }

    #[IsGranted(UserRoles::PROMOTER)]
    #[Route('/courtesy-codes/{code}/redeem', methods: ['POST'], format: 'json')]
    public function redeem(
        #[MapEntity(mapping: ['code' => 'uuid'])] Code $code,
        #[MapRequestPayload()] PostRedeemDto $redeemDto,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
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

        $code->setStatus(CodeStatus::ALREADY_REDEEMED);
        $entityManager->flush();
        $entityManager->commit();

        return $this->json($normalizer->normalize(
            $code->getCourtesyTickets(),
            format: 'array'
        ));
    }
}
