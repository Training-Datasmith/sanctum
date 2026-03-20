<?php

declare (strict_types=1);
namespace Laravel\Sanctum;

use Laravel\Sanctum\Contracts\Has_Abilities;
class Transient_Token implements Has_Abilities
{
    /**
     * Determine if the token has a given ability.
     *
     * @param  string  $ability
     */
    public function can($ability): bool
    {
        return true;
    }
    /**
     * Determine if the token is missing a given ability.
     *
     * @param  string  $ability
     */
    public function cant($ability): bool
    {
        return false;
    }
}