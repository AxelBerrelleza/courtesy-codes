<?php

namespace App\Exception\Cache;

class EventZoneIdIsBeingProcessByOtherUserException extends \Exception
{
    protected $message = 'The event zone is currently being processed by another user. Please try again later.';
}
