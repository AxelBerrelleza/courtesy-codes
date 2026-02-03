<?php

namespace App\Enum;

enum GuestType: string
{
  case PRESS = 'press';
  case SPONSOR = 'sponsor';
  case VIP = 'vip';
  case STAFF = 'staff';
  case OTHER = 'other';

  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }
}
