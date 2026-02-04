<?php

namespace App\Service\Code;

use App\Dto\PostRedeemDto;
use App\Entity\Code;
use App\Entity\RedeemedCode;
use App\Entity\User;
use App\Enum\CodeStatus;
use App\Enum\GuestType;

class CourtesyCodeRedeemer
{
    public function redeemAvailableCode(
        Code $code,
        PostRedeemDto $redeemDto,
        User $redeemedBy,
        ?User $userOwner = null,
    ) {
        /** @todo verify code expiration */
        $code->setStatus(CodeStatus::ALREADY_REDEEMED);
        $redeemedCode = new RedeemedCode();
        $redeemedCode->setCode($code);
        $redeemedCode->setRedeemedAt(new \DateTimeImmutable());
        $redeemedCode->setRedeemedBy($redeemedBy);
        $redeemedCode->setUserOwner($userOwner);
        $redeemedCode->setGuestName($redeemDto->guestName);
        $redeemedCode->setGuestEmail($redeemDto->guestEmail);
        $redeemedCode->setGuestType(GuestType::tryFrom(
            $redeemDto->guestType ?? '' // to avoid a deprecation msg
        ));

        $code->setRedeemedCode($redeemedCode);
    }
}
