<?php

namespace App\Tests\Api;

use App\Dto\PostRedeemDto;
use App\Enum\CodeStatus;
use App\Factory\CodeFactory;
use App\Factory\UserFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CourtesyCodeRedeemTest extends BaseApiTestCase
{
    protected string $endpoint = '/courtesy-codes/%s/redeem';

    public function testBodyValidation(): void
    {
        $client = static::createAuthenticatedClient();
        $code = CodeFactory::createOne();
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

    public function testFailsWithInvalidCode(): void
    {
        $client = static::createAuthenticatedClient();
        $courtesyCode = CodeFactory::createOne(['status' => CodeStatus::CANCELLED]);
        $redeemDto = new PostRedeemDto();
        $redeemDto->userId = UserFactory::random()->getId();
        $normalizer = $this->getContainer()->get(NormalizerInterface::class);
        $redeemData = $normalizer->normalize($redeemDto, format: 'array');
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
}
