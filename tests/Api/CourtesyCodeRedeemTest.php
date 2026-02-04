<?php

namespace App\Tests\Api;

use App\Dto\CodeDto;
use App\Dto\PostRedeemDto;
use App\Enum\CodeStatus;
use App\Enum\UserRoles;
use App\Factory\CodeFactory;
use App\Factory\EventFactory;
use App\Factory\UserFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CourtesyCodeRedeemTest extends BaseApiTestCase
{
    protected string $endpoint = '/courtesy-codes/%s/redeem';

    public function testAccessControl()
    {
        $client = static::createAuthenticatedClient();
        $courtesyCode = CodeFactory::randomOrCreate();
        $client->request('POST', sprintf($this->endpoint, $courtesyCode->getUuid()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testBodyValidation(): void
    {
        $client = static::createAuthenticatedClient(UserRoles::PROMOTER);
        $code = $this->createCode();
        $client->request('POST', sprintf($this->endpoint, $code->getUuid()), content: '{}');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('violations', $response);
        $this->assertArrayHasKey('detail', $response);
        $this->assertSame(PostRedeemDto::ERROR_MSG_EMPTY, $response['detail']);

        // When the request only contains userId
        $client->request(
            'POST',
            sprintf($this->endpoint, $code->getUuid()),
            content: json_encode(['userId' => 'invalid_value'])
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        // dump($response);
        $this->assertArrayHasKey('violations', $response);
        $this->assertArrayHasKey('detail', $response);
        $this->assertStringContainsString('userId', $response['detail']);

        // When request with not existing user
        $client->request(
            'POST',
            sprintf($this->endpoint, $code->getUuid()),
            content: json_encode(['userId' => 0])
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        // dump($response);
        $this->assertArrayHasKey('detail', $response);
        $this->assertStringContainsString('User', $response['detail']);

        // When the request contains guest data
        $client->request(
            'POST',
            sprintf($this->endpoint, $code->getUuid()),
            content: json_encode([
                'guestName' => '',
                'guestEmail' => '',
                'guestType' => '',
            ])
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('violations', $response);
        $this->assertArrayHasKey('detail', $response);
        // dump($response['detail']);
        $this->assertStringContainsString('guestName', $response['detail']);
        $this->assertStringContainsString('guestEmail', $response['detail']);
        $this->assertStringContainsString('guestType', $response['detail']);
    }

    protected function createCode(array $attributes = [])
    {
        $event = EventFactory::createOne([
            'promoter' => UserFactory::findBy(['accessToken' => self::PROMOTER_KEY])[0]
        ]);
        return CodeFactory::createOne([
            'event' => $event,
            ...$attributes
        ]);
    }

    public function testFailsWithInvalidCode(): void
    {
        $client = static::createAuthenticatedClient(UserRoles::PROMOTER);
        $courtesyCode = $this->createCode(['status' => CodeStatus::CANCELLED]);
        $redeemData = $this->buildValidRequestBody();
        $client->request(
            'POST',
            sprintf($this->endpoint, $courtesyCode->getUuid()),
            content: json_encode($redeemData),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        // dump($response['detail']);
        $this->arrayHasKey('detail', $response);
        $this->assertStringContainsString('The code is not available.', $response['detail']);
    }

    protected function buildValidRequestBody(): array
    {
        $redeemDto = new PostRedeemDto();
        $redeemDto->userId = UserFactory::random()->getId();
        $normalizer = $this->getContainer()->get(NormalizerInterface::class);
        return $normalizer->normalize($redeemDto, format: 'array');
    }

    public function testRedeemHappyPath(): void
    {
        $client = static::createAuthenticatedClient();
        $event = EventFactory::createOne([
            'promoter' => UserFactory::findBy(['accessToken' => self::PROMOTER_KEY])[0]
        ]);
        $creationResponse = CourtesyCodeCreationTest::createCode($client, $event);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $redeemData = $this->buildValidRequestBody();
        self::changeApiKey($client, UserRoles::PROMOTER);
        $client->request(
            'POST',
            sprintf($this->endpoint, $creationResponse['uuid']),
            content: json_encode($redeemData),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        // dump($response);
        $this->assertIsArray($response);
        $this->arrayHasKey(0, $response);
        $this->assertIsArray($response[0]);
        $this->arrayHasKey('ticket', $response[0]);
        $this->assertIsArray($response[0]['ticket']);
        $this->arrayHasKey('uuid', $response[0]['ticket']);
        $this->arrayHasKey('event', $response[0]['ticket']);

        // verify that code is not available anymore
        $client->request(
            'POST',
            sprintf($this->endpoint, $creationResponse['uuid']),
            content: json_encode($redeemData),
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->arrayHasKey('detail', $response);
        $this->assertStringContainsString('The code is not available.', $response['detail']);
    }
}
