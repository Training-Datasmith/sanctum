<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Contracts;

use DateTimeInterface;
interface Has_Api_Tokens
{
    /**
     * Get the access tokens that belong to model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tokens();
    /**
     * Determine if the current API token has a given scope.
     *
     * @return bool
     */
    public function token_can(string $ability);
    /**
     * Create a new personal access token for the user.
     *
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function create_token(string $name, array $abilities = ['*'], ?DateTimeInterface $expires_at = null);
    /**
     * Get the access token currently associated with the user.
     *
     * @return \Laravel\Sanctum\Contracts\HasAbilities
     */
    public function current_access_token();
    /**
     * Set the current access token for the user.
     *
     * @param  \Laravel\Sanctum\Contracts\HasAbilities  $accessToken
     * @return \Laravel\Sanctum\Contracts\HasApiTokens
     */
    public function with_access_token($access_token);
}