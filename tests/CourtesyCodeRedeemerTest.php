<?php

namespace App\Tests;

use App\Dto\PostRedeemDto;
use App\Enum\GuestType;
use App\Factory\CodeFactory;
use App\Factory\UserFactory;
use App\Service\Code\CourtesyCodeExpiredException;
use App\Service\Code\CourtesyCodeRedeemer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\faker;

class CourtesyCodeRedeemerTest extends KernelTestCase
{
    use Factories;

    public function testRedeemAvailableCode(): void
    {
        $code = CodeFactory::createOne();
        $this->assertNull($code->getRedeemedCode());
        $redeemDto = new PostRedeemDto();
        $redeemDto->guestName = faker()->name();
        $redeemDto->guestEmail = faker()->email();
        $redeemDto->guestType = faker()->shuffleArray(GuestType::values())[0];
        $courtesyCodeRedeemer = $this->getContainer()->get(CourtesyCodeRedeemer::class);
        $courtesyCodeRedeemer->redeemAvailableCode($code, $redeemDto, UserFactory::random());
        $this->assertNotNull($code->getRedeemedCode());
    }

    public function testFailsWithExpiredCode()
    {
        $code = CodeFactory::createOne(['expiresAt' => new \DateTimeImmutable('yesterday')]);
        $redeemDto = new PostRedeemDto();
        $courtesyCodeRedeemer = $this->getContainer()->get(CourtesyCodeRedeemer::class);
        $this->expectException(CourtesyCodeExpiredException::class);
        $courtesyCodeRedeemer->redeemAvailableCode($code, $redeemDto, UserFactory::random());
    }
}
