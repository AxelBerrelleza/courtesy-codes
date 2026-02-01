<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseApiTestCase extends WebTestCase
{
    public static function createAuthenticatedClient()
    {
        return static::createClient(server: [
            'HTTP_X-API-Key' => 'sk_test_admin_000000',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }
}
