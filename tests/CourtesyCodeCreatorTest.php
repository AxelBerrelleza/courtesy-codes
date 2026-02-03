<?php

namespace App\Tests;

use App\Dto\CodeDto;
use App\Factory\EventFactory;
use App\Service\Code\CourtesyCodeCreator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CourtesyCodeCreatorTest extends KernelTestCase
{
    public function testCodeCreation(): void
    {
        $courtesyCodeCreator = $this->getContainer()->get(CourtesyCodeCreator::class);
        $event = EventFactory::random();
        $codeDto = new CodeDto();
        $codeDto->quantity = $quantity = 10;
        $codeDto->type = 'VIP';
        $codeDto->zoneId = 'Main Stage';
        $code = $courtesyCodeCreator->create($codeDto, $event);
        $this->assertCount($quantity, $code->getCourtesyTickets());
        $firstTicket = $code->getCourtesyTickets()->first()->getTicket();
        $this->assertNotNull($firstTicket);
        $this->assertEquals($event->getId(), $firstTicket->getEvent()->getId());

        // to verify that the code can actually be persisted
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($code);
        $entityManager->flush();
    }
}
