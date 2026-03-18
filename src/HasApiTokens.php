<?php

declare(strict_types=1);

namespace Laravel\Sanctum;

use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * @template TToken of \Laravel\Sanctum\Contracts\HasAbilities = \Laravel\Sanctum\PersonalAccessToken
 */
trait HasApiTokens
{
    /**
     * The access token the user is using for the current request.
     *
     * @var TToken
     */
    protected $accessToken;

    /**
     * Get the access tokens that belong to model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<TToken, $this>
     */
    public function tokens()
    {
        return $this->morphMany(Sanctum::$personalAccessTokenModel, 'tokenable');
    }

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $ability): bool
    {
        return $this->accessToken && $this->accessToken->can($ability);
    }

    /**
     * Determine if the current API token does not have a given scope.
     */
    public function tokenCant(string $ability): bool
    {
        return ! $this->tokenCan($ability);
    }

    /**
     * Create a new personal access token for the user.
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): \Laravel\Sanctum\NewAccessToken
    {
        $plainTextToken = $this->generateTokenString();

        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', (string) $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    /**
     * Generate the token string.
     */
    public function generateTokenString(): string
    {
        return sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(40),
            hash('crc32b', $tokenEntropy)
        );
    }

    /**
     * Get the access token currently associated with the user.
     *
     * @return TToken
     */
    public function currentAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the current access token for the user.
     *
     * @param  TToken  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
