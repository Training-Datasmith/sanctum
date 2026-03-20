<?php

declare (strict_types=1);
namespace Laravel\Sanctum;

use DateTimeInterface;
use Illuminate\Support\Str;
/**
 * @template TToken of \Laravel\Sanctum\Contracts\HasAbilities = \Laravel\Sanctum\PersonalAccessToken
 */
trait Has_Api_Tokens
{
    /**
     * The access token the user is using for the current request.
     *
     * @var TToken
     */
    protected $access_token;
    /**
     * Get the access tokens that belong to model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<TToken, $this>
     */
    public function tokens()
    {
        return $this->morph_many(Sanctum::$personal_access_token_model, 'tokenable');
    }
    /**
     * Determine if the current API token has a given scope.
     */
    public function token_can(string $ability): bool
    {
        return $this->access_token && $this->access_token->can($ability);
    }
    /**
     * Determine if the current API token does not have a given scope.
     */
    public function token_cant(string $ability): bool
    {
        return !$this->token_can($ability);
    }
    /**
     * Create a new personal access token for the user.
     */
    public function create_token(string $name, array $abilities = ['*'], ?DateTimeInterface $expires_at = null): \Laravel\Sanctum\New_Access_Token
    {
        $plain_text_token = $this->generate_token_string();
        $token = $this->tokens()->create(['name' => $name, 'token' => hash('sha256', (string) $plain_text_token), 'abilities' => $abilities, 'expires_at' => $expires_at]);
        return new New_Access_Token($token, $token->get_key() . '|' . $plain_text_token);
    }
    /**
     * Generate the token string.
     */
    public function generate_token_string(): string
    {
        return sprintf('%s%s%s', config('sanctum.token_prefix', ''), $token_entropy = Str::random(40), hash('crc32b', $token_entropy));
    }
    /**
     * Get the access token currently associated with the user.
     *
     * @return TToken
     */
    public function current_access_token()
    {
        return $this->access_token;
    }
    /**
     * Set the current access token for the user.
     *
     * @param  TToken  $accessToken
     * @return $this
     */
    public function with_access_token($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }
}