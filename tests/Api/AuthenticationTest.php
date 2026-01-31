<?php

namespace App\Tests\Api;

use App\Tests\BaseApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationTest extends BaseApiTestCase
{
    public function testMainFirewall(): void
    {
        $endpoint = '/events/%d/courtesy-codes';
        $client = static::createClient();
        $not_existing_event_id = 0;
        $client->request('POST', sprintf($endpoint, $not_existing_event_id));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // avoids kernel booting errors
        self::$booted = false;
        $client = self::createAuthenticatedClient();
        $client->request('POST', sprintf($endpoint, $not_existing_event_id));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
