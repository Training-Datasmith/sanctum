<?php

declare(strict_types=1);

namespace Laravel\Sanctum;

use Laravel\Sanctum\Contracts\HasAbilities;

class TransientToken implements HasAbilities
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
