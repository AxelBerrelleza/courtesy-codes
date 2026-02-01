<?php

namespace App\Enum;

enum CodeStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case ALREADY_REDEEMED = 'already_redeemed';
}
