<?php

namespace App\Tests\Api;

use App\Dto\CodeDto;
use App\Enum\UserRoles;
use App\Factory\EventFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zenstruck\Foundry\Test\Factories;

/** @todo handle other scenarios */
class CourtesyCodeCreationTest extends BaseApiTestCase
{
    use Factories;

    protected string $endpoint = '/events/%d/courtesy-codes';

    public function testAccessControl()
    {
        $client = static::createAuthenticatedClient();
        $event = EventFactory::randomOrCreate();
        $client->request('POST', \sprintf($this->endpoint, $event->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testHappyPath(): void
    {
        $client = static::createAuthenticatedClient(UserRoles::ADMIN);
        $event = EventFactory::randomOrCreate();

        $codeArr = $this->buildValidRequestBody();
        $client->request(
            'POST',
            \sprintf($this->endpoint, $event->getId()),
            content: \json_encode($codeArr),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('redeemedCode', $response);
        $this->assertArrayNotHasKey('courtesyTickets', $response);
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

    protected function buildValidRequestBody(): array
    {
        $codeDto = new CodeDto();
        $codeDto->quantity = 10;
        $codeDto->type = 'VIP';
        $codeDto->zoneId = 'Main Stage';
        // preferable use the default assigned on testing
        // $codeDto->expiresAt = new \DateTimeImmutable('+1 day');
        $normalizer = $this->getContainer()->get(NormalizerInterface::class);
        return $normalizer->normalize($codeDto, format: 'array');
    }

    public function testInvalidDatesOnExpiresAt()
    {
        $client = static::createAuthenticatedClient(UserRoles::ADMIN);
        $event = EventFactory::randomOrCreate();
        $codeArr = $this->buildValidRequestBody();
        // verify that fails with a past date
        $codeArr['expiresAt'] = (new \DateTimeImmutable('yesterday'))->format('Y-m-d');
        $client->request(
            'POST',
            \sprintf($this->endpoint, $event->getId()),
            content: \json_encode($codeArr),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('violations', $response);
        $this->assertArrayHasKey('detail', $response);
        $this->assertStringContainsString(
            'expiresAt: This value should be greater than',
            $response['detail']
        );

        // verify that fails with a date grater than the events date
        $codeArr['expiresAt'] = $event
            ->getDate()->add(new \DateInterval('P1D'))->format('Y-m-d');
        $client->request(
            'POST',
            \sprintf($this->endpoint, $event->getId()),
            content: \json_encode($codeArr),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('detail', $response);
        // dump($response['detail']);
    }

    public static function createCode(KernelBrowser $client)
    {
        self::changeApiKey($client, UserRoles::ADMIN);
        $event = EventFactory::randomOrCreate();

        $codeDto = new CodeDto();
        $codeDto->quantity = 10;
        $codeDto->type = 'VIP';
        $codeDto->zoneId = 'Main Stage';
        $codeArr = get_object_vars($codeDto);
        $client->request(
            'POST',
            \sprintf('/events/%d/courtesy-codes', $event->getId()),
            content: \json_encode($codeArr),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $response;
    }
}
