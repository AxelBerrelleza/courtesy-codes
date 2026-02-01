<?php

namespace App\Tests\Api;

use App\Dto\PostRedeemDto;
use App\Factory\CodeFactory;
use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

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
}
