<?php

namespace App\Tests\Api;

use App\Enum\UserRoles;
use App\Factory\CodeFactory;
use App\Factory\EventFactory;
use App\Factory\RedeemedCodeFactory;
use App\Factory\UserFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourtesyCodeListTest extends BaseApiTestCase
{
    protected string $endpoint = '/events/%d/courtesy-codes';

    public function testAccessControl()
    {
        $client = static::createAuthenticatedClient();
        self::changeApiKey($client, UserRoles::PROMOTER);
        $event = EventFactory::createOne([
            'promoter' => UserFactory::findBy(['accessToken' => self::PROMOTER_KEY])[0]
        ]);
        $client->request('GET', \sprintf($this->endpoint, $event->getId()));
        $this->assertResponseIsSuccessful();
        // fails with a different promoter
        $event = EventFactory::createOne([
            'promoter' => null
        ]);
        $client->request('GET', \sprintf($this->endpoint, $event->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        self::changeApiKey($client, UserRoles::ADMIN);
        $client->request('GET', \sprintf($this->endpoint, $event->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testListingToAdmin(): void
    {
        $client = static::createAuthenticatedClient();
        $code = CourtesyCodeCreationTest::createCode($client);
        $this->assertNotNull($code);
        self::changeApiKey($client, UserRoles::ADMIN);
        $client->request('GET', \sprintf($this->endpoint, $code['event']['id']));
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
        $this->assertIsArray($response);
        $this->assertArrayHasKey(0, $response);
        $this->assertIsArray($response[0]);
        $this->assertArrayHasKey('uuid', $response[0]);
        $this->assertArrayHasKey('event', $response[0]);
    }

    public function testListingToPromoter(): void
    {
        $client = static::createAuthenticatedClient(UserRoles::PROMOTER);
        $promoter = UserFactory::findBy(['accessToken' => self::PROMOTER_KEY])[0];
        $event = EventFactory::createOne([
            'promoter' => $promoter
        ]);
        $code = CodeFactory::createOne([
            'event' => $event
        ]);
        $client->request('GET', \sprintf($this->endpoint, $event->getId()));
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
        $this->assertIsArray($response);
        $this->assertEmpty($response);

        RedeemedCodeFactory::createOne([
            'code' => $code,
            'redeemedBy' => $promoter,
        ]);

        $this->assertNotNull($code);
        $client->request('GET', \sprintf($this->endpoint, $event->getId()));
        $response = json_decode($client->getResponse()->getContent(), true);
        // dump($response);
        $this->assertResponseIsSuccessful();
        $this->assertIsArray($response);
        $this->assertArrayHasKey(0, $response);
        $this->assertIsArray($response[0]);
        $this->assertArrayHasKey('uuid', $response[0]);
        $this->assertArrayHasKey('event', $response[0]);
    }
}
