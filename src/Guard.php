<?php

declare (strict_types=1);
namespace Laravel\Sanctum;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Events\Token_Authenticated;
class Guard
{
    /**
     * Create a new guard instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth  The authentication factory implementation.
     * @param  int  $expiration  The number of minutes tokens should be allowed to remain valid.
     * @param  string  $provider  The provider name.
     * @param  bool  $trackLastUsedAt  Whether to track the last used timestamp.
     */
    public function __construct(protected Auth_Factory $auth, protected $expiration = null, protected $provider = null, protected $track_last_used_at = true)
    {
    }
    /**
     * Retrieve the authenticated user for the incoming request.
     *
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        foreach (Arr::wrap(config('sanctum.guard', 'web')) as $guard) {
            if ($user = $this->auth->guard($guard)->user()) {
                return $this->supports_tokens($user) ? $user->with_access_token(new Transient_Token()) : $user;
            }
        }
        if ($token = $this->get_token_from_request($request)) {
            $model = Sanctum::$personal_access_token_model;
            $access_token = $model::find_token($token);
            if (!$this->is_valid_access_token($access_token) || !$this->supports_tokens($access_token->tokenable)) {
                return;
            }
            $tokenable = $access_token->tokenable->with_access_token($access_token);
            event(new Token_Authenticated($access_token));
            if ($this->track_last_used_at) {
                $this->update_last_used_at($access_token);
            }
            return $tokenable;
        }
    }
    /**
     * Determine if the tokenable model supports API tokens.
     *
     * @param  mixed  $tokenable
     */
    protected function supports_tokens($tokenable = null): bool
    {
        return $tokenable && in_array(Has_Api_Tokens::class, class_uses_recursive($tokenable::class));
    }
    /**
     * Get the token from the request.
     *
     * @return string|null
     */
    protected function get_token_from_request(Request $request)
    {
        if (is_callable(Sanctum::$access_token_retrieval_callback)) {
            return (string) (Sanctum::$access_token_retrieval_callback)($request);
        }
        $token = $request->bearer_token();
        return $this->is_valid_bearer_token($token) ? $token : null;
    }
    /**
     * Determine if the bearer token is in the correct format.
     */
    protected function is_valid_bearer_token(?string $token = null): bool
    {
        if (!is_null($token) && str_contains($token, '|')) {
            $model = new Sanctum::$personal_access_token_model();
            if ($model->get_key_type() === 'int') {
                [$id, $token] = explode('|', $token, 2);
                return ctype_digit($id) && !empty($token);
            }
        }
        return !empty($token);
    }
    /**
     * Determine if the provided access token is valid.
     *
     * @param  mixed  $accessToken
     */
    protected function is_valid_access_token($access_token): bool
    {
        if (!$access_token) {
            return false;
        }
        $is_valid = (!$this->expiration || $access_token->created_at->gt(now()->sub_minutes($this->expiration))) && (!$access_token->expires_at || !$access_token->expires_at->is_past()) && $this->has_valid_provider($access_token->tokenable);
        if (is_callable(Sanctum::$access_token_authentication_callback)) {
            return (bool) (Sanctum::$access_token_authentication_callback)($access_token, $is_valid);
        }
        return $is_valid;
    }
    /**
     * Determine if the tokenable model matches the provider's model type.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $tokenable
     * @return bool
     */
    protected function has_valid_provider($tokenable)
    {
        if (is_null($this->provider)) {
            return true;
        }
        $model = config("auth.providers.{$this->provider}.model");
        return $tokenable instanceof $model;
    }
    /**
     * Store the time the token was last used.
     *
     * @param  \Laravel\Sanctum\PersonalAccessToken  $accessToken
     * @return void
     */
    protected function update_last_used_at($access_token)
    {
        if (method_exists($access_token->get_connection(), 'hasModifiedRecords') && method_exists($access_token->get_connection(), 'setRecordModificationState')) {
            $has_modified_records = $access_token->get_connection()->has_modified_records();
            $access_token->force_fill(['last_used_at' => now()])->save();
            $access_token->get_connection()->set_record_modification_state($has_modified_records);
        } else {
            $access_token->force_fill(['last_used_at' => now()])->save();
        }
    }
}