<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class ApiKeyExtractor implements AccessTokenExtractorInterface
{
    public function extractAccessToken(Request $request): ?string
    {
        // Retorna el valor del header 'X-API-TOKEN' o null si no existe
        return $request->headers->get('X-API-Key');
    }
}
