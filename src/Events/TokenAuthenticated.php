<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Events;

class Token_Authenticated
{
    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Sanctum\PersonalAccessToken  $token  The personal access token that was authenticated.
     */
    public function __construct(public $token)
    {
    }
}