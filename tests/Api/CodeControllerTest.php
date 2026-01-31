<?php

namespace App\Tests\Api;

use App\Dto\CodeDto;
use App\Factory\EventFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zenstruck\Foundry\Test\Factories;

/** @todo handle other scenarios */
class CodeControllerTest extends BaseApiTestCase
{
    use Factories;

    public function testHappyPath(): void
    {
        $client = static::createAuthenticatedClient();
        $endpoint = '/events/%d/courtesy-codes';
        $event = EventFactory::randomOrCreate();

        $codeDto = new CodeDto();
        $codeDto->quantity = 10;
        $codeDto->type = 'VIP';
        $codeDto->zoneId = 'Main Stage';
        $codeDto->expiresAt = new \DateTimeImmutable('+1 day');
        $normalizer = $this->getContainer()->get(NormalizerInterface::class);
        $codeArr = $normalizer->normalize($codeDto, format: 'array');
        $client->request(
            'POST',
            \sprintf($endpoint, $event->getId()),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: \json_encode($codeArr),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
        $keysToVerify = [
            'id',
            'uuid',
            'quantity',
            'type',
            'zoneId',
            'expiresAt',
            'createdAt',
            'event',
        ];
        foreach ($keysToVerify as $key)
            $this->assertArrayHasKey($key, $response);
    }
}
