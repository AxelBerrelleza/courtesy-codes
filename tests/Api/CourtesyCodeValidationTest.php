<?php

namespace App\Tests\Api;

use App\Enum\UserRoles;
use App\Factory\CodeFactory;
use App\Factory\EventFactory;
use App\Factory\UserFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourtesyCodeValidationTest extends BaseApiTestCase
{
    protected string $endpoint = '/courtesy-codes/%s/validate';

    public function testAccessControl()
    {
        $client = static::createAuthenticatedClient();
        $event = EventFactory::createOne([
            'promoter' => UserFactory::findBy(['accessToken' => self::PROMOTER_KEY])[0]
        ]);
        $code = CourtesyCodeCreationTest::createCode($client, $event);
        $this->assertNotNull($code);
        self::changeApiKey($client, UserRoles::PROMOTER);
        $client->request('GET', \sprintf($this->endpoint, $code['uuid']));
        $this->assertResponseIsSuccessful();

        $differentCode = CodeFactory::createOne();
        $client->request('GET', \sprintf($this->endpoint, $differentCode->getUuid()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        self::changeApiKey($client, UserRoles::ADMIN);
        $client->request('GET', \sprintf($this->endpoint, $code['uuid']));
        $this->assertResponseIsSuccessful();
    }

    public function testHappyPath(): void
    {
        $client = static::createAuthenticatedClient(UserRoles::ADMIN);
        $code = CourtesyCodeCreationTest::createCode($client);
        $this->assertNotNull($code);
        $client->request('GET', sprintf($this->endpoint, $code['uuid']));
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
    }
}
