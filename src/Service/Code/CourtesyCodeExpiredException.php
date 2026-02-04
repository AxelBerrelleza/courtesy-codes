<?php

namespace App\Service\Code;

class CourtesyCodeExpiredException extends \Exception
{
    protected $message = 'The code has expired';
}
