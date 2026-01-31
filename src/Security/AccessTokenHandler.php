<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(protected UserRepository $userRepository) {}

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $user = $this->userRepository->findOneBy(['accessToken' => $accessToken]);
        if (! $user)
            throw new BadCredentialsException('xd');

        return new UserBadge($user->getUserIdentifier());
    }
}
