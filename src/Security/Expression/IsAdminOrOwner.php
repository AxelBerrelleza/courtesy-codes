<?php

namespace App\Security\Expression;

use App\Enum\UserRoles;
use Symfony\Component\ExpressionLanguage\Expression;

class IsAdminOrOwner extends Expression
{
    public function __construct(bool $isCode = false)
    {
        $ownerPath = $isCode ? 'subject.getEvent().getPromoter()' : 'subject.getPromoter()';

        parent::__construct(sprintf(
            'is_granted("%s") or (is_granted("%s") and %s == user)',
            UserRoles::ADMIN,
            UserRoles::PROMOTER,
            $ownerPath
        ));
    }
}
