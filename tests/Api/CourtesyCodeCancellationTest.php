<?php

namespace App\Tests\Api;

use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Factory\CodeFactory;
use App\Factory\EventFactory;
use App\Factory\UserFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourtesyCodeCancellationTest extends BaseApiTestCase
{
    protected string $endpoint = '/courtesy-codes/%s';

    public function testAccessControl()
    {
        $client = static::createAuthenticatedClient();
        $event = EventFactory::createOne([
            'promoter' => UserFactory::findBy(['accessToken' => self::PROMOTER_KEY])[0]
        ]);
        $code = CodeFactory::createOne([
            'event' => $event
        ]);
        $this->assertNotNull($code);
        self::changeApiKey($client, UserRoles::PROMOTER);
        $client->request('DELETE', \sprintf($this->endpoint, $code->getUuid()));
        $this->assertResponseIsSuccessful();

        $differentCode = CodeFactory::createOne();
        $client->request('DELETE', \sprintf($this->endpoint, $differentCode->getUuid()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        self::changeApiKey($client, UserRoles::ADMIN);
        $client->request('DELETE', \sprintf($this->endpoint, $code->getUuid()));
        $this->assertResponseIsSuccessful();
    }

    public function testCancels(): void
    {
        $client = static::createAuthenticatedClient(UserRoles::ADMIN);
        $code = CodeFactory::createOne();
        $client->request('DELETE', sprintf($this->endpoint, $code->getUuid()));
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertEquals(true, $response['success']);
        $this->assertArrayHasKey('code', $response);
        $this->assertEquals($code->getUuid(), $response['code']);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals(CodeStatus::CANCELLED->value, $response['status']);
    }
}
