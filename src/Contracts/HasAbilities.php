<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Contracts;

interface Has_Abilities
{
    /**
     * Determine if the token has a given ability.
     *
     * @param  string  $ability
     * @return bool
     */
    public function can($ability);
    /**
     * Determine if the token is missing a given ability.
     *
     * @param  string  $ability
     * @return bool
     */
    public function cant($ability);
}