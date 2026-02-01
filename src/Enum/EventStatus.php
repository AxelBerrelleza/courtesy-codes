<?php

namespace App\Enum;

enum EventStatus: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
}
