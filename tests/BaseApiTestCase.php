<?php

namespace App\Tests;

use App\Enum\UserRoles;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseApiTestCase extends WebTestCase
{
    const ADMIN_KEY = 'sk_test_admin_000000';
    const PROMOTER_KEY = 'sk_test_promoter_abc123';
    const BASE_USER_KEY = 'sk_test_user_12345';

    public static function createAuthenticatedClient(string $role = UserRoles::USER, array $options = [], array $server = [])
    {
        return static::createClient(
            options: $options,
            server: [
                'HTTP_X-API-Key' => match ($role) {
                    UserRoles::ADMIN => self::ADMIN_KEY,
                    UserRoles::PROMOTER => self::PROMOTER_KEY,
                    UserRoles::USER => self::BASE_USER_KEY,
                    default => null
                },
                'CONTENT_TYPE' => 'application/json',
                ...$server,
            ]
        );
    }

    public static function changeApiKey(KernelBrowser $client, string $role = UserRoles::USER)
    {
        $client->setServerParameter('HTTP_X-API-Key', match ($role) {
            UserRoles::ADMIN => self::ADMIN_KEY,
            UserRoles::PROMOTER => self::PROMOTER_KEY,
            UserRoles::USER => self::BASE_USER_KEY,
            default => null
        });
    }
}
